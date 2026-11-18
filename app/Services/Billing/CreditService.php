<?php

namespace App\Services\Billing;

use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * All wallet mutations go through this service inside a database transaction.
 * Ledger rows are the source of truth for every credit movement.
 */
class CreditService
{
    public function lockUser(int $userId): User
    {
        return User::query()->whereKey($userId)->lockForUpdate()->firstOrFail();
    }

    public function holdForGeneration(User $user, Generation $generation): CreditTransaction
    {
        $amount = $generation->estimated_user_charge_micros;

        if ($user->credit_balance_micros < $amount) {
            throw new RuntimeException('Insufficient credits to hold for this generation.');
        }

        return $this->record(
            user: $user,
            amountMicros: -$amount,
            type: CreditTransaction::TYPE_HOLD,
            description: 'Estimated charge held for generation',
            generation: $generation,
            meta: [
                'estimated_provider_micros' => $generation->estimated_provider_cost_micros,
                'markup_basis_points' => $generation->markup_basis_points,
            ],
        );
    }

    /**
     * Settle a successful generation. The held estimate is replaced by the
     * actual charge: the difference is refunded or debited (capped at the
     * remaining balance so wallets never go negative).
     *
     * @return array{charged: int, adjustment: int, shortfall: int}
     */
    public function settleGeneration(User $user, Generation $generation, int $actualChargeMicros): array
    {
        $hold = $this->holdFor($generation);
        $held = $hold ? abs($hold->amount_micros) : 0;
        $target = max(0, $actualChargeMicros);
        $adjustment = $target - $held;
        $shortfall = 0;

        if ($adjustment < 0) {
            // Refund the unused estimate.
            $this->record(
                user: $user,
                amountMicros: -$adjustment,
                type: CreditTransaction::TYPE_SETTLEMENT,
                description: 'Settlement refund for generation',
                generation: $generation,
                meta: ['held_micros' => $held, 'actual_micros' => $target],
            );
        } elseif ($adjustment > 0) {
            if ($user->credit_balance_micros < $adjustment) {
                $shortfall = $adjustment - $user->credit_balance_micros;
                $adjustment = $user->credit_balance_micros;
            }

            $this->record(
                user: $user,
                amountMicros: -$adjustment,
                type: CreditTransaction::TYPE_SETTLEMENT,
                description: 'Final charge adjustment for generation',
                generation: $generation,
                meta: [
                    'held_micros' => $held,
                    'actual_micros' => $target,
                    'shortfall_micros' => $shortfall,
                ],
            );
        } else {
            $this->record(
                user: $user,
                amountMicros: 0,
                type: CreditTransaction::TYPE_SETTLEMENT,
                description: 'Settlement matched the held estimate',
                generation: $generation,
                meta: ['held_micros' => $held, 'actual_micros' => $target],
            );
        }

        return [
            'charged' => $held + $adjustment,
            'adjustment' => $adjustment,
            'shortfall' => $shortfall,
        ];
    }

    public function refundGeneration(User $user, Generation $generation): void
    {
        $hold = $this->holdFor($generation);

        if (! $hold) {
            return;
        }

        $this->record(
            user: $user,
            amountMicros: abs($hold->amount_micros),
            type: CreditTransaction::TYPE_REFUND,
            description: 'Generation failed — credits returned',
            generation: $generation,
            meta: ['reason' => 'generation_failed'],
        );
    }

    public function purchase(User $user, string $packageKey, ?string $reference = null): CreditTransaction
    {
        $package = Arr::get(config('lumora.credits.packages', []), $packageKey);

        if (! is_array($package)) {
            throw new RuntimeException('Unknown credit package.');
        }

        $reference ??= 'purchase_'.Str::uuid();
        $existing = CreditTransaction::query()->where('reference', $reference)->first();

        if ($existing) {
            return $existing;
        }

        $credits = (int) $package['credits'];
        $amount = Money::creditsToMicros($credits);

        return $this->record(
            user: $user,
            amountMicros: $amount,
            type: CreditTransaction::TYPE_PURCHASE,
            description: "{$package['name']} pack · {$credits} credits",
            reference: $reference,
            meta: [
                'package' => $packageKey,
                'credits' => $credits,
                'price_micros' => (int) $package['price_micros'],
            ],
        );
    }

    public function grant(User $user, int $amountMicros, string $type, string $description, ?string $reference = null, array $meta = []): CreditTransaction
    {
        if (! in_array($type, [CreditTransaction::TYPE_BONUS, CreditTransaction::TYPE_ADJUSTMENT], true)) {
            throw new RuntimeException('Invalid grant type.');
        }

        if ($reference) {
            $existing = CreditTransaction::query()->where('reference', $reference)->first();

            if ($existing) {
                return $existing;
            }
        }

        return $this->record(
            user: $user,
            amountMicros: max(0, $amountMicros),
            type: $type,
            description: $description,
            reference: $reference,
            meta: $meta,
        );
    }

    public function holdFor(Generation $generation): ?CreditTransaction
    {
        return CreditTransaction::query()
            ->where('generation_id', $generation->id)
            ->where('type', CreditTransaction::TYPE_HOLD)
            ->first();
    }

    public function summary(User $user): array
    {
        $purchased = (int) $user->creditTransactions()
            ->where('type', CreditTransaction::TYPE_PURCHASE)
            ->sum('amount_micros');

        $bonus = (int) $user->creditTransactions()
            ->where('type', CreditTransaction::TYPE_BONUS)
            ->sum('amount_micros');

        $consumed = (int) $user->generations()
            ->where('status', Generation::STATUS_SUCCEEDED)
            ->sum('actual_user_charge_micros');

        $pending = (int) $user->generations()
            ->processing()
            ->sum('estimated_user_charge_micros');

        return [
            'balance_micros' => (int) $user->credit_balance_micros,
            'purchased_micros' => $purchased,
            'bonus_micros' => $bonus,
            'consumed_micros' => $consumed,
            'pending_micros' => $pending,
        ];
    }

    private function record(
        User $user,
        int $amountMicros,
        string $type,
        string $description,
        ?Generation $generation = null,
        ?string $reference = null,
        array $meta = [],
    ): CreditTransaction {
        if ($generation !== null) {
            $existing = CreditTransaction::query()
                ->where('generation_id', $generation->id)
                ->where('type', $type)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $nextBalance = $user->credit_balance_micros + $amountMicros;

        if ($nextBalance < 0) {
            throw new RuntimeException('Credit balance cannot become negative.');
        }

        $user->credit_balance_micros = $nextBalance;
        $user->save();

        return CreditTransaction::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'generation_id' => $generation?->id,
            'type' => $type,
            'amount_micros' => $amountMicros,
            'balance_after_micros' => $nextBalance,
            'reference' => $reference,
            'description' => $description,
            'meta' => $meta ?: null,
        ]);
    }
}
