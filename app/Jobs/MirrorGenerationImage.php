<?php

namespace App\Jobs;

use App\Models\Generation;
use App\Services\Generations\ImageCaptionStripper;
use App\Services\Images\GenerationImageStore;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Pixelforge image URLs are signed and stop working 24 hours after the render
 * completes. Copy each finished render onto the app's own disk so galleries and
 * detail pages keep working, and keep the provider URL for audit only.
 *
 * The job also doubles as a repair step: when the stored file has gone missing
 * (for example after a container redeploy) it retries from the provider URL if
 * one is still valid.
 */
class MirrorGenerationImage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 60, 300];

    public function __construct(public int $generationId) {}

    public function handle(ImageCaptionStripper $stripper, GenerationImageStore $store): void
    {
        if (! config('lumora.generation.mirror_images', true)) {
            return;
        }

        $generation = Generation::query()->find($this->generationId);

        if (! $generation || $generation->status !== Generation::STATUS_SUCCEEDED) {
            return;
        }

        $sourceUrl = $generation->image_url;

        if ($store->usesStoredImage($generation)) {
            if ($store->exists($generation)) {
                return;
            }

            $sourceUrl = $generation->meta['provider_image_url'] ?? null;

            if (! is_string($sourceUrl) || $sourceUrl === '') {
                Log::warning('Stored generation image is missing and no provider URL is recorded', [
                    'generation' => $generation->uuid,
                ]);

                return;
            }
        }

        if (! is_string($sourceUrl) || $sourceUrl === '') {
            return;
        }

        if (! filter_var($sourceUrl, FILTER_VALIDATE_URL) || ! in_array(parse_url($sourceUrl, PHP_URL_SCHEME), ['http', 'https'], true)) {
            Log::warning('Skipped mirroring a generation image with an unsupported URL', [
                'generation' => $generation->uuid,
            ]);

            return;
        }

        $response = Http::connectTimeout(10)
            ->timeout(30)
            ->get($sourceUrl);

        if (! $response->successful()) {
            throw new RuntimeException('Image download failed with HTTP '.$response->status().'.');
        }

        $body = $response->body();

        if ($body === '' || strlen($body) > $store->maxBytes()) {
            throw new RuntimeException('Image download was empty or larger than the allowed size.');
        }

        $extension = $store->extension($response->header('Content-Type'), $sourceUrl, $body);
        $captionsStripped = false;
        $removeCaptions = (bool) ($generation->meta['remove_captions']
            ?? config('lumora.generation.strip_captions', false));

        if ($extension === 'png' && $removeCaptions) {
            $clean = $stripper->strip($body);

            if ($clean !== $body) {
                $body = $clean;
                $captionsStripped = true;
            }
        }

        $meta = [
            'provider_image_url' => $sourceUrl,
            'image_source' => 'provider',
        ];

        if ($captionsStripped) {
            $meta['captions_stripped_at'] = now()->toIso8601String();
        }

        $path = $store->store($generation, $body, $extension, $meta);

        Log::info('Generation image mirrored locally', [
            'generation' => $generation->uuid,
            'path' => $path,
            'bytes' => strlen($body),
            'captions_stripped' => $captionsStripped,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::warning('Generation image could not be mirrored before the provider URL expired', [
            'generation_id' => $this->generationId,
            'message' => $exception?->getMessage(),
        ]);
    }
}
