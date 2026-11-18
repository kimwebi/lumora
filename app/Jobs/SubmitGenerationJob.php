<?php

namespace App\Jobs;

use App\Models\Generation;
use App\Services\Generations\GenerationFinalizer;
use App\Services\Pixelforge\PixelforgeClient;
use App\Services\Pixelforge\PixelforgeException;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class SubmitGenerationJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $uniqueFor = 600;

    public function __construct(public readonly int $generationId) {}

    public function uniqueId(): string
    {
        return 'generation:'.$this->generationId;
    }

    public function backoff(): array
    {
        return [5, 15, 30, 60];
    }

    public function handle(PixelforgeClient $client, GenerationFinalizer $finalizer): void
    {
        $generation = Generation::query()->find($this->generationId);

        if (! $generation || $generation->isTerminal()) {
            return;
        }

        if (! $client->isConfigured()) {
            $finalizer->fail(
                $generation,
                'provider_not_configured',
                'Image generation is not available yet. Add your Pixelforge credentials to enable the studio.',
                [],
                'configuration',
            );

            return;
        }

        $limiterKey = 'pixelforge-api';

        if (RateLimiter::tooManyAttempts($limiterKey, (int) config('lumora.provider.requests_per_minute', 60))) {
            $this->release(RateLimiter::availableIn($limiterKey) ?: 5);

            return;
        }

        RateLimiter::hit($limiterKey, 60);

        try {
            $payload = [
                'prompt' => $generation->prompt,
                'size' => $generation->size,
                'quality' => $generation->quality,
                'n' => 1,
                'metadata' => [
                    'generation_uuid' => $generation->uuid,
                    'lumora_user_id' => $generation->user_id,
                ],
            ];

            if (! empty($generation->reference_images)) {
                $payload['reference_images'] = array_values($generation->reference_images);
            }

            $response = $client->createGeneration($payload, 'lumora-generation-'.$generation->uuid);

            $generation->refresh();

            if ($generation->isTerminal()) {
                return;
            }

            $status = strtolower((string) $finalizer->providerStatus($response));

            $finalizer->markProcessing($generation, $response, 'submit');

            if (in_array($status, ['succeeded', 'success', 'completed', 'complete'], true)) {
                $finalizer->succeed($generation, $response, 'submit');
            } elseif (in_array($status, ['failed', 'error', 'cancelled', 'canceled'], true)) {
                $finalizer->fail(
                    $generation,
                    'provider_failed',
                    'The provider could not generate this image. Your credits were returned.',
                    $response,
                    'submit',
                );
            } else {
                PollGenerationJob::dispatch($generation->id)
                    ->delay(now()->addSeconds((int) config('lumora.generation.poll_interval_seconds', 5)));
            }
        } catch (PixelforgeException $exception) {
            Log::warning('Pixelforge submit failed', [
                'generation' => $generation->uuid,
                'status' => $exception->status,
                'code' => $exception->errorCode,
                'message' => $exception->getMessage(),
            ]);

            if ($exception->isClientError()) {
                $finalizer->fail(
                    $generation,
                    $exception->errorCode,
                    $exception->getMessage(),
                    $exception->response,
                    'submit',
                );

                return;
            }

            // Transient failure: let the queue retry with backoff.
            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $generation = Generation::query()->find($this->generationId);

        if (! $generation || $generation->isTerminal()) {
            return;
        }

        app(GenerationFinalizer::class)->fail(
            $generation,
            'provider_unavailable',
            'We could not reach the image provider. Your credits were returned — please try again.',
            [],
            'submit_failed',
        );
    }
}
