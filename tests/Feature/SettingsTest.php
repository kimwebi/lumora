<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_settings_page_shows_the_profile_and_stats(): void
    {
        $this->withoutVite();

        $user = User::factory()->create(['credit_balance_micros' => 100_000]);

        $this->actingAs($user)
            ->get('/settings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('features/settings/pages/Settings')
                ->where('profile.email', $user->email)
                ->where('stats.generations', 0)
            );
    }

    #[Test]
    public function users_can_update_their_profile(): void
    {
        $user = User::factory()->create(['credit_balance_micros' => 100_000]);

        $this->actingAs($user)
            ->put('/settings/profile', [
                'name' => 'New Name',
                'email' => 'new@example.com',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $user->refresh();

        $this->assertSame('New Name', $user->name);
        $this->assertSame('new@example.com', $user->email);
    }

    #[Test]
    public function users_can_change_their_password_with_the_current_one(): void
    {
        $user = User::factory()->create([
            'password' => 'old-password',
            'credit_balance_micros' => 100_000,
        ]);

        $this->actingAs($user)
            ->put('/settings/password', [
                'current_password' => 'old-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }

    #[Test]
    public function changing_the_password_requires_the_current_password(): void
    {
        $user = User::factory()->create([
            'password' => 'old-password',
            'credit_balance_micros' => 100_000,
        ]);

        $this->actingAs($user)
            ->from('/settings')
            ->put('/settings/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect('/settings')
            ->assertSessionHasErrors('current_password');
    }

    #[Test]
    public function deleting_an_account_requires_the_password_and_signs_the_user_out(): void
    {
        $user = User::factory()->create([
            'password' => 'secret-password',
            'credit_balance_micros' => 100_000,
        ]);

        $this->actingAs($user)
            ->delete('/settings/account', ['password' => 'secret-password'])
            ->assertRedirect('/')
            ->assertSessionHas('success');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
