<?php

namespace App\Services\Pixelforge;

use App\Support\Money;
use Illuminate\Support\Arr;

class PricingCalculator
{
    private const TOKEN_RATE_KEYS = [
        'input_text_tokens' => ['input_text_tokens', 'input_tokens', 'prompt_tokens'],
        'input_image_tokens' => ['input_image_tokens', 'image_tokens'],
        'output_tokens' => ['output_tokens', 'completion_tokens'],
        'reasoning_tokens' => ['reasoning_tokens'],
    ];

    public function markupBasisPoints(): int
    {
        return (int) config('lumora.pricing.markup_basis_points', 2_500);
    }

    /**
     * Estimate the provider cost from the documented token model. Reasoning is
     * variable, so the maximum is used and the hold is always sufficient.
     */
    public function estimate(
        string $size,
        string $quality,
        int $images = 1,
        string $prompt = '',
        int $references = 0,
    ): PriceEstimate {
        $images = max(1, $images);
        $basisPoints = $this->markupBasisPoints();

        $unit = $this->estimatedProviderCostMicros($size, $quality, $prompt, $references);
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
     * fields win; token usage is priced with the rates reported alongside the
     * generation, then the configured rates; the token estimate is the final
     * fallback.
     */
    public function actualProviderCostMicros(
        ?array $payload,
        string $size = '1024x1024',
        string $quality = 'medium',
        int $images = 1,
    ): int {
        $payload = $payload ?? [];

        $explicit = $this->firstNumeric($payload, [
            'usage.cost_usd', 'usage.cost',
            'data.object.usage.cost_usd', 'data.object.usage.cost',
            'data.usage.cost_usd', 'data.usage.cost',
            'cost_usd', 'cost', 'data.cost_usd', 'data.cost',
            'provider_cost_usd', 'provider_cost',
        ]);

        if ($explicit !== null && $explicit >= 0) {
            return (int) round($explicit * Money::MICROS_PER_UNIT);
        }

        $usage = $this->usageFrom($payload);

        if ($usage === []) {
            return $this->estimate($size, $quality, $images)->providerMicros;
        }

        $rates = $this->ratesFrom($payload);
        $configured = config('lumora.pricing.usage_rates_micros_per_million', []);

        $numerator = 0;
        $priced = false;

        foreach (self::TOKEN_RATE_KEYS as $rateKey => $usageKeys) {
            $tokens = $this->tokens($usage, $usageKeys);

            if ($tokens <= 0) {
                continue;
            }

            $rate = (int) ($rates[$rateKey] ?? $configured[$rateKey] ?? 0);

            if ($rate <= 0) {
                continue;
            }

            $numerator += $tokens * $rate;
            $priced = true;
        }

        if ($priced) {
            return intdiv($numerator + 999_999, 1_000_000);
        }

        $total = (int) ($usage['total_tokens'] ?? 0);

        if ($total > 0) {
            $outputRate = (int) ($rates['output_tokens'] ?? $configured['output_tokens'] ?? 0);

            if ($outputRate > 0) {
                return intdiv($total * $outputRate + 999_999, 1_000_000);
            }
        }

        return $this->estimate($size, $quality, $images)->providerMicros;
    }

    public function chargeForProviderCost(int $providerMicros): int
    {
        return Money::withMarkup($providerMicros, $this->markupBasisPoints());
    }

    /**
     * Pull the usage block from either a generation object or a webhook event.
     *
     * @return array<string, mixed>
     */
    public function usageFrom(array $payload): array
    {
        $usage = $payload['usage']
            ?? Arr::get($payload, 'data.object.usage')
            ?? Arr::get($payload, 'data.usage');

        return is_array($usage) ? $usage : [];
    }

    /**
     * Rates reported with a generation, as micro-dollars per million tokens.
     *
     * @return array<string, int>
     */
    private function ratesFrom(array $payload): array
    {
        $rates = $payload['rates']
            ?? Arr::get($payload, 'data.object.rates')
            ?? Arr::get($payload, 'data.rates');

        if (! is_array($rates)) {
            return [];
        }

        $micros = [];

        foreach (self::TOKEN_RATE_KEYS as $rateKey => $usageKeys) {
            if (! isset($rates[$rateKey]) || ! is_numeric($rates[$rateKey])) {
                continue;
            }

            $micros[$rateKey] = (int) round((float) $rates[$rateKey] * Money::MICROS_PER_UNIT);
        }

        return $micros;
    }

    private function estimatedProviderCostMicros(
        string $size,
        string $quality,
        string $prompt,
        int $references,
    ): int {
        $rates = config('lumora.pricing.usage_rates_micros_per_million', []);

        $characters = mb_strlen($prompt, 'UTF-8');
        $charactersPerToken = max(1, (int) config('lumora.pricing.characters_per_text_token', 4));
        $textTokens = (int) ceil($characters / $charactersPerToken);
        $uncertainty = (int) config('lumora.pricing.text_token_uncertainty_basis_points', 1_500);
        $textTokens = (int) ceil($textTokens * (10_000 + $uncertainty) / 10_000);

        $imageTokens = max(0, $references) * (int) config('lumora.pricing.reference_image_tokens', 765);
        $outputTokens = Arr::get(
            config('lumora.pricing.output_token_estimates', []),
            "{$quality}.{$size}",
        );

        if (! is_numeric($outputTokens)) {
            return (int) config('lumora.pricing.fallback_image_micros', 20_000);
        }

        $reasoningTokens = (int) Arr::get(
            config('lumora.pricing.reasoning_token_max', []),
            $quality,
            0,
        );

        $numerator = $textTokens * (int) ($rates['input_text_tokens'] ?? 0)
            + $imageTokens * (int) ($rates['input_image_tokens'] ?? 0)
            + (int) $outputTokens * (int) ($rates['output_tokens'] ?? 0)
            + $reasoningTokens * (int) ($rates['reasoning_tokens'] ?? 0);

        return intdiv($numerator + 999_999, 1_000_000);
    }

    /**
     * @param  array<string, mixed>  $usage
     * @param  list<string>  $keys
     */
    private function tokens(array $usage, array $keys): int
    {
        foreach ($keys as $key) {
            if (isset($usage[$key]) && is_numeric($usage[$key])) {
                return max(0, (int) $usage[$key]);
            }
        }

        return 0;
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
