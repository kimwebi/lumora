<?php

namespace App\Console\Commands;

use App\Models\Generation;
use App\Services\Generations\ImageCaptionStripper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class StripImageCaptionsCommand extends Command
{
    protected $signature = 'lumora:strip-image-captions';

    protected $description = 'Paint out provider captions on mirrored renders whose per-render preference (or the configured default) asks for it';

    public function handle(ImageCaptionStripper $stripper): int
    {
        $disk = Storage::disk((string) config('lumora.generation.image_disk', 'public'));
        $default = (bool) config('lumora.generation.strip_captions', false);
        $stripped = 0;
        $skipped = 0;

        Generation::withTrashed()
            ->where('status', Generation::STATUS_SUCCEEDED)
            ->whereNotNull('image_url')
            ->orderBy('id')
            ->chunkById(100, function ($generations) use ($disk, $stripper, $default, &$stripped, &$skipped): void {
                foreach ($generations as $generation) {
                    if (! (bool) ($generation->meta['remove_captions'] ?? $default)) {
                        $skipped++;

                        continue;
                    }

                    $path = $this->storedPath($generation->image_url);

                    if ($path === null || ! $disk->exists($path)) {
                        $skipped++;

                        continue;
                    }

                    $contents = (string) $disk->get($path);
                    $clean = $stripper->strip($contents);

                    if ($clean === $contents) {
                        $skipped++;

                        continue;
                    }

                    $disk->put($path, $clean);

                    $generation->forceFill([
                        'meta' => array_merge($generation->meta ?? [], [
                            'captions_stripped_at' => now()->toIso8601String(),
                        ]),
                    ])->save();

                    $stripped++;
                }
            });

        $this->info("Stripped captions from {$stripped} image(s). Skipped {$skipped}.");

        return self::SUCCESS;
    }

    /**
     * Turn a stored image URL back into a path on the configured disk.
     */
    private function storedPath(?string $url): ?string
    {
        if (! is_string($url) || ! str_starts_with($url, '/storage/')) {
            return null;
        }

        return substr($url, strlen('/storage/'));
    }
}
