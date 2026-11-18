<?php

namespace Tests\Feature;

use App\Models\Generation;
use App\Models\ProviderUsageRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'credit_balance_micros' => 1_000_000,
        ]);
    }

    #[Test]
    public function guests_are_redirected_and_non_admins_are_forbidden(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));

        $user = User::factory()->create(['credit_balance_micros' => 100_000]);

        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->actingAs($user)->post('/admin/reconcile')->assertForbidden();
    }

    #[Test]
    public function admins_see_aggregate_metrics(): void
    {
        $this->withoutVite();

        $admin = $this->admin();
        $creator = User::factory()->create(['credit_balance_micros' => 40_000]);

        Generation::factory()->succeeded()->create([
            'user_id' => $creator->id,
            'actual_provider_cost_micros' => 20_000,
            'actual_user_charge_micros' => 25_000,
            'settled_at' => now(),
        ]);

        Generation::factory()->failed()->create([
            'user_id' => $creator->id,
            'actual_provider_cost_micros' => 0,
            'actual_user_charge_micros' => 0,
            'settled_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin?days=7')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('features/admin/pages/AdminDashboard')
                ->where('metrics.range_days', 7)
                ->where('metrics.totals.generations', 2)
                ->where('metrics.totals.succeeded', 1)
                ->where('metrics.totals.failed', 1)
                ->where('metrics.totals.charge_micros', 25_000)
                ->where('metrics.totals.provider_micros', 20_000)
                ->where('metrics.totals.profit_micros', 5_000)
                ->where('metrics.totals.success_rate', 50)
                ->has('metrics.series', 7)
                ->has('metrics.per_user', 2)
                ->where('metrics.per_user.0.charge_micros', 25_000)
            );
    }

    #[Test]
    public function an_invalid_range_falls_back_to_fourteen_days(): void
    {
        $this->withoutVite();

        $this->actingAs($this->admin())
            ->get('/admin?days=999')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('metrics.range_days', 14));
    }

    #[Test]
    public function reconciling_pulls_provider_usage_and_links_generations(): void
    {
        config([
            'lumora.provider.api_key' => 'test-key',
            'lumora.provider.base_url' => 'https://pixelforge.test',
        ]);

        $admin = $this->admin();
        $creator = User::factory()->create(['credit_balance_micros' => 75_000]);

        $generation = Generation::factory()->create([
            'user_id' => $creator->id,
            'provider_generation_id' => 'gen_usage_1',
            'status' => Generation::STATUS_PROCESSING,
        ]);

        Http::fake([
            '*' => Http::response([
                'data' => [
                    [
                        'id' => 'use_1',
                        'generation_id' => 'gen_usage_1',
                        'cost_usd' => 0.015,
                        'created_at' => now()->toIso8601String(),
                    ],
                ],
            ]),
        ]);

        $this->actingAs($admin)
            ->post('/admin/reconcile')
            ->assertRedirect()
            ->assertSessionHas('success');

        $record = ProviderUsageRecord::query()->firstOrFail();

        $this->assertSame('use_1', $record->provider_usage_id);
        $this->assertSame($generation->id, $record->generation_id);
        $this->assertSame(15_000, $record->provider_cost_micros);
    }

    #[Test]
    public function reconciliation_reports_provider_failures_without_crashing(): void
    {
        config([
            'lumora.provider.api_key' => 'test-key',
            'lumora.provider.base_url' => 'https://pixelforge.test',
        ]);

        Http::fake([
            '*' => Http::response(['message' => 'Provider unavailable'], 503),
        ]);

        $this->actingAs($this->admin())
            ->post('/admin/reconcile')
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0, ProviderUsageRecord::query()->count());
    }
}
