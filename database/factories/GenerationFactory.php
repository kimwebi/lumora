<?php

namespace Database\Factories;

use App\Models\Generation;
use App\Models\User;
use App\Services\Pixelforge\PricingCalculator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Generation>
 */
class GenerationFactory extends Factory
{
    protected $model = Generation::class;

    public function definition(): array
    {
        $sizes = config('lumora.generation.size_options', []);
        $size = fake()->randomElement($sizes);
        $quality = fake()->randomElement(collect(config('lumora.generation.quality_options', []))->pluck('value')->all());
        $estimate = app(PricingCalculator::class)->estimate($size['value'], $quality);

        return [
            'uuid' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'prompt' => fake()->sentence(14),
            'size' => $size['value'],
            'width' => $size['width'],
            'height' => $size['height'],
            'quality' => $quality,
            'reference_images' => null,
            'status' => Generation::STATUS_PENDING,
            'provider' => 'pixelforge',
            'estimated_provider_cost_micros' => $estimate->providerMicros,
            'estimated_user_charge_micros' => $estimate->totalMicros,
            'markup_basis_points' => $estimate->markupBasisPoints,
        ];
    }

    public function succeeded(): static
    {
        return $this->state(fn () => [
            'status' => Generation::STATUS_SUCCEEDED,
            'image_url' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=1024',
            'actual_provider_cost_micros' => 20_000,
            'actual_user_charge_micros' => 25_000,
            'charged_micros' => 25_000,
            'settled_at' => now(),
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => Generation::STATUS_FAILED,
            'error_code' => 'provider_failed',
            'error_message' => 'The provider could not generate this image.',
            'actual_provider_cost_micros' => 0,
            'actual_user_charge_micros' => 0,
            'charged_micros' => 0,
            'settled_at' => now(),
            'failed_at' => now(),
        ]);
    }
}
