<?php

namespace Tests\Feature;

use App\Jobs\SubmitGenerationJob;
use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\User;
use App\Services\Billing\CreditService;
use App\Services\Generations\GenerationFinalizer;
use App\Services\Generations\GenerationService;
use App\Services\Pixelforge\PricingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GenerationFlowTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(int $balanceMicros = 1_000_000): User
    {
        return User::factory()->create(['credit_balance_micros' => $balanceMicros]);
    }

    private function makeGeneration(User $user, array $attributes = []): Generation
    {
        return app(GenerationService::class)->create($user, array_merge([
            'prompt' => 'A quiet lighthouse at dusk, painted in soft oils',
            'size' => '1024x1024',
            'quality' => 'medium',
            'reference_images' => [],
        ], $attributes));
    }

    #[Test]
    public function creating_a_generation_holds_the_estimated_charge_and_dispatches_the_job(): void
    {
        Queue::fake();

        $user = $this->makeUser(100_000);

        $response = $this->actingAs($user)->post('/generations', [
            'prompt' => '  A quiet lighthouse at dusk  ',
            'size' => '1024x1024',
            'quality' => 'medium',
        ]);

        $response->assertRedirect(route('generate'));

        $generation = Generation::query()->firstOrFail();
        $estimate = app(PricingCalculator::class)->estimate('1024x1024', 'medium', 1, 'A quiet lighthouse at dusk');

        $this->assertSame(Generation::STATUS_PENDING, $generation->status);
        $this->assertSame('A quiet lighthouse at dusk', $generation->prompt);
        $this->assertSame($estimate->providerMicros, $generation->estimated_provider_cost_micros);
        $this->assertSame($estimate->totalMicros, $generation->estimated_user_charge_micros);
        $this->assertSame(2_500, $generation->markup_basis_points);
        $this->assertFalse($generation->meta['remove_captions']);

        $this->assertSame(100_000 - $estimate->totalMicros, $user->fresh()->credit_balance_micros);

        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'generation_id' => $generation->id,
            'type' => CreditTransaction::TYPE_HOLD,
            'amount_micros' => -$estimate->totalMicros,
            'balance_after_micros' => 100_000 - $estimate->totalMicros,
        ]);

        Queue::assertPushed(SubmitGenerationJob::class);
    }

    #[Test]
    public function the_caption_preference_is_stored_with_the_generation(): void
    {
        Queue::fake();

        $user = $this->makeUser(1_000_000);

        $this->actingAs($user)->post('/generations', [
            'prompt' => 'A quiet lighthouse at dusk',
            'size' => '1024x1024',
            'quality' => 'medium',
            'remove_captions' => true,
        ])->assertRedirect(route('generate'));

        $this->assertTrue(Generation::query()->firstOrFail()->meta['remove_captions']);
    }

    #[Test]
    public function prompts_used_by_provider_test_modes_are_stored_verbatim(): void
    {
        Queue::fake();

        $user = $this->makeUser(1_000_000);

        foreach (['[fail] a broken render', '[slow] a render that takes its time', '[duplicate] a webhook test'] as $prompt) {
            $this->actingAs($user)->post('/generations', [
                'prompt' => $prompt,
                'size' => '1024x1024',
                'quality' => 'medium',
            ])->assertRedirect(route('generate'));
        }

        foreach (['[fail] a broken render', '[slow] a render that takes its time', '[duplicate] a webhook test'] as $prompt) {
            $this->assertDatabaseHas('generations', ['prompt' => $prompt]);
        }
    }

    #[Test]
    public function users_without_enough_credits_cannot_generate(): void
    {
        $user = $this->makeUser(10_000);

        try {
            $this->makeGeneration($user);
            $this->fail('A generation should not be created without enough credits.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('credits', $exception->errors());
        }

        $this->assertDatabaseCount('generations', 0);
        $this->assertDatabaseCount('credit_transactions', 0);
        $this->assertSame(10_000, $user->fresh()->credit_balance_micros);
    }

    #[Test]
    public function users_cannot_exceed_the_active_generation_limit(): void
    {
        config(['lumora.generation.max_active_per_user' => 2]);

        $user = $this->makeUser(1_000_000);

        $this->makeGeneration($user);
        $this->makeGeneration($user);

        try {
            $this->makeGeneration($user);
            $this->fail('The active generation limit should be enforced.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('prompt', $exception->errors());
        }

        $this->assertSame(2, $user->generations()->processing()->count());
    }

    #[Test]
    public function a_failed_generation_is_refunded_in_full(): void
    {
        $user = $this->makeUser(100_000);
        $generation = $this->makeGeneration($user);

        $this->assertSame(100_000 - $generation->estimated_user_charge_micros, $user->fresh()->credit_balance_micros);

        $applied = app(GenerationFinalizer::class)->fail(
            $generation,
            'provider_failed',
            'The provider could not generate this image.',
            [],
            'test',
        );

        $this->assertTrue($applied);
        $this->assertSame(100_000, $user->fresh()->credit_balance_micros);

        $generation->refresh();
        $this->assertSame(Generation::STATUS_FAILED, $generation->status);
        $this->assertSame(0, $generation->actual_user_charge_micros);
        $this->assertSame(0, $generation->charged_micros);
        $this->assertNotNull($generation->settled_at);

        $this->assertDatabaseHas('credit_transactions', [
            'generation_id' => $generation->id,
            'type' => CreditTransaction::TYPE_REFUND,
            'amount_micros' => $generation->estimated_user_charge_micros,
        ]);
    }

    #[Test]
    public function a_successful_generation_refunds_the_unused_estimate(): void
    {
        $user = $this->makeUser(100_000);
        $generation = $this->makeGeneration($user);

        $applied = app(GenerationFinalizer::class)->succeed($generation, [
            'status' => 'succeeded',
            'data' => ['image_url' => 'https://cdn.example.com/image.png'],
            'usage' => ['cost_usd' => 0.01],
        ], 'test');

        $this->assertTrue($applied);

        $generation->refresh();
        $this->assertSame(Generation::STATUS_SUCCEEDED, $generation->status);
        $this->assertSame(10_000, $generation->actual_provider_cost_micros);
        $this->assertSame(12_500, $generation->actual_user_charge_micros);
        $this->assertSame(12_500, $generation->charged_micros);
        $this->assertSame('https://cdn.example.com/image.png', $generation->image_url);

        // 25,000 was held, the real charge was 12,500, so 12,500 comes back.
        $this->assertSame(87_500, $user->fresh()->credit_balance_micros);
    }

    #[Test]
    public function a_successful_generation_can_charge_more_than_the_estimate(): void
    {
        $user = $this->makeUser(100_000);
        $generation = $this->makeGeneration($user);

        app(GenerationFinalizer::class)->succeed($generation, [
            'status' => 'succeeded',
            'data' => ['images' => [['url' => 'https://cdn.example.com/image.png']]],
            'usage' => ['cost_usd' => 0.04],
        ], 'test');

        $generation->refresh();

        $this->assertSame(40_000, $generation->actual_provider_cost_micros);
        $this->assertSame(50_000, $generation->actual_user_charge_micros);
        $this->assertSame(50_000, $user->fresh()->credit_balance_micros);
    }

    #[Test]
    public function settlement_is_idempotent_and_never_double_charges(): void
    {
        $user = $this->makeUser(100_000);
        $generation = $this->makeGeneration($user);
        $finalizer = app(GenerationFinalizer::class);

        $payload = [
            'status' => 'succeeded',
            'data' => ['image_url' => 'https://cdn.example.com/image.png'],
            'usage' => ['cost_usd' => 0.01],
        ];

        $this->assertTrue($finalizer->succeed($generation, $payload, 'test'));
        $this->assertFalse($finalizer->succeed($generation->fresh(), $payload, 'test'));
        $this->assertFalse($finalizer->fail($generation->fresh(), 'late', 'late failure', [], 'test'));

        $this->assertSame(1, CreditTransaction::query()
            ->where('generation_id', $generation->id)
            ->where('type', CreditTransaction::TYPE_SETTLEMENT)
            ->count());

        $this->assertSame(87_500, $user->fresh()->credit_balance_micros);
        $this->assertSame(Generation::STATUS_SUCCEEDED, $generation->fresh()->status);
    }

    #[Test]
    public function older_provider_events_are_ignored_for_unsettled_generations(): void
    {
        $user = $this->makeUser(100_000);
        $generation = $this->makeGeneration($user);

        $generation->forceFill(['last_provider_event_at' => now()])->save();

        $applied = app(GenerationFinalizer::class)->fail(
            $generation,
            'stale_failure',
            'An older event should not win.',
            [],
            'test',
            now()->subMinute(),
        );

        $this->assertFalse($applied);
        $this->assertSame(Generation::STATUS_PENDING, $generation->fresh()->status);
        $this->assertSame(100_000 - $generation->estimated_user_charge_micros, $user->fresh()->credit_balance_micros);
    }

    #[Test]
    public function stale_generations_expire_and_are_refunded(): void
    {
        $user = $this->makeUser(100_000);
        $generation = $this->makeGeneration($user);

        DB::table('generations')
            ->where('id', $generation->id)
            ->update(['updated_at' => now()->subMinutes(30)]);

        $expired = app(GenerationService::class)->expireStale();

        $this->assertSame(1, $expired);
        $this->assertSame(Generation::STATUS_FAILED, $generation->fresh()->status);
        $this->assertSame('generation_timeout', $generation->fresh()->error_code);
        $this->assertSame(100_000, $user->fresh()->credit_balance_micros);
    }

    #[Test]
    public function the_wallet_ledger_always_matches_the_balance(): void
    {
        $user = $this->makeUser(100_000);
        $generation = $this->makeGeneration($user);

        app(GenerationFinalizer::class)->succeed($generation, [
            'status' => 'succeeded',
            'data' => ['image_url' => 'https://cdn.example.com/image.png'],
            'usage' => ['cost_usd' => 0.01],
        ], 'test');

        $ledgerTotal = (int) CreditTransaction::query()
            ->where('user_id', $user->id)
            ->sum('amount_micros');

        // The seeded opening balance (100,000) plus every ledger movement must
        // equal the wallet balance.
        $this->assertSame($user->fresh()->credit_balance_micros, 100_000 + $ledgerTotal);
    }

    #[Test]
    public function the_credit_service_refuses_to_hold_more_than_the_balance(): void
    {
        $user = $this->makeUser(1_000);
        $generation = Generation::factory()->create([
            'user_id' => $user->id,
            'estimated_user_charge_micros' => 25_000,
            'estimated_provider_cost_micros' => 20_000,
        ]);

        $this->expectException(\RuntimeException::class);

        app(CreditService::class)->holdForGeneration($user, $generation);
    }
}
