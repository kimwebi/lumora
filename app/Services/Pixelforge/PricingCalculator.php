<?php

namespace App\Services\Pixelforge;

use App\Support\Money;
use Illuminate\Support\Arr;

class PricingCalculator
{
    public function markupBasisPoints(): int
    {
        return (int) config('lumora.pricing.markup_basis_points', 2_500);
    }

    public function estimate(string $size, string $quality, int $images = 1): PriceEstimate
    {
        $images = max(1, $images);
        $basisPoints = $this->markupBasisPoints();

        $unit = (int) Arr::get(
            config('lumora.pricing.image_rates_micros', []),
            "{$size}.{$quality}",
            config('lumora.pricing.fallback_image_micros', 20_000),
        );

        $provider = $unit * $images;
        $markup = Money::applyMarkup($provider, $basisPoints);

        return new PriceEstimate(
            providerMicros: $provider,
            markupMicros: $markup,
            totalMicros: $provider + $markup,
            markupBasisPoints: $basisPoints,
            images: $images,
            size: $size,
            quality: $quality,
        );
    }

    /**
     * Derive the actual provider cost from a provider payload. Explicit cost
     * fields win; token usage is priced with the documented rates; the rate
     * card is the final fallback.
     */
    public function actualProviderCostMicros(
        ?array $payload,
        string $size = '1024x1024',
        string $quality = 'standard',
        int $images = 1,
    ): int {
        $payload = $payload ?? [];
        $usage = $this->usageFrom($payload);

        $explicit = $this->firstNumeric($payload, [
            'usage.cost_usd', 'usage.cost', 'data.usage.cost_usd', 'data.usage.cost',
            'cost_usd', 'cost', 'data.cost_usd', 'data.cost',
            'provider_cost_usd', 'provider_cost',
        ]);

        if ($explicit !== null && $explicit >= 0) {
            return (int) round($explicit * Money::MICROS_PER_UNIT);
        }

        $rates = config('lumora.pricing.usage_rates_micros_per_million', []);
        $input = (int) ($usage['input_tokens'] ?? $usage['prompt_tokens'] ?? 0);
        $output = (int) ($usage['output_tokens'] ?? $usage['completion_tokens'] ?? 0);
        $imageTokens = (int) ($usage['image_tokens'] ?? 0);
        $total = (int) ($usage['total_tokens'] ?? 0);

        if ($input === 0 && $output === 0 && $imageTokens === 0 && $total > 0) {
            $output = $total;
        }

        if ($input > 0 || $output > 0 || $imageTokens > 0) {
            $inputCost = $this->tokensToMicros($input, (int) ($rates['input_tokens'] ?? 0));
            $outputCost = $this->tokensToMicros($output, (int) ($rates['output_tokens'] ?? 0));
            $imageCost = $this->tokensToMicros($imageTokens, (int) ($rates['image_tokens'] ?? 0));

            return $inputCost + $outputCost + $imageCost;
        }

        return $this->estimate($size, $quality, $images)->providerMicros;
    }

    public function chargeForProviderCost(int $providerMicros): int
    {
        return Money::withMarkup($providerMicros, $this->markupBasisPoints());
    }

    public function usageFrom(array $payload): array
    {
        $usage = $payload['usage'] ?? $payload['data']['usage'] ?? null;

        if (! is_array($usage)) {
            return [];
        }

        return $usage;
    }

    private function tokensToMicros(int $tokens, int $ratePerMillion): int
    {
        if ($tokens <= 0 || $ratePerMillion <= 0) {
            return 0;
        }

        return intdiv($tokens * $ratePerMillion + 999_999, 1_000_000);
    }

    private function firstNumeric(array $payload, array $paths): ?float
    {
        foreach ($paths as $path) {
            $value = Arr::get($payload, $path);
            if (is_numeric($value)) {
                return (float) $value;
            }
        }

        return null;
    }
}
