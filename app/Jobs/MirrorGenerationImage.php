<?php

namespace App\Jobs;

use App\Models\Generation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Pixelforge image URLs are signed and stop working 24 hours after the render
 * completes. Copy each finished render onto the app's own disk so galleries and
 * detail pages keep working, and keep the provider URL for audit only.
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

    public function handle(): void
    {
        if (! config('lumora.generation.mirror_images', true)) {
            return;
        }

        $generation = Generation::query()->find($this->generationId);

        if (! $generation || $generation->status !== Generation::STATUS_SUCCEEDED) {
            return;
        }

        $sourceUrl = $generation->image_url;

        if (! is_string($sourceUrl) || $sourceUrl === '' || $this->isStoredUrl($sourceUrl)) {
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
        $maxBytes = (int) config('lumora.generation.image_max_bytes', 15 * 1024 * 1024);

        if ($body === '' || strlen($body) > $maxBytes) {
            throw new RuntimeException('Image download was empty or larger than the allowed size.');
        }

        $disk = (string) config('lumora.generation.image_disk', 'public');
        $path = 'generations/'.$generation->uuid.'.'.$this->extension($response->header('Content-Type'), $sourceUrl);

        if (! Storage::disk($disk)->put($path, $body)) {
            throw new RuntimeException('The generation image could not be stored.');
        }

        $meta = $generation->meta ?? [];
        $meta['provider_image_url'] = $sourceUrl;
        $meta['image_mirrored_at'] = now()->toIso8601String();

        $generation->forceFill([
            'image_url' => $this->publicPath($disk, $path),
            'thumbnail_url' => $this->publicPath($disk, $path),
            'meta' => $meta,
        ])->save();

        Log::info('Generation image mirrored locally', [
            'generation' => $generation->uuid,
            'path' => $path,
            'bytes' => strlen($body),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::warning('Generation image could not be mirrored before the provider URL expired', [
            'generation_id' => $this->generationId,
            'message' => $exception?->getMessage(),
        ]);
    }

    private function isStoredUrl(string $url): bool
    {
        return str_starts_with($url, '/storage/')
            || str_starts_with($url, 'storage/');
    }

    private function publicPath(string $disk, string $path): string
    {
        $url = Storage::disk($disk)->url($path);
        $urlPath = parse_url($url, PHP_URL_PATH);

        return is_string($urlPath) && $urlPath !== '' ? $urlPath : '/storage/'.$path;
    }

    private function extension(?string $contentType, string $sourceUrl): string
    {
        $map = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];

        $type = strtolower(trim(explode(';', (string) $contentType)[0]));

        if (isset($map[$type])) {
            return $map[$type];
        }

        $extension = strtolower(pathinfo((string) parse_url($sourceUrl, PHP_URL_PATH), PATHINFO_EXTENSION));

        return in_array($extension, ['png', 'jpg', 'jpeg', 'webp', 'gif'], true) ? $extension : 'png';
    }
}
