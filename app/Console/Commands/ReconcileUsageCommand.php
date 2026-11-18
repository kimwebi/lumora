<?php

namespace App\Console\Commands;

use App\Services\Generations\WebhookProcessor;
use App\Services\Pixelforge\PixelforgeException;
use App\Services\Pixelforge\UsageReconciler;
use Illuminate\Console\Command;

class ReconcileUsageCommand extends Command
{
    protected $signature = 'lumora:reconcile-usage {--days=2 : Window of usage to sync}';

    protected $description = 'Sync Pixelforge usage records and replay unmatched webhooks';

    public function handle(UsageReconciler $reconciler, WebhookProcessor $webhooks): int
    {
        $days = max(1, (int) $this->option('days'));

        $replayed = $webhooks->replayPending();
        if ($replayed > 0) {
            $this->info("Replayed {$replayed} pending webhook event(s).");
        }

        try {
            $result = $reconciler->sync(now()->subDays($days), now());
            $this->info("Usage synced: {$result['stored']} record(s), ".number_format($result['provider_micros'] / 1_000_000, 4).' USD provider cost.');

            if ($result['missing_generations'] > 0) {
                $this->warn("{$result['missing_generations']} usage record(s) could not be matched to a local generation.");
            }
        } catch (PixelforgeException $exception) {
            $this->error('Usage sync failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
