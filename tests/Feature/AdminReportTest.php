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

class AdminReportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'credit_balance_micros' => 0,
        ]);
    }

    #[Test]
    public function guests_are_redirected_and_non_admins_are_forbidden(): void
    {
        $this->get('/admin/reports')->assertRedirect(route('login'));

        $user = User::factory()->create(['credit_balance_micros' => 100_000]);

        $this->actingAs($user)->get('/admin/reports')->assertForbidden();
        $this->actingAs($user)->post('/admin/reports/sync')->assertForbidden();
        $this->actingAs($user)->get('/admin/reports/export/pdf')->assertForbidden();
        $this->actingAs($user)->get('/admin/reports/export/xlsx')->assertForbidden();
        $this->actingAs($user)->get('/admin/reports/export/png')->assertForbidden();
    }

    #[Test]
    public function the_report_contains_daily_and_per_user_totals_with_provider_variance(): void
    {
        $this->withoutVite();

        $admin = $this->admin();
        $creator = User::factory()->create(['credit_balance_micros' => 40_000]);

        $succeeded = Generation::factory()->succeeded()->create([
            'user_id' => $creator->id,
            'provider_generation_id' => 'gen_report_1',
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

        ProviderUsageRecord::query()->create([
            'provider_usage_id' => 'usage_report_1',
            'provider_generation_id' => $succeeded->provider_generation_id,
            'generation_id' => $succeeded->id,
            'provider_cost_micros' => 18_000,
            'occurred_at' => now(),
            'raw' => [],
        ]);

        $this->actingAs($admin)
            ->get('/admin/reports?days=7')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('features/admin/pages/AdminReports')
                ->where('report.range_days', 7)
                ->where('report.totals.generations', 2)
                ->where('report.totals.succeeded', 1)
                ->where('report.totals.failed', 1)
                ->where('report.totals.charge_micros', 25_000)
                ->where('report.totals.provider_micros', 20_000)
                ->where('report.totals.profit_micros', 5_000)
                ->where('report.totals.provider_usage_micros', 18_000)
                ->where('report.totals.provider_usage_profit_micros', 7_000)
                ->where('report.totals.variance_micros', -2_000)
                ->where('report.totals.active_users', 1)
                ->where('report.reconciliation.records', 1)
                ->where('report.reconciliation.linked_records', 1)
                ->where('report.reconciliation.unlinked_records', 0)
                ->where('report.reconciliation.active_days', 1)
                ->has('report.daily', 7)
                ->has('report.users', 1)
                ->where('report.users.0.email', $creator->email)
                ->where('report.users.0.charge_micros', 25_000)
                ->where('report.users.0.provider_micros', 20_000)
                ->where('report.users.0.profit_micros', 5_000)
                ->where('report.users.0.usage_provider_micros', 18_000)
                ->where('report.users.0.variance_micros', -2_000)
            );
    }

    #[Test]
    public function per_user_provider_api_cost_is_allocated_from_the_daily_pool(): void
    {
        $this->withoutVite();

        $admin = $this->admin();

        $big = User::factory()->create(['credit_balance_micros' => 40_000]);
        $small = User::factory()->create(['credit_balance_micros' => 40_000]);

        Generation::factory()->succeeded()->create([
            'user_id' => $big->id,
            'actual_provider_cost_micros' => 30_000,
            'actual_user_charge_micros' => 40_000,
            'settled_at' => now(),
        ]);

        Generation::factory()->succeeded()->create([
            'user_id' => $small->id,
            'actual_provider_cost_micros' => 10_000,
            'actual_user_charge_micros' => 15_000,
            'settled_at' => now(),
        ]);

        ProviderUsageRecord::query()->create([
            'provider_usage_id' => 'usage_report_allocated',
            'provider_cost_micros' => 20_000,
            'occurred_at' => now(),
            'raw' => [],
        ]);

        $this->actingAs($admin)
            ->get('/admin/reports?days=7')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('report.totals.provider_usage_micros', 20_000)
                ->where('report.users.0.email', $big->email)
                ->where('report.users.0.usage_provider_micros', 15_000)
                ->where('report.users.0.variance_micros', -15_000)
                ->where('report.users.1.email', $small->email)
                ->where('report.users.1.usage_provider_micros', 5_000)
                ->where('report.users.1.variance_micros', -5_000)
            );
    }

    #[Test]
    public function the_report_flags_missing_provider_usage_instead_of_reporting_a_zero_variance(): void
    {
        $this->withoutVite();

        $admin = $this->admin();
        $creator = User::factory()->create(['credit_balance_micros' => 40_000]);

        Generation::factory()->succeeded()->create([
            'user_id' => $creator->id,
            'actual_provider_cost_micros' => 15_000,
            'actual_user_charge_micros' => 20_000,
            'settled_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/reports?days=14')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('report.totals.provider_usage_micros', null)
                ->where('report.totals.variance_micros', null)
                ->where('report.reconciliation.records', 0)
                ->where('report.daily.13.usage_provider_micros', null)
            );
    }

    #[Test]
    public function syncing_provider_usage_uses_the_selected_report_range(): void
    {
        config([
            'lumora.provider.api_key' => 'test-key',
            'lumora.provider.base_url' => 'https://pixelforge.test',
        ]);

        Http::fake(['*' => Http::response(['data' => []])]);

        $this->actingAs($this->admin())
            ->post('/admin/reports/sync', ['days' => 30])
            ->assertRedirect()
            ->assertSessionHas('success');

        Http::assertSent(function ($request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return str_contains($request->url(), '/v1/usage')
                && ($query['start_date'] ?? null) === now()->subDays(29)->toDateString()
                && ($query['end_date'] ?? null) === now()->toDateString();
        });
    }

    #[Test]
    public function the_report_downloads_pdf_excel_and_png_exports(): void
    {
        $admin = $this->admin();
        $creator = User::factory()->create(['credit_balance_micros' => 40_000]);

        Generation::factory()->succeeded()->create([
            'user_id' => $creator->id,
            'actual_provider_cost_micros' => 20_000,
            'actual_user_charge_micros' => 25_000,
            'settled_at' => now(),
        ]);

        $pdf = $this->actingAs($admin)->get('/admin/reports/export/pdf?days=7');
        $pdf->assertOk();
        $pdf->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->assertStringContainsString('.pdf"', (string) $pdf->headers->get('content-disposition'));

        $xlsx = $this->actingAs($admin)->get('/admin/reports/export/xlsx?days=7');
        $xlsx->assertOk();
        $xlsx->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );
        $this->assertStringStartsWith('PK', $xlsx->getContent());
        $this->assertStringContainsString('.xlsx"', (string) $xlsx->headers->get('content-disposition'));

        $png = $this->actingAs($admin)->get('/admin/reports/export/png?days=7');
        $png->assertOk();
        $png->assertHeader('content-type', 'image/png');
        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $png->getContent());
        $this->assertStringContainsString('.png"', (string) $png->headers->get('content-disposition'));
    }

    #[Test]
    public function unsupported_export_formats_are_not_routed(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/reports/export/csv')
            ->assertNotFound();
    }
}
