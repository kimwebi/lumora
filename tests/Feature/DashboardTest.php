<?php

namespace Tests\Feature;

use App\Models\Generation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_dashboard_sends_recent_renders_as_a_plain_list(): void
    {
        $user = User::factory()->create(['credit_balance_micros' => 1_000_000]);

        $generations = Generation::factory()->succeeded()->count(2)->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('features/dashboard/pages/Dashboard')
                ->has('recent', 2)
                ->where('recent.0.uuid', $generations->last()->uuid)
                ->where('stats.total_generations', 2)
                ->where('stats.succeeded', 2));
    }

    #[Test]
    public function the_dashboard_hides_another_users_renders(): void
    {
        $user = User::factory()->create(['credit_balance_micros' => 1_000_000]);
        Generation::factory()->succeeded()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->has('recent', 0)
                ->where('stats.total_generations', 0));
    }
}
