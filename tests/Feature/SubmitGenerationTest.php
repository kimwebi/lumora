<?php

namespace Tests\Feature;

use App\Jobs\SubmitGenerationJob;
use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\User;
use App\Services\Generations\GenerationFinalizer;
use App\Services\Generations\GenerationService;
use App\Services\Pixelforge\PixelforgeClient;
use App\Services\Pixelforge\PixelforgeException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubmitGenerationTest extends TestCase
{
    use RefreshDatabase;

    private function submit(Generation $generation): void
    {
        (new SubmitGenerationJob($generation->id))->handle(
            app(PixelforgeClient::class),
            app(GenerationFinalizer::class),
        );
    }

    #[Test]
    public function a_not_found_response_to_a_submit_is_retried_without_refunding(): void
    {
        Queue::fake();

        Http::fake([
            '*/v1/generations' => Http::sequence()
                ->push([
                    'error' => [
                        'type' => 'invalid_request_error',
                        'code' => 'not_found',
                        'message' => 'No such resource.',
                    ],
                ], 404)
                ->push([
                    'id' => 'gen_retried',
                    'object' => 'generation',
                    'status' => 'queued',
                ], 202),
        ]);

        $generation = Generation::factory()->create([
            'status' => Generation::STATUS_PENDING,
            'provider_generation_id' => null,
        ]);

        try {
            $this->submit($generation);
            $this->fail('A not-found response should be left to the queue retry, not failed permanently.');
        } catch (PixelforgeException $exception) {
            $this->assertSame(404, $exception->status);
            $this->assertTrue($exception->isNotFound());
        }

        $generation->refresh();

        $this->assertSame(Generation::STATUS_PENDING, $generation->status);
        $this->assertNull($generation->error_code);
        $this->assertSame(0, CreditTransaction::query()
            ->where('generation_id', $generation->id)
            ->where('type', CreditTransaction::TYPE_REFUND)
            ->count());

        $this->submit($generation);

        $generation->refresh();

        $this->assertSame('gen_retried', $generation->provider_generation_id);
        $this->assertNotSame(Generation::STATUS_FAILED, $generation->status);

        Http::assertSentCount(2);
    }

    #[Test]
    public function a_rejected_submit_records_the_provider_error_and_refunds(): void
    {
        Queue::fake();

        Http::fake([
            '*/v1/generations' => Http::response([
                'error' => [
                    'type' => 'invalid_request_error',
                    'code' => 'validation_failed',
                    'message' => 'The selected size is invalid.',
                ],
            ], 422),
        ]);

        $user = User::factory()->create(['credit_balance_micros' => 500_000]);
        $generation = app(GenerationService::class)->create($user, [
            'prompt' => 'A quiet lighthouse at dusk, painted in soft oils',
            'size' => '1024x1024',
            'quality' => 'medium',
            'reference_images' => [],
        ]);

        $this->submit($generation);

        $generation->refresh();

        $this->assertSame(Generation::STATUS_FAILED, $generation->status);
        $this->assertSame('provider_validation_failed', $generation->error_code);
        $this->assertSame(500_000, $user->fresh()->credit_balance_micros);
        $this->assertSame([
            'code' => 'validation_failed',
            'message' => 'The selected size is invalid.',
        ], $generation->meta['provider_error']);
        $this->assertSame('submit', $generation->meta['failure_source']);
    }

    #[Test]
    public function a_submit_that_runs_out_of_retries_is_refunded(): void
    {
        Queue::fake();

        $user = User::factory()->create(['credit_balance_micros' => 500_000]);
        $generation = app(GenerationService::class)->create($user, [
            'prompt' => 'A quiet lighthouse at dusk, painted in soft oils',
            'size' => '1024x1024',
            'quality' => 'medium',
            'reference_images' => [],
        ]);

        $this->assertLessThan(500_000, $user->fresh()->credit_balance_micros);

        (new SubmitGenerationJob($generation->id))->failed(new PixelforgeException('No such resource.', 404));

        $generation->refresh();

        $this->assertSame(Generation::STATUS_FAILED, $generation->status);
        $this->assertSame('provider_unavailable', $generation->error_code);
        $this->assertSame(0, $generation->charged_micros);
        $this->assertSame(500_000, $user->fresh()->credit_balance_micros);
    }
}
