<?php

namespace Tests\Feature;

use App\Jobs\SubmitGenerationJob;
use App\Models\Generation;
use App\Services\Generations\GenerationFinalizer;
use App\Services\Pixelforge\PixelforgeClient;
use App\Services\Pixelforge\PricingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PricingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_estimate_matches_the_documented_high_quality_example(): void
    {
        $estimate = app(PricingCalculator::class)->estimate(
            '1536x1024',
            'high',
            1,
            str_repeat('a', 400),
            2,
        );

        // 115 text + 1,530 image + 6,450 output + 8,000 reasoning tokens.
        $this->assertSame(373_875, $estimate->providerMicros);
        $this->assertSame(93_469, $estimate->markupMicros);
        $this->assertSame(467_344, $estimate->totalMicros);
    }

    #[Test]
    public function actual_cost_uses_the_reported_usage_and_rates(): void
    {
        $cost = app(PricingCalculator::class)->actualProviderCostMicros([
            'id' => 'gen_123',
            'status' => 'succeeded',
            'usage' => [
                'input_text_tokens' => 100,
                'input_image_tokens' => 765,
                'output_tokens' => 1_100,
                'reasoning_tokens' => 1_000,
                'total_tokens' => 2_965,
            ],
            'rates' => [
                'input_text_tokens' => '5.00',
                'input_image_tokens' => '10.00',
                'output_tokens' => '40.00',
                'reasoning_tokens' => '12.50',
            ],
        ]);

        // $0.0005 + $0.00765 + $0.044 + $0.0125
        $this->assertSame(64_650, $cost);
    }

    #[Test]
    public function the_submit_request_only_sends_fields_the_provider_accepts(): void
    {
        Queue::fake();
        Http::fake([
            '*/v1/generations' => Http::response([
                'id' => 'gen_test',
                'object' => 'generation',
                'status' => 'queued',
            ], 202),
        ]);

        $generation = Generation::factory()->create([
            'status' => Generation::STATUS_PENDING,
            'size' => '1024x1024',
            'quality' => 'medium',
            'reference_images' => ['https://example.com/style.png'],
            'provider_generation_id' => null,
        ]);

        (new SubmitGenerationJob($generation->id))->handle(
            app(PixelforgeClient::class),
            app(GenerationFinalizer::class),
        );

        Http::assertSent(function (Request $request) use ($generation): bool {
            $data = $request->data();

            return str_ends_with($request->url(), '/v1/generations')
                && $data['prompt'] === $generation->prompt
                && $data['size'] === '1024x1024'
                && $data['quality'] === 'medium'
                && $data['reference_images'] === ['https://example.com/style.png']
                && $data['metadata']['generation_uuid'] === $generation->uuid
                && is_string($data['metadata']['lumora_user_id'])
                && ! array_key_exists('n', $data);
        });
    }
}
