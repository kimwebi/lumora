<?php

namespace Tests\Feature;

use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function registering_creates_a_wallet_with_the_welcome_bonus(): void
    {
        $this->withoutVite();

        $response = $this->post('/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
            'terms' => 'on',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'ada@example.com')->firstOrFail();

        $this->assertSame(5_000_000, $user->credit_balance_micros);

        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'type' => CreditTransaction::TYPE_BONUS,
            'amount_micros' => 5_000_000,
            'balance_after_micros' => 5_000_000,
            'reference' => 'welcome:'.$user->id,
        ]);
    }

    #[Test]
    public function registration_requires_accepting_the_terms(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('terms');
        $this->assertDatabaseMissing('users', ['email' => 'ada@example.com']);
    }

    #[Test]
    public function registration_rejects_duplicate_emails(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->from('/register')->post('/register', [
            'name' => 'Ada Lovelace',
            'email' => 'taken@example.com',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
            'terms' => 'on',
        ]);

        $response->assertSessionHasErrors('email');
    }

    #[Test]
    public function users_can_sign_in_and_out(): void
    {
        $this->withoutVite();

        $user = User::factory()->create([
            'email' => 'grace@example.com',
            'password' => 'secret-password',
            'credit_balance_micros' => 100_000,
        ]);

        $response = $this->post('/login', [
            'email' => 'grace@example.com',
            'password' => 'secret-password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }
}
