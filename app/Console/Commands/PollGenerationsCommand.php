<?php

namespace App\Console\Commands;

use App\Jobs\PollGenerationJob;
use App\Models\Generation;
use Illuminate\Console\Command;

class PollGenerationsCommand extends Command
{
    protected $signature = 'lumora:poll-generations';

    protected $description = 'Queue status polls for in-progress generations (webhook fallback)';

    public function handle(): int
    {
        $dispatched = 0;

        Generation::query()
            ->processing()
            ->whereNotNull('provider_generation_id')
            ->where('updated_at', '<', now()->subSeconds(8))
            ->orderBy('id')
            ->limit(100)
            ->get()
            ->each(function (Generation $generation) use (&$dispatched) {
                PollGenerationJob::dispatch($generation->id);
                $dispatched++;
            });

        $this->info("Queued {$dispatched} generation poll(s).");

        return self::SUCCESS;
    }
}
