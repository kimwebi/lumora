<?php

namespace App\Jobs;

use App\Models\Generation;
use App\Services\Generations\GenerationFinalizer;
use App\Services\Pixelforge\PixelforgeClient;
use App\Services\Pixelforge\PixelforgeException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PollGenerationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 60;

    public function __construct(public readonly int $generationId) {}

    public function handle(PixelforgeClient $client, GenerationFinalizer $finalizer): void
    {
        $generation = Generation::query()->find($this->generationId);

        if (! $generation || $generation->isTerminal()) {
            return;
        }

        if (blank($generation->provider_generation_id)) {
            $finalizer->fail(
                $generation,
                'provider_missing_reference',
                'The provider did not return a generation reference. Your credits were returned.',
                [],
                'poll',
            );

            return;
        }

        if ($this->attempts() > (int) config('lumora.generation.max_poll_attempts', 60)) {
            $finalizer->fail(
                $generation,
                'generation_timeout',
                'This image took too long to generate. Your credits were returned.',
                [],
                'poll_timeout',
            );

            return;
        }

        try {
            $response = $client->getGeneration($generation->provider_generation_id);
        } catch (PixelforgeException $exception) {
            Log::warning('Pixelforge poll failed', [
                'generation' => $generation->uuid,
                'status' => $exception->status,
                'code' => $exception->errorCode,
            ]);

            if ($exception->isClientError() && $exception->status !== 404) {
                $finalizer->fail(
                    $generation,
                    $exception->errorCode,
                    $exception->getMessage(),
                    $exception->response,
                    'poll',
                );

                return;
            }

            $this->release($exception->retryDelay((int) config('lumora.generation.poll_interval_seconds', 5)));

            return;
        }

        $generation->refresh();

        if ($generation->isTerminal()) {
            return;
        }

        $status = strtolower((string) $finalizer->providerStatus($response));

        if (in_array($status, ['succeeded', 'success', 'completed', 'complete'], true)) {
            $finalizer->succeed($generation, $response, 'poll');

            return;
        }

        if (in_array($status, ['failed', 'error', 'cancelled', 'canceled'], true)) {
            $finalizer->fail(
                $generation,
                'provider_failed',
                'The provider could not generate this image. Your credits were returned.',
                $response,
                'poll',
            );

            return;
        }

        $finalizer->markProcessing($generation, $response, 'poll');

        $this->release((int) config('lumora.generation.poll_interval_seconds', 5));
    }
}
