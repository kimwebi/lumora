<?php

namespace App\Console\Commands;

use App\Models\Generation;
use App\Services\Images\GenerationImageStore;
use Illuminate\Console\Command;

class VerifyGenerationImagesCommand extends Command
{
    protected $signature = 'lumora:verify-images {--json : Output machine-readable JSON}';

    protected $description = 'Report completed renders whose stored image file is missing';

    public function handle(GenerationImageStore $store): int
    {
        $missing = $store->missing();

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'driver' => $store->driver(),
                'disk' => $store->disk(),
                'missing' => $missing->count(),
                'generations' => $missing->map(fn (Generation $generation): array => [
                    'uuid' => $generation->uuid,
                    'prompt' => $generation->prompt,
                    'filename' => $this->filename($store, $generation),
                    'has_provider_url' => filled($generation->meta['provider_image_url'] ?? null),
                    'created_at' => $generation->created_at?->toIso8601String(),
                ])->values()->all(),
            ], JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        if ($missing->isEmpty()) {
            $this->info('All completed render images are available ('.$this->storageLabel($store).').');

            return self::SUCCESS;
        }

        $this->warn($missing->count().' image(s) are missing from '.$this->storageLabel($store).'.');

        $this->table(
            ['UUID', 'Filename', 'Prompt', 'Created'],
            $missing->take(50)->map(fn (Generation $generation): array => [
                $generation->uuid,
                $this->filename($store, $generation),
                str($generation->prompt)->limit(48)->toString(),
                $generation->created_at?->toDateTimeString() ?? '—',
            ])->all(),
        );

        if ($missing->count() > 50) {
            $this->line('… and '.($missing->count() - 50).' more.');
        }

        $this->newLine();
        $this->line('Restore downloaded files with: php artisan lumora:restore-images <directory>');

        return self::SUCCESS;
    }

    private function filename(GenerationImageStore $store, Generation $generation): string
    {
        $path = $store->storedPath($generation);

        return $path !== null ? basename($path) : $generation->uuid.'.png';
    }

    private function storageLabel(GenerationImageStore $store): string
    {
        return $store->storesInDatabase() ? 'the database' : 'the '.$store->disk().' disk';
    }
}
