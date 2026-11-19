<?php

namespace App\Console\Commands;

use App\Models\Generation;
use App\Services\Images\GenerationImageStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use SplFileInfo;
use Symfony\Component\Finder\SplFileInfo as FinderFile;
use Throwable;

class RestoreGenerationImagesCommand extends Command
{
    private const EXTENSIONS = ['png', 'jpg', 'jpeg', 'webp', 'gif'];

    protected $signature = 'lumora:restore-images
        {path : Directory of images named after the generation UUID}
        {--dry-run : List what would be restored without writing files}';

    protected $description = 'Restore generation images from a directory of recovered files';

    public function handle(GenerationImageStore $store): int
    {
        $path = (string) $this->argument('path');

        if (! is_dir($path)) {
            $this->error("Directory not found: {$path}");

            return self::FAILURE;
        }

        $files = collect(File::allFiles($path))
            ->filter(fn (SplFileInfo|FinderFile $file): bool => in_array(strtolower($file->getExtension()), self::EXTENSIONS, true))
            ->values();

        if ($files->isEmpty()) {
            $this->warn('No PNG, JPG, WEBP or GIF files were found in '.$path.'.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $restored = 0;
        $skipped = [];
        $unmatched = [];
        $failed = [];

        foreach ($files as $file) {
            $name = $file->getFilename();
            $generation = $store->generationForFilename($name);

            if (! $generation) {
                $unmatched[] = $name;

                continue;
            }

            if ($generation->status !== Generation::STATUS_SUCCEEDED) {
                $skipped[] = $name.' ('.$generation->status.')';

                continue;
            }

            if ($dryRun) {
                $restored++;
                $this->line("Would restore {$name} → {$generation->uuid}");

                continue;
            }

            try {
                $store->restoreFile($generation, $file->getPathname());
                $restored++;
                $this->info("Restored {$name} → {$generation->uuid}");
            } catch (Throwable $exception) {
                $failed[] = $name.': '.$exception->getMessage();
            }
        }

        $this->newLine();
        $this->line(($dryRun ? 'Would restore ' : 'Restored ').$restored.' of '.$files->count().' file(s).');

        if ($skipped !== []) {
            $this->warn('Skipped '.count($skipped).' file(s) without a completed render: '.implode(', ', array_slice($skipped, 0, 5)));
        }

        if ($unmatched !== []) {
            $this->warn('No matching generation for '.count($unmatched).' file(s): '.implode(', ', array_slice($unmatched, 0, 5)));
        }

        if ($failed !== []) {
            $this->error('Failed to restore '.count($failed).' file(s):');
            foreach (array_slice($failed, 0, 5) as $failure) {
                $this->line('  '.$failure);
            }

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
