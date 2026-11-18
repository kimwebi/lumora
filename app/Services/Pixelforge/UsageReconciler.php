<?php

namespace App\Services\Pixelforge;

use App\Models\Generation;
use App\Models\ProviderUsageRecord;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class UsageReconciler
{
    public function __construct(
        private readonly PixelforgeClient $client,
        private readonly PricingCalculator $pricing,
    ) {}

    /**
     * Pull provider usage for a window and upsert local usage records.
     *
     * @return array{fetched: int, stored: int, provider_micros: int, missing_generations: int}
     */
    public function sync(CarbonInterface $from, CarbonInterface $to): array
    {
        $response = $this->client->usage(
            $from->toIso8601String(),
            $to->toIso8601String(),
        );

        $records = $this->recordsFrom($response);
        $stored = 0;
        $providerTotal = 0;
        $missing = 0;

        foreach ($records as $record) {
            if (! is_array($record)) {
                continue;
            }

            $providerId = Arr::get($record, 'provider_generation_id')
                ?? Arr::get($record, 'generation_id')
                ?? Arr::get($record, 'generation.id');

            $generation = $providerId
                ? Generation::query()->where('provider_generation_id', $providerId)->first()
                : null;

            if ($providerId && ! $generation) {
                $missing++;
            }

            $usage = $this->pricing->usageFrom(['usage' => $record['usage'] ?? $record]);

            $explicit = null;
            foreach (['cost_usd', 'cost', 'provider_cost_usd', 'provider_cost'] as $key) {
                if (isset($record[$key]) && is_numeric($record[$key])) {
                    $explicit = (float) $record[$key];
                    break;
                }
            }

            $providerMicros = $explicit !== null
                ? (int) round($explicit * Money::MICROS_PER_UNIT)
                : $this->pricing->actualProviderCostMicros(
                    ['usage' => $record['usage'] ?? $record],
                    $generation?->size ?? '1024x1024',
                    $generation?->quality ?? 'standard',
                );

            $occurredAt = $record['created_at'] ?? $record['occurred_at'] ?? $record['timestamp'] ?? null;

            $usageId = (string) (
                Arr::get($record, 'id')
                ?? Arr::get($record, 'usage_id')
                ?? 'usage_'.substr(hash('sha256', json_encode($record) ?: Str::random(16)), 0, 32)
            );

            ProviderUsageRecord::query()->updateOrCreate(
                ['provider_usage_id' => $usageId],
                [
                    'provider_generation_id' => $providerId,
                    'generation_id' => $generation?->id,
                    'input_tokens' => (int) ($usage['input_tokens'] ?? $usage['prompt_tokens'] ?? 0),
                    'output_tokens' => (int) ($usage['output_tokens'] ?? $usage['completion_tokens'] ?? 0),
                    'image_tokens' => (int) ($usage['image_tokens'] ?? 0),
                    'total_tokens' => (int) ($usage['total_tokens'] ?? 0),
                    'provider_cost_micros' => $providerMicros,
                    'occurred_at' => $occurredAt,
                    'raw' => $record,
                ],
            );

            $stored++;
            $providerTotal += $providerMicros;
        }

        return [
            'fetched' => count($records),
            'stored' => $stored,
            'provider_micros' => $providerTotal,
            'missing_generations' => $missing,
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function recordsFrom(array $response): array
    {
        $candidates = [
            Arr::get($response, 'data'),
            Arr::get($response, 'usage'),
            Arr::get($response, 'items'),
            Arr::get($response, 'records'),
            Arr::get($response, 'data.data'),
        ];

        foreach ($candidates as $candidate) {
            if (is_array($candidate)) {
                return $this->looksAssociative($candidate) && ! $this->isList($candidate)
                    ? [$candidate]
                    : $candidate;
            }
        }

        return [];
    }

    private function looksAssociative(array $value): bool
    {
        return array_keys($value) !== range(0, count($value) - 1);
    }

    private function isList(array $value): bool
    {
        return array_is_list($value);
    }

    public function recordsForRange(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return ProviderUsageRecord::query()
            ->whereBetween('occurred_at', [$from, $to])
            ->get();
    }
}
