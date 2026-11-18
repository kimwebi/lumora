<?php

namespace Tests\Feature;

use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_billing_page_lists_packages_and_the_ledger(): void
    {
        $this->withoutVite();

        $user = User::factory()->create(['credit_balance_micros' => 250_000]);

        CreditTransaction::query()->create([
            'user_id' => $user->id,
            'type' => CreditTransaction::TYPE_BONUS,
            'amount_micros' => 250_000,
            'balance_after_micros' => 250_000,
            'description' => 'Welcome credits from Lumora',
        ]);

        $this->actingAs($user)
            ->get('/billing')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('features/billing/pages/Billing')
                ->where('summary.balance_micros', 250_000)
                ->has('packages', 3)
                ->has('transactions.data', 1)
                ->where('creditMicros', 10_000)
            );
    }

    #[Test]
    public function buying_a_package_credits_the_wallet(): void
    {
        $user = User::factory()->create(['credit_balance_micros' => 0]);

        $response = $this->actingAs($user)->post('/billing/purchase', [
            'package' => 'studio',
            'purchase_token' => 'token-abc-123',
        ]);

        $response->assertRedirect()->assertSessionHas('success');

        $this->assertSame(12_000_000, $user->fresh()->credit_balance_micros);

        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'type' => CreditTransaction::TYPE_PURCHASE,
            'amount_micros' => 12_000_000,
            'reference' => 'purchase_token-abc-123',
        ]);
    }

    #[Test]
    public function the_same_purchase_token_never_credits_twice(): void
    {
        $user = User::factory()->create(['credit_balance_micros' => 0]);

        $payload = ['package' => 'starter', 'purchase_token' => 'token-idempotent'];

        $this->actingAs($user)->post('/billing/purchase', $payload)->assertRedirect();
        $this->actingAs($user)->post('/billing/purchase', $payload)->assertRedirect();

        $this->assertSame(5_000_000, $user->fresh()->credit_balance_micros);
        $this->assertSame(1, CreditTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', CreditTransaction::TYPE_PURCHASE)
            ->count());
    }

    #[Test]
    public function invalid_packages_and_tokens_are_rejected(): void
    {
        $user = User::factory()->create(['credit_balance_micros' => 0]);

        $this->actingAs($user)
            ->from('/billing')
            ->post('/billing/purchase', ['package' => 'mega', 'purchase_token' => 'token-1'])
            ->assertRedirect('/billing')
            ->assertSessionHasErrors('package');

        $this->actingAs($user)
            ->from('/billing')
            ->post('/billing/purchase', ['package' => 'starter', 'purchase_token' => 'not a token!'])
            ->assertRedirect('/billing')
            ->assertSessionHasErrors('purchase_token');

        $this->assertSame(0, $user->fresh()->credit_balance_micros);
    }
}
