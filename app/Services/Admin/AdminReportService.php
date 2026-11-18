<?php

namespace App\Services\Admin;

use App\Models\Generation;
use App\Models\ProviderUsageRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AdminReportService
{
    public const RANGES = [7, 14, 30, 90];

    /**
     * @return array<string, mixed>
     */
    public function build(int $days = 14): array
    {
        $days = $this->normaliseDays($days);
        $from = CarbonImmutable::now()->subDays($days - 1)->startOfDay();
        $to = CarbonImmutable::now()->endOfDay();

        $settled = Generation::withTrashed()
            ->whereNotNull('settled_at')
            ->whereBetween('settled_at', [$from, $to]);

        $totals = $this->totals($settled);
        $usage = $this->usageTotals($from, $to);
        $daily = $this->daily($from, $to, $settled, $usage['by_day'], $usage['records'] > 0);
        $users = $this->perUser($settled, $from, $to, $usage['by_day'], $usage['records'] > 0);

        $variance = $usage['records'] > 0
            ? $usage['provider_micros'] - $totals['provider_micros']
            : null;

        return [
            'range_days' => $days,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'generated_at' => now()->toIso8601String(),
            'totals' => [
                ...$totals,
                'users' => User::query()->count(),
                'active_users' => User::query()
                    ->whereHas('generations', fn (Builder $query) => $query
                        ->withTrashed()
                        ->whereNotNull('settled_at')
                        ->whereBetween('settled_at', [$from, $to]))
                    ->count(),
                'credits' => round($totals['charge_micros'] / 10_000, 2),
                'margin_percent' => $totals['charge_micros'] > 0
                    ? round(($totals['profit_micros'] / $totals['charge_micros']) * 100, 1)
                    : 0.0,
                'provider_usage_micros' => $usage['records'] > 0 ? $usage['provider_micros'] : null,
                'provider_usage_profit_micros' => $usage['records'] > 0
                    ? $totals['charge_micros'] - $usage['provider_micros']
                    : null,
                'variance_micros' => $variance,
            ],
            'reconciliation' => [
                ...$usage,
                'by_day' => null,
                'unlinked_records' => $usage['records'] - $usage['linked_records'],
                'variance_micros' => $variance,
                'generation_micros' => $totals['provider_micros'],
            ],
            'daily' => $daily,
            'users' => $users,
        ];
    }

    public function normaliseDays(int $days): int
    {
        return in_array($days, self::RANGES, true) ? $days : 14;
    }

    /**
     * @return array{generations: int, succeeded: int, failed: int, charge_micros: int, provider_micros: int, profit_micros: int}
     */
    private function totals(Builder $settled): array
    {
        $row = (clone $settled)
            ->selectRaw('COUNT(*) as generations')
            ->selectRaw('COALESCE(SUM(actual_user_charge_micros), 0) as charge_micros')
            ->selectRaw('COALESCE(SUM(actual_provider_cost_micros), 0) as provider_micros')
            ->selectRaw('COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) as succeeded', [Generation::STATUS_SUCCEEDED])
            ->selectRaw('COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) as failed', [Generation::STATUS_FAILED])
            ->first();

        $charge = (int) ($row->charge_micros ?? 0);
        $provider = (int) ($row->provider_micros ?? 0);

        return [
            'generations' => (int) ($row->generations ?? 0),
            'succeeded' => (int) ($row->succeeded ?? 0),
            'failed' => (int) ($row->failed ?? 0),
            'charge_micros' => $charge,
            'provider_micros' => $provider,
            'profit_micros' => $charge - $provider,
        ];
    }

    /**
     * @return array{records: int, linked_records: int, active_days: int, provider_micros: int, last_synced_at: ?string, by_day: Collection<string, object>}
     */
    private function usageTotals(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $range = ProviderUsageRecord::query()
            ->whereRaw(
                'COALESCE(provider_usage_records.occurred_at, provider_usage_records.created_at) between ? and ?',
                [$from, $to],
            );

        $records = (clone $range)->count();
        $linked = (clone $range)->whereNotNull('generation_id')->count();
        $providerMicros = (int) (clone $range)->sum('provider_cost_micros');
        $lastSynced = (clone $range)->max('updated_at');

        $byDay = (clone $range)
            ->selectRaw('DATE(COALESCE(provider_usage_records.occurred_at, provider_usage_records.created_at)) as day')
            ->selectRaw('COUNT(*) as records')
            ->selectRaw('COALESCE(SUM(provider_cost_micros), 0) as provider_micros')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        return [
            'records' => $records,
            'linked_records' => $linked,
            'active_days' => $byDay->filter(fn (object $row): bool => (int) $row->provider_micros > 0)->count(),
            'provider_micros' => $providerMicros,
            'last_synced_at' => $lastSynced ? CarbonImmutable::parse($lastSynced)->toIso8601String() : null,
            'by_day' => $byDay,
        ];
    }

    /**
     * @param  Collection<string, object>  $usageByDay
     * @return array<int, array<string, mixed>>
     */
    private function daily(
        CarbonImmutable $from,
        CarbonImmutable $to,
        Builder $settled,
        Collection $usageByDay,
        bool $hasUsage,
    ): array {
        $localRows = (clone $settled)
            ->selectRaw('DATE(settled_at) as day')
            ->selectRaw('COUNT(*) as generations')
            ->selectRaw('COALESCE(SUM(actual_user_charge_micros), 0) as charge_micros')
            ->selectRaw('COALESCE(SUM(actual_provider_cost_micros), 0) as provider_micros')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $days = [];

        foreach (CarbonPeriod::create($from, $to->startOfDay()) as $date) {
            $key = $date->toDateString();
            $local = $localRows->get($key);
            $usage = $usageByDay->get($key);

            $charge = (int) ($local->charge_micros ?? 0);
            $provider = (int) ($local->provider_micros ?? 0);
            $usageMicros = $hasUsage ? (int) ($usage->provider_micros ?? 0) : null;

            $days[] = [
                'date' => $key,
                'label' => $date->format('M j'),
                'generations' => (int) ($local->generations ?? 0),
                'charge_micros' => $charge,
                'provider_micros' => $provider,
                'profit_micros' => $charge - $provider,
                'usage_provider_micros' => $usageMicros,
                'usage_records' => (int) ($usage->records ?? 0),
                'variance_micros' => $usageMicros !== null ? $usageMicros - $provider : null,
                'provider_usage_profit_micros' => $usageMicros !== null ? $charge - $usageMicros : null,
            ];
        }

        return $days;
    }

    /**
     * @param  Collection<string, object>  $usageByDay
     * @return array<int, array<string, mixed>>
     */
    private function perUser(
        Builder $settled,
        CarbonImmutable $from,
        CarbonImmutable $to,
        Collection $usageByDay,
        bool $hasUsage,
    ): array {
        $allocated = $this->allocateUsage(
            (clone $settled)
                ->selectRaw('user_id')
                ->selectRaw('DATE(settled_at) as day')
                ->selectRaw('COALESCE(SUM(actual_provider_cost_micros), 0) as provider_micros')
                ->groupBy('user_id', 'day')
                ->get(),
            $usageByDay,
        );

        return User::query()
            ->leftJoin('generations', function ($join) use ($from, $to): void {
                $join->on('generations.user_id', '=', 'users.id')
                    ->whereNotNull('generations.settled_at')
                    ->whereBetween('generations.settled_at', [$from, $to]);
            })
            ->select(['users.id', 'users.name', 'users.email', 'users.credit_balance_micros'])
            ->selectRaw('COUNT(generations.id) as generations')
            ->selectRaw('COALESCE(SUM(generations.actual_user_charge_micros), 0) as charge_micros')
            ->selectRaw('COALESCE(SUM(generations.actual_provider_cost_micros), 0) as provider_micros')
            ->groupBy('users.id', 'users.name', 'users.email', 'users.credit_balance_micros')
            ->havingRaw('COUNT(generations.id) > 0')
            ->orderByDesc('charge_micros')
            ->orderBy('users.name')
            ->get()
            ->map(function (object $row) use ($allocated, $hasUsage): array {
                $charge = (int) $row->charge_micros;
                $provider = (int) $row->provider_micros;
                $usageMicros = $hasUsage ? (int) ($allocated[(int) $row->id] ?? 0) : null;

                return [
                    'id' => (int) $row->id,
                    'name' => $row->name,
                    'email' => $row->email,
                    'balance_micros' => (int) $row->credit_balance_micros,
                    'generations' => (int) $row->generations,
                    'charge_micros' => $charge,
                    'provider_micros' => $provider,
                    'profit_micros' => $charge - $provider,
                    'usage_provider_micros' => $usageMicros,
                    'variance_micros' => $usageMicros !== null ? $usageMicros - $provider : null,
                    'provider_usage_profit_micros' => $usageMicros !== null ? $charge - $usageMicros : null,
                ];
            })
            ->all();
    }

    /**
     * Pixelforge reports usage as a daily total with no per-generation link.
     * Attribute each day's provider cost to users by their share of the local
     * settled provider cost that day, so per-user rows add up to the daily pool.
     *
     * @param  Collection<int, object>  $byUserDay
     * @param  Collection<string, object>  $usageByDay
     * @return array<int, int>
     */
    private function allocateUsage(Collection $byUserDay, Collection $usageByDay): array
    {
        $allocations = [];

        foreach ($byUserDay->groupBy('day') as $day => $rows) {
            $record = $usageByDay->get($day);
            $pool = (int) ($record->provider_micros ?? 0);
            $localTotal = (int) $rows->sum('provider_micros');

            if ($pool <= 0 || $localTotal <= 0) {
                continue;
            }

            $shares = [];
            $remainders = [];

            foreach ($rows as $row) {
                $exact = ((int) $row->provider_micros) * $pool;
                $userId = (int) $row->user_id;

                $shares[$userId] = intdiv($exact, $localTotal);
                $remainders[$userId] = $exact % $localTotal;
            }

            $remaining = $pool - array_sum($shares);

            if ($remaining > 0) {
                arsort($remainders);

                foreach (array_keys($remainders) as $userId) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $shares[$userId]++;
                    $remaining--;
                }
            }

            foreach ($shares as $userId => $micros) {
                $allocations[$userId] = ($allocations[$userId] ?? 0) + $micros;
            }
        }

        return $allocations;
    }
}
