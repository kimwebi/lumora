<?php

namespace Tests\Feature;

use App\Models\ProviderUsageRecord;
use App\Services\Pixelforge\UsageReconciler;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UsageReconcilerTest extends TestCase
{
    use RefreshDatabase;

    private function configureProvider(): void
    {
        config([
            'lumora.provider.api_key' => 'test-key',
            'lumora.provider.base_url' => 'https://pixelforge.test',
        ]);
    }

    private function dailyUsage(string $date, string $cost): array
    {
        return [
            'date' => $date,
            'generations' => 1,
            'input_text_tokens' => 19,
            'input_image_tokens' => 0,
            'output_tokens' => 280,
            'reasoning_tokens' => 451,
            'cost_usd' => $cost,
        ];
    }

    #[Test]
    public function daily_usage_aggregates_are_upserted_instead_of_duplicated(): void
    {
        $this->configureProvider();

        $date = now()->toDateString();

        Http::fake([
            '*' => Http::sequence()
                ->push(['data' => [$this->dailyUsage($date, '0.010000000')]])
                ->push(['data' => [$this->dailyUsage($date, '0.025000000')]]),
        ]);

        $reconciler = app(UsageReconciler::class);
        $from = CarbonImmutable::now()->startOfDay();
        $to = CarbonImmutable::now()->endOfDay();

        $reconciler->sync($from, $to);
        $reconciler->sync($from, $to);

        $records = ProviderUsageRecord::query()->get();

        $this->assertCount(1, $records);
        $this->assertSame(25_000, $records->first()->provider_cost_micros);
        $this->assertSame('usage_'.sha1($date), $records->first()->provider_usage_id);
    }

    #[Test]
    public function previously_hashed_daily_rows_for_the_same_day_are_collapsed(): void
    {
        $this->configureProvider();

        $date = now()->toDateString();

        foreach (['usage_old_one', 'usage_old_two'] as $id) {
            ProviderUsageRecord::query()->create([
                'provider_usage_id' => $id,
                'provider_cost_micros' => 5_000,
                'occurred_at' => $date,
                'raw' => [],
            ]);
        }

        Http::fake([
            '*' => Http::response(['data' => [$this->dailyUsage($date, '0.017000000')]]),
        ]);

        app(UsageReconciler::class)->sync(
            CarbonImmutable::now()->startOfDay(),
            CarbonImmutable::now()->endOfDay(),
        );

        $records = ProviderUsageRecord::query()->get();

        $this->assertCount(1, $records);
        $this->assertSame(17_000, $records->first()->provider_cost_micros);
        $this->assertSame('usage_'.sha1($date), $records->first()->provider_usage_id);
    }
}
