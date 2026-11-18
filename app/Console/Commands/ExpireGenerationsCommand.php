<?php

namespace App\Console\Commands;

use App\Services\Generations\GenerationService;
use Illuminate\Console\Command;

class ExpireGenerationsCommand extends Command
{
    protected $signature = 'lumora:expire-generations';

    protected $description = 'Fail and refund generations stuck beyond the configured timeout';

    public function handle(GenerationService $generations): int
    {
        $expired = $generations->expireStale();

        $this->info("Expired {$expired} stale generation(s).");

        return self::SUCCESS;
    }
}
