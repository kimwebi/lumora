<?php

namespace App\Services\Images;

use App\Models\Generation;
use App\Models\GenerationImage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Owns where generation images live and how their public URLs are built.
 *
 * Two drivers are supported:
 *
 * - "disk" (default outside production): files are written to LUMORA_IMAGE_DISK
 *   and served through the storage symlink. Fast, but the files disappear when
 *   a container host rebuilds the filesystem.
 * - "database": the bytes are kept in the generation_images table and served
 *   from /media/generations/{uuid}. Survives redeploys and restarts on hosts
 *   without persistent disks, such as Render's free plan.
 *
 * Every write and check goes through this class so local, restored and
 * re-fetched images follow the same rules.
 */
class GenerationImageStore
{
    private const DIRECTORY = 'generations';

    private const MEDIA_PREFIX = '/media/generations/';

    /**
     * @var list<string>
     */
    private const EXTENSIONS = ['png', 'jpg', 'jpeg', 'webp', 'gif'];

    /**
     * @var array<string, string>
     */
    private const MIME_EXTENSIONS = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    /**
     * @var array<string, string>
     */
    private const EXTENSION_MIMES = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
    ];

    public function disk(): string
    {
        return (string) config('lumora.generation.image_disk', 'public');
    }

    /**
     * Where images are stored: "database" or "disk".
     */
    public function driver(): string
    {
        return config('lumora.generation.image_store', 'disk') === 'database' ? 'database' : 'disk';
    }

    public function storesInDatabase(): bool
    {
        return $this->driver() === 'database';
    }

    public function maxBytes(): int
    {
        $maximum = (int) config('lumora.generation.image_max_bytes', 15 * 1024 * 1024);

        if ($this->storesInDatabase()) {
            $maximum = min($maximum, (int) config('lumora.generation.image_database_max_bytes', 8 * 1024 * 1024));
        }

        return $maximum;
    }

    /**
     * The path of the stored file on the configured disk, or null when the
     * render points at an external URL (or at the database) we do not own.
     */
    public function storedPath(Generation $generation): ?string
    {
        $metaPath = $generation->meta['image_path'] ?? null;

        if (is_string($metaPath) && $metaPath !== '') {
            return ltrim($metaPath, '/');
        }

        $url = (string) $generation->image_url;
        $path = parse_url($url, PHP_URL_PATH);
        $host = parse_url($url, PHP_URL_HOST);

        if ($this->isLocalDisk() && is_string($host) && $host !== '' && ! $this->isAppHost($host)) {
            return null;
        }

        $path = ltrim(is_string($path) && $path !== '' ? $path : $url, '/');

        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        if (! str_starts_with($path, self::DIRECTORY.'/')) {
            return null;
        }

        return $path;
    }

    /**
     * Whether this render is served from storage we control rather than a
     * third-party URL.
     */
    public function usesStoredImage(Generation $generation): bool
    {
        if ($this->storedPath($generation) !== null) {
            return true;
        }

        if (($generation->meta['image_storage'] ?? null) === 'database') {
            return true;
        }

        return str_starts_with((string) parse_url((string) $generation->image_url, PHP_URL_PATH), self::MEDIA_PREFIX);
    }

    /**
     * The durable copy of a render, if one has been written to the database.
     */
    public function databaseImage(Generation $generation): ?GenerationImage
    {
        return GenerationImage::query()
            ->where('generation_id', $generation->id)
            ->first();
    }

    /**
     * Whether the render's image can currently be displayed.
     */
    public function exists(Generation $generation): bool
    {
        if (! $this->usesStoredImage($generation)) {
            return filled($generation->image_url);
        }

        if ($this->storesInDatabase()) {
            return GenerationImage::query()
                ->where('generation_id', $generation->id)
                ->exists();
        }

        $path = $this->storedPath($generation);

        return $path !== null && Storage::disk($this->disk())->exists($path);
    }

    /**
     * Succeeded renders whose image is gone.
     *
     * @return Collection<int, Generation>
     */
    public function missing(?int $limit = null): Collection
    {
        $missing = collect();

        Generation::query()
            ->with('user:id,name,email')
            ->where('status', Generation::STATUS_SUCCEEDED)
            ->whereNotNull('image_url')
            ->latest('id')
            ->chunk(200, function (Collection $generations) use (&$missing, $limit): bool {
                foreach ($generations as $generation) {
                    if ($this->exists($generation)) {
                        continue;
                    }

                    $missing->push($generation);

                    if ($limit !== null && $missing->count() >= $limit) {
                        return false;
                    }
                }

                return true;
            });

        return $missing->values();
    }

    /**
     * Match an uploaded file to a render. Files downloaded from the app are
     * named after the generation UUID, but browsers add suffixes like
     * "(1)" when the same file is saved twice.
     */
    public function generationForFilename(string $filename): ?Generation
    {
        $stem = pathinfo($filename, PATHINFO_FILENAME);

        if (preg_match('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i', $stem, $matches)) {
            $generation = Generation::withTrashed()
                ->where('uuid', strtolower($matches[0]))
                ->first();

            if ($generation) {
                return $generation;
            }
        }

        if (ctype_digit($stem)) {
            return Generation::withTrashed()->find((int) $stem);
        }

        return null;
    }

    /**
     * Write raw image bytes for a render and point the row at the new copy.
     */
    public function store(Generation $generation, string $contents, string $extension, array $meta = []): string
    {
        if ($contents === '') {
            throw new RuntimeException('The image was empty.');
        }

        if (strlen($contents) > $this->maxBytes()) {
            throw new RuntimeException($this->storesInDatabase()
                ? 'The image is larger than the database limit. Use a persistent disk or S3 for large files.'
                : 'The image is larger than the allowed size.');
        }

        $extension = $this->normaliseExtension($extension);

        if ($this->storesInDatabase()) {
            return $this->storeInDatabase($generation, $contents, $extension, $meta);
        }

        return $this->storeOnDisk($generation, $contents, $extension, $meta);
    }

    /**
     * Restore a render from a file on the local filesystem, such as an image
     * downloaded before the container was rebuilt.
     */
    public function restoreFile(Generation $generation, string $file, ?string $mime = null): string
    {
        $contents = @file_get_contents($file);

        if ($contents === false) {
            throw new RuntimeException('The image file could not be read.');
        }

        return $this->store($generation, $contents, $this->extension($mime, $file, $contents), [
            'image_restore_source' => 'upload',
            'image_restored_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Re-download a render from the Pixelforge URL kept for audit. Provider
     * URLs expire 24 hours after completion, so this only helps for recent
     * renders.
     */
    public function restoreFromProvider(Generation $generation): string
    {
        if ($generation->status !== Generation::STATUS_SUCCEEDED) {
            throw new RuntimeException('Only completed renders can be restored.');
        }

        $url = $generation->meta['provider_image_url'] ?? null;

        if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('No Pixelforge image URL is stored for this render.');
        }

        if (! in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
            throw new RuntimeException('The stored Pixelforge image URL is not valid.');
        }

        try {
            $response = Http::connectTimeout(10)->timeout(30)->get($url);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Pixelforge could not be reached: '.$exception->getMessage());
        }

        if (! $response->successful()) {
            throw new RuntimeException('Pixelforge returned HTTP '.$response->status().'. The link has probably expired.');
        }

        $body = $response->body();
        $mime = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));

        if (! str_starts_with($mime, 'image/')) {
            throw new RuntimeException('Pixelforge did not return an image.');
        }

        return $this->store($generation, $body, $this->extension($mime, $url, $body), [
            'provider_image_url' => $url,
            'image_restore_source' => 'provider',
            'image_restored_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * The current image bytes for a render, from whichever driver holds them.
     */
    public function contents(Generation $generation): ?string
    {
        if ($this->storesInDatabase()) {
            $image = $this->databaseImage($generation);

            if ($image === null) {
                return null;
            }

            $decoded = base64_decode($image->data, true);

            return $decoded === false || $decoded === '' ? null : $decoded;
        }

        $path = $this->storedPath($generation);

        if ($path === null) {
            return null;
        }

        $disk = Storage::disk($this->disk());

        return $disk->exists($path) ? (string) $disk->get($path) : null;
    }

    public function extension(?string $mime, ?string $name, string $contents = ''): string
    {
        $mime = strtolower(trim(explode(';', (string) $mime)[0]));

        if (isset(self::MIME_EXTENSIONS[$mime])) {
            return self::MIME_EXTENSIONS[$mime];
        }

        $extension = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));

        if (in_array($extension, self::EXTENSIONS, true)) {
            return $this->normaliseExtension($extension);
        }

        if ($contents !== '' && function_exists('getimagesizefromstring')) {
            $info = @getimagesizefromstring($contents);

            if (is_array($info) && isset($info['mime'])) {
                return self::MIME_EXTENSIONS[strtolower($info['mime'])] ?? 'png';
            }
        }

        return 'png';
    }

    private function storeOnDisk(Generation $generation, string $contents, string $extension, array $meta): string
    {
        $path = self::DIRECTORY.'/'.$generation->uuid.'.'.$extension;
        $disk = Storage::disk($this->disk());

        if (! $disk->put($path, $contents)) {
            throw new RuntimeException('The generation image could not be stored.');
        }

        $previous = $generation->meta['image_path'] ?? null;

        if (is_string($previous) && ltrim($previous, '/') !== $path) {
            $previous = ltrim($previous, '/');

            if (str_starts_with($previous, self::DIRECTORY.'/')) {
                $disk->delete($previous);
            }
        }

        // A render restored to disk no longer needs its database copy.
        GenerationImage::query()->where('generation_id', $generation->id)->delete();

        $meta = array_merge($generation->meta ?? [], $meta, [
            'image_path' => $path,
            'image_storage' => 'disk',
            'image_mirrored_at' => now()->toIso8601String(),
        ]);

        $url = $this->publicUrl($path);

        $generation->forceFill([
            'image_url' => $url,
            'thumbnail_url' => $url,
            'meta' => $meta,
        ])->save();

        return $path;
    }

    private function storeInDatabase(Generation $generation, string $contents, string $extension, array $meta): string
    {
        $path = self::DIRECTORY.'/'.$generation->uuid.'.'.$extension;
        $mime = self::EXTENSION_MIMES[$extension] ?? 'image/png';
        $checksum = hash('sha256', $contents);

        GenerationImage::query()->updateOrCreate(
            ['generation_id' => $generation->id],
            [
                'mime' => $mime,
                'extension' => $extension,
                'bytes' => strlen($contents),
                'checksum' => $checksum,
                'data' => base64_encode($contents),
            ],
        );

        // Drop the old file so a stale copy cannot be served after a later
        // switch back to disk storage.
        $previous = $generation->meta['image_path'] ?? null;

        if (is_string($previous)) {
            $previous = ltrim($previous, '/');

            if (str_starts_with($previous, self::DIRECTORY.'/')) {
                Storage::disk($this->disk())->delete($previous);
            }
        }

        $meta = array_merge($generation->meta ?? [], $meta, [
            'image_path' => $path,
            'image_storage' => 'database',
            'image_mime' => $mime,
            'image_bytes' => strlen($contents),
            'image_checksum' => $checksum,
            'image_mirrored_at' => now()->toIso8601String(),
        ]);

        $url = self::MEDIA_PREFIX.$generation->uuid;

        $generation->forceFill([
            'image_url' => $url,
            'thumbnail_url' => $url,
            'meta' => $meta,
        ])->save();

        return $path;
    }

    private function publicUrl(string $path): string
    {
        $url = (string) Storage::disk($this->disk())->url($path);

        if ($url === '') {
            return '/storage/'.$path;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        if ($scheme !== 'http' && $scheme !== 'https') {
            return '/'.ltrim($url, '/');
        }

        $appUrl = rtrim((string) config('app.url'), '/');

        if ($appUrl !== '' && str_starts_with($url, $appUrl.'/')) {
            return (string) parse_url($url, PHP_URL_PATH);
        }

        return $url;
    }

    private function normaliseExtension(string $extension): string
    {
        $extension = strtolower($extension);

        return $extension === 'jpeg' ? 'jpg' : $extension;
    }

    private function isAppHost(string $host): bool
    {
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        return is_string($appHost) && $appHost !== '' && strcasecmp($host, $appHost) === 0;
    }

    private function isLocalDisk(): bool
    {
        return config('filesystems.disks.'.$this->disk().'.driver', 'local') === 'local';
    }
}
