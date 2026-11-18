<?php

namespace App\Services\Admin;

use App\Models\Generation;
use App\Models\ProviderUsageRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class AdminMetrics
{
    public function overview(int $days = 14): array
    {
        $days = in_array($days, [7, 14, 30, 90], true) ? $days : 14;
        $start = CarbonImmutable::now()->subDays($days - 1)->startOfDay();

        $settled = Generation::withTrashed()->whereNotNull('settled_at');

        $providerMicros = (int) (clone $settled)->sum('actual_provider_cost_micros');
        $chargeMicros = (int) (clone $settled)->sum('actual_user_charge_micros');

        $statusCounts = Generation::withTrashed()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalGenerations = (int) $statusCounts->sum();
        $succeeded = (int) ($statusCounts[Generation::STATUS_SUCCEEDED] ?? 0);
        $failed = (int) ($statusCounts[Generation::STATUS_FAILED] ?? 0);
        $processing = (int) ($statusCounts[Generation::STATUS_PROCESSING] ?? 0)
            + (int) ($statusCounts[Generation::STATUS_PENDING] ?? 0);

        $dailyRows = Generation::withTrashed()
            ->where('settled_at', '>=', $start)
            ->selectRaw('DATE(settled_at) as day')
            ->selectRaw('COUNT(*) as generations')
            ->selectRaw('COALESCE(SUM(actual_provider_cost_micros), 0) as provider_micros')
            ->selectRaw('COALESCE(SUM(actual_user_charge_micros), 0) as charge_micros')
            ->selectRaw('COALESCE(SUM(actual_user_charge_micros - actual_provider_cost_micros), 0) as profit_micros')
            ->groupBy('day')
            ->get()
            ->keyBy(fn ($row) => CarbonImmutable::parse($row->day)->toDateString());

        $series = [];
        foreach (CarbonPeriod::create($start, CarbonImmutable::now()->startOfDay()) as $date) {
            $key = $date->toDateString();
            $row = $dailyRows->get($key);

            $series[] = [
                'date' => $key,
                'label' => $date->format('M j'),
                'generations' => (int) ($row->generations ?? 0),
                'provider_micros' => (int) ($row->provider_micros ?? 0),
                'charge_micros' => (int) ($row->charge_micros ?? 0),
                'profit_micros' => (int) ($row->profit_micros ?? 0),
            ];
        }

        $perUser = $this->perUserBreakdown();

        return [
            'range_days' => $days,
            'totals' => [
                'users' => User::query()->count(),
                'generations' => $totalGenerations,
                'succeeded' => $succeeded,
                'failed' => $failed,
                'processing' => $processing,
                'provider_micros' => $providerMicros,
                'charge_micros' => $chargeMicros,
                'profit_micros' => $chargeMicros - $providerMicros,
                'success_rate' => $totalGenerations > 0 ? round(($succeeded / max(1, $succeeded + $failed)) * 100, 1) : 0.0,
                'active_users' => User::query()->whereHas('generations')->count(),
            ],
            'series' => $series,
            'per_user' => $perUser,
            'reconciliation' => $this->reconciliation($providerMicros),
        ];
    }

    private function perUserBreakdown(): array
    {
        return User::query()
            ->leftJoin('generations', function ($join) {
                $join->on('generations.user_id', '=', 'users.id')
                    ->whereNotNull('generations.settled_at');
            })
            ->select(['users.id', 'users.name', 'users.email', 'users.credit_balance_micros'])
            ->selectRaw('COUNT(generations.id) as generations_count')
            ->selectRaw('COALESCE(SUM(generations.actual_user_charge_micros), 0) as charge_micros')
            ->selectRaw('COALESCE(SUM(generations.actual_provider_cost_micros), 0) as provider_micros')
            ->selectRaw('COALESCE(SUM(generations.actual_user_charge_micros - generations.actual_provider_cost_micros), 0) as profit_micros')
            ->groupBy('users.id', 'users.name', 'users.email', 'users.credit_balance_micros')
            ->orderByDesc('charge_micros')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => $row->name,
                'email' => $row->email,
                'balance_micros' => (int) $row->credit_balance_micros,
                'generations' => (int) $row->generations_count,
                'charge_micros' => (int) $row->charge_micros,
                'provider_micros' => (int) $row->provider_micros,
                'profit_micros' => (int) $row->profit_micros,
            ])
            ->all();
    }

    private function reconciliation(int $generationProviderMicros): array
    {
        $usageMicros = (int) ProviderUsageRecord::query()->sum('provider_cost_micros');
        $usageCount = ProviderUsageRecord::query()->count();
        $linked = ProviderUsageRecord::query()->whereNotNull('generation_id')->count();
        $lastSynced = ProviderUsageRecord::query()->max('updated_at');

        return [
            'usage_micros' => $usageMicros,
            'generation_micros' => $generationProviderMicros,
            'variance_micros' => $usageMicros - $generationProviderMicros,
            'usage_records' => $usageCount,
            'linked_records' => $linked,
            'last_synced_at' => $lastSynced,
        ];
    }
}
