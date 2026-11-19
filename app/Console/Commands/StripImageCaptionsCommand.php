<?php

namespace App\Console\Commands;

use App\Models\Generation;
use App\Services\Generations\ImageCaptionStripper;
use App\Services\Images\GenerationImageStore;
use Illuminate\Console\Command;

class StripImageCaptionsCommand extends Command
{
    protected $signature = 'lumora:strip-image-captions';

    protected $description = 'Paint out provider captions on mirrored renders whose per-render preference (or the configured default) asks for it';

    public function handle(ImageCaptionStripper $stripper, GenerationImageStore $store): int
    {
        $default = (bool) config('lumora.generation.strip_captions', false);
        $stripped = 0;
        $skipped = 0;

        Generation::withTrashed()
            ->where('status', Generation::STATUS_SUCCEEDED)
            ->whereNotNull('image_url')
            ->orderBy('id')
            ->chunkById(100, function ($generations) use ($store, $stripper, $default, &$stripped, &$skipped): void {
                foreach ($generations as $generation) {
                    if (! (bool) ($generation->meta['remove_captions'] ?? $default)) {
                        $skipped++;

                        continue;
                    }

                    $contents = $store->contents($generation);

                    if ($contents === null) {
                        $skipped++;

                        continue;
                    }

                    $clean = $stripper->strip($contents);

                    if ($clean === $contents) {
                        $skipped++;

                        continue;
                    }

                    $extension = $store->extension(
                        $generation->meta['image_mime'] ?? null,
                        $generation->meta['image_path'] ?? $generation->image_url,
                        $clean,
                    );

                    $store->store($generation, $clean, $extension, [
                        'captions_stripped_at' => now()->toIso8601String(),
                    ]);

                    $stripped++;
                }
            });

        $this->info("Stripped captions from {$stripped} image(s). Skipped {$skipped}.");

        return self::SUCCESS;
    }
}
