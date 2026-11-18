<?php

namespace App\Services\Generations;

use App\Models\Generation;
use App\Services\Billing\CreditService;
use App\Services\Pixelforge\PricingCalculator;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Single, idempotent place where a generation reaches a terminal state and the
 * wallet is settled. Webhooks, polling and reconciliation all funnel here.
 */
class GenerationFinalizer
{
    public function __construct(
        private readonly PricingCalculator $pricing,
        private readonly CreditService $credits,
    ) {}

    public function succeed(Generation $generation, array $payload, string $source, ?CarbonInterface $occurredAt = null): bool
    {
        return DB::transaction(function () use ($generation, $payload, $source, $occurredAt) {
            $locked = Generation::query()->whereKey($generation->id)->lockForUpdate()->firstOrFail();

            if ($locked->isSettled() || ! $this->acceptEvent($locked, $occurredAt)) {
                return false;
            }

            $usage = $this->pricing->usageFrom($payload);
            $providerCost = $this->pricing->actualProviderCostMicros(
                $payload,
                $locked->size,
                $locked->quality,
            );
            $actualCharge = $this->pricing->chargeForProviderCost($providerCost);

            $user = $this->credits->lockUser($locked->user_id);
            $settlement = $this->credits->settleGeneration($user, $locked, $actualCharge);

            $locked->forceFill([
                'status' => Generation::STATUS_SUCCEEDED,
                'provider_status' => (string) ($this->providerStatus($payload) ?? 'succeeded'),
                'image_url' => $this->imageUrl($payload),
                'thumbnail_url' => $this->imageUrl($payload),
                'actual_provider_cost_micros' => $providerCost,
                'actual_user_charge_micros' => $settlement['charged'],
                'charged_micros' => $settlement['charged'],
                'usage' => $usage ?: null,
                'error_code' => null,
                'error_message' => null,
                'completed_at' => now(),
                'settled_at' => now(),
                'last_provider_event_at' => $occurredAt ?? now(),
                'provider_generation_id' => $locked->provider_generation_id ?? $this->providerGenerationId($payload),
                'meta' => array_merge($locked->meta ?? [], [
                    'settlement_source' => $source,
                    'shortfall_micros' => $settlement['shortfall'],
                ]),
            ])->save();

            Log::info('Generation settled successfully', [
                'generation' => $locked->uuid,
                'source' => $source,
                'provider_micros' => $providerCost,
                'charge_micros' => $settlement['charged'],
            ]);

            return true;
        });
    }

    public function fail(
        Generation $generation,
        string $code,
        ?string $message,
        array $payload = [],
        string $source = 'system',
        ?CarbonInterface $occurredAt = null,
    ): bool {
        return DB::transaction(function () use ($generation, $code, $message, $payload, $source, $occurredAt) {
            $locked = Generation::query()->whereKey($generation->id)->lockForUpdate()->firstOrFail();

            if ($locked->isSettled() || ! $this->acceptEvent($locked, $occurredAt)) {
                return false;
            }

            $user = $this->credits->lockUser($locked->user_id);
            $this->credits->refundGeneration($user, $locked);

            // Failed generations are free for the customer. Provider cost, when
            // reported, is still recorded for admin reconciliation.
            $reportedProviderCost = $this->pricing->actualProviderCostMicros(
                $payload,
                $locked->size,
                $locked->quality,
            );

            $locked->forceFill([
                'status' => Generation::STATUS_FAILED,
                'provider_status' => (string) ($this->providerStatus($payload) ?? 'failed'),
                'error_code' => $code,
                'error_message' => $message ?: 'The image could not be generated. Your credits were returned.',
                'actual_provider_cost_micros' => $this->hasUsage($payload) ? $reportedProviderCost : 0,
                'actual_user_charge_micros' => 0,
                'charged_micros' => 0,
                'usage' => $this->pricing->usageFrom($payload) ?: null,
                'failed_at' => now(),
                'settled_at' => now(),
                'last_provider_event_at' => $occurredAt ?? now(),
                'provider_generation_id' => $locked->provider_generation_id ?? $this->providerGenerationId($payload),
                'meta' => array_merge($locked->meta ?? [], ['failure_source' => $source]),
            ])->save();

            Log::info('Generation failed and credits refunded', [
                'generation' => $locked->uuid,
                'source' => $source,
                'code' => $code,
            ]);

            return true;
        });
    }

    /**
     * Record an intermediate "still processing" state without touching money.
     */
    public function markProcessing(Generation $generation, array $payload, string $source): void
    {
        DB::transaction(function () use ($generation, $payload, $source) {
            $locked = Generation::query()->whereKey($generation->id)->lockForUpdate()->firstOrFail();

            if ($locked->isTerminal()) {
                return;
            }

            $locked->forceFill([
                'status' => Generation::STATUS_PROCESSING,
                'provider_status' => $this->providerStatus($payload),
                'provider_generation_id' => $locked->provider_generation_id ?? $this->providerGenerationId($payload),
                'submitted_at' => $locked->submitted_at ?? now(),
                'meta' => array_merge($locked->meta ?? [], ['last_status_source' => $source]),
            ])->save();
        });
    }

    public function acceptEvent(Generation $generation, ?CarbonInterface $occurredAt): bool
    {
        if ($occurredAt === null || $generation->last_provider_event_at === null) {
            return true;
        }

        // Ignore out-of-order events older than the last applied transition.
        return $occurredAt->greaterThanOrEqualTo($generation->last_provider_event_at);
    }

    public function imageUrl(array $payload): ?string
    {
        $candidates = [
            'data.images.0.url',
            'data.images.0',
            'images.0.url',
            'images.0',
            'data.image_url',
            'data.url',
            'data.output.0.url',
            'data.output.0',
            'output.0.url',
            'output.0',
            'image_url',
            'url',
            'result.url',
        ];

        foreach ($candidates as $path) {
            $value = Arr::get($payload, $path);

            if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
                return $value;
            }
        }

        return null;
    }

    public function providerGenerationId(array $payload): ?string
    {
        foreach (['id', 'generation_id', 'data.id', 'data.generation_id', 'generation.id'] as $path) {
            $value = Arr::get($payload, $path);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    public function providerStatus(array $payload): ?string
    {
        foreach (['status', 'data.status', 'generation.status'] as $path) {
            $value = Arr::get($payload, $path);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function hasUsage(array $payload): bool
    {
        return $this->pricing->usageFrom($payload) !== [];
    }
}
