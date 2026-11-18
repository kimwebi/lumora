<?php

namespace Tests\Feature;

use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\Generations\GenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'whsec_lumora_test';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'lumora.provider.webhook_secret' => self::SECRET,
            'lumora.provider.signature.scheme' => 'timestamped',
            'lumora.provider.signature.tolerance_seconds' => 300,
        ]);
    }

    private function makeUser(int $balanceMicros = 100_000): User
    {
        return User::factory()->create(['credit_balance_micros' => $balanceMicros]);
    }

    private function makeGeneration(User $user, array $attributes = [], ?string $providerId = null): Generation
    {
        $generation = app(GenerationService::class)->create($user, array_merge([
            'prompt' => 'A quiet lighthouse at dusk',
            'size' => '1024x1024',
            'quality' => 'standard',
            'reference_images' => [],
        ], $attributes));

        $generation->forceFill([
            'provider_generation_id' => $providerId ?? 'gen_'.$generation->id,
        ])->save();

        return $generation->fresh();
    }

    private function signedPost(array $payload, ?string $secret = null, ?int $timestamp = null): TestResponse
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $timestamp ??= now()->timestamp;
        $secret ??= self::SECRET;
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, $secret);

        return $this->call('POST', '/webhooks/pixelforge', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_PIXELFORGE_SIGNATURE' => "t={$timestamp},v1={$signature}",
        ], $body);
    }

    private function successPayload(Generation $generation, array $overrides = []): array
    {
        return array_replace_recursive([
            'id' => 'evt_success_1',
            'type' => 'generation.succeeded',
            'status' => 'succeeded',
            'created_at' => now()->timestamp,
            'data' => [
                'id' => 'gen_123',
                'metadata' => ['generation_uuid' => $generation->uuid],
                'images' => [['url' => 'https://cdn.example.com/rendered.png']],
            ],
            'usage' => ['cost_usd' => 0.01],
        ], $overrides);
    }

    #[Test]
    public function a_signed_success_webhook_settles_the_generation(): void
    {
        $user = $this->makeUser();
        $generation = $this->makeGeneration($user);

        $response = $this->signedPost($this->successPayload($generation));

        $response->assertOk()
            ->assertJsonPath('received', true)
            ->assertJsonPath('outcome', 'processed')
            ->assertJsonPath('status', 'succeeded');

        $generation->refresh();

        $this->assertSame(Generation::STATUS_SUCCEEDED, $generation->status);
        $this->assertSame('https://cdn.example.com/rendered.png', $generation->image_url);
        $this->assertSame(10_000, $generation->actual_provider_cost_micros);
        $this->assertSame(12_500, $generation->actual_user_charge_micros);
        $this->assertSame(87_500, $user->fresh()->credit_balance_micros);

        $this->assertDatabaseHas('credit_transactions', [
            'generation_id' => $generation->id,
            'type' => CreditTransaction::TYPE_SETTLEMENT,
            'amount_micros' => 12_500,
        ]);
    }

    #[Test]
    public function duplicate_webhooks_are_idempotent(): void
    {
        $user = $this->makeUser();
        $generation = $this->makeGeneration($user);
        $payload = $this->successPayload($generation);

        $this->signedPost($payload)->assertOk()->assertJsonPath('outcome', 'processed');
        $this->signedPost($payload)->assertOk()->assertJsonPath('outcome', 'duplicate');

        $this->assertSame(1, CreditTransaction::query()
            ->where('generation_id', $generation->id)
            ->where('type', CreditTransaction::TYPE_SETTLEMENT)
            ->count());

        $this->assertSame(87_500, $user->fresh()->credit_balance_micros);
        $this->assertSame(1, WebhookEvent::query()->count());
    }

    #[Test]
    public function a_failure_webhook_refunds_the_held_credits(): void
    {
        $user = $this->makeUser();
        $generation = $this->makeGeneration($user);

        $response = $this->signedPost([
            'id' => 'evt_fail_1',
            'type' => 'generation.failed',
            'status' => 'failed',
            'created_at' => now()->timestamp,
            'data' => [
                'id' => 'gen_123',
                'metadata' => ['generation_uuid' => $generation->uuid],
            ],
            'error' => ['code' => 'content_policy', 'message' => 'Prompt rejected by the provider.'],
        ]);

        $response->assertOk()->assertJsonPath('status', 'failed');

        $generation->refresh();

        $this->assertSame(Generation::STATUS_FAILED, $generation->status);
        $this->assertSame('content_policy', $generation->error_code);
        $this->assertSame(0, $generation->actual_user_charge_micros);
        $this->assertSame(100_000, $user->fresh()->credit_balance_micros);

        $this->assertDatabaseHas('credit_transactions', [
            'generation_id' => $generation->id,
            'type' => CreditTransaction::TYPE_REFUND,
            'amount_micros' => 25_000,
        ]);
    }

    #[Test]
    public function late_out_of_order_events_do_not_change_a_settled_generation(): void
    {
        $user = $this->makeUser();
        $generation = $this->makeGeneration($user);

        $this->signedPost($this->successPayload($generation))->assertOk();

        $response = $this->signedPost([
            'id' => 'evt_late_fail',
            'type' => 'generation.failed',
            'status' => 'failed',
            'created_at' => now()->subMinutes(5)->timestamp,
            'data' => [
                'id' => 'gen_123',
                'metadata' => ['generation_uuid' => $generation->uuid],
            ],
        ]);

        $response->assertOk()->assertJsonPath('outcome', 'ignored');

        $generation->refresh();

        $this->assertSame(Generation::STATUS_SUCCEEDED, $generation->status);
        $this->assertSame(87_500, $user->fresh()->credit_balance_micros);
        $this->assertSame(0, CreditTransaction::query()
            ->where('generation_id', $generation->id)
            ->where('type', CreditTransaction::TYPE_REFUND)
            ->count());
    }

    #[Test]
    public function an_invalid_signature_is_rejected_and_nothing_is_settled(): void
    {
        $user = $this->makeUser();
        $generation = $this->makeGeneration($user);

        $response = $this->signedPost($this->successPayload($generation), 'wrong-secret');

        $response->assertStatus(401)->assertJsonPath('message', 'Invalid signature.');

        $generation->refresh();

        $this->assertSame(Generation::STATUS_PENDING, $generation->status);
        $this->assertSame(75_000, $user->fresh()->credit_balance_micros);
        $this->assertSame(0, WebhookEvent::query()->count());
    }

    #[Test]
    public function a_stale_timestamp_is_rejected(): void
    {
        $user = $this->makeUser();
        $generation = $this->makeGeneration($user);

        $response = $this->signedPost(
            $this->successPayload($generation),
            null,
            now()->subMinutes(10)->timestamp,
        );

        $response->assertStatus(401);
        $this->assertSame(Generation::STATUS_PENDING, $generation->fresh()->status);
    }

    #[Test]
    public function a_webhook_without_a_configured_secret_is_rejected(): void
    {
        config(['lumora.provider.webhook_secret' => null]);

        $user = $this->makeUser();
        $generation = $this->makeGeneration($user);

        $this->signedPost($this->successPayload($generation))->assertStatus(401);
        $this->assertSame(Generation::STATUS_PENDING, $generation->fresh()->status);
    }

    #[Test]
    public function events_for_unknown_generations_are_stored_for_replay(): void
    {
        $payload = [
            'id' => 'evt_orphan',
            'type' => 'generation.succeeded',
            'status' => 'succeeded',
            'created_at' => now()->timestamp,
            'data' => [
                'id' => 'gen_unknown',
                'metadata' => ['generation_uuid' => '00000000-0000-4000-8000-000000000000'],
            ],
        ];

        $response = $this->signedPost($payload);

        $response->assertOk()->assertJsonPath('outcome', 'unmatched');

        $event = WebhookEvent::query()->firstOrFail();

        $this->assertFalse($event->processed);
        $this->assertSame('generation_not_found', $event->error);
        $this->assertSame('evt_orphan', $event->provider_event_id);
    }

    #[Test]
    public function duplicate_prompts_are_kept_separate_but_only_settle_once_per_event(): void
    {
        $user = $this->makeUser(200_000);

        $first = $this->makeGeneration($user, ['prompt' => '[duplicate] same prompt']);
        $second = $this->makeGeneration($user, ['prompt' => '[duplicate] same prompt']);

        $this->assertNotSame($first->uuid, $second->uuid);
        $this->assertNotSame($first->id, $second->id);

        $this->signedPost($this->successPayload($first, [
            'id' => 'evt_dup_1',
            'data' => ['id' => 'gen_first'],
        ]))->assertOk()->assertJsonPath('outcome', 'processed');

        $this->signedPost($this->successPayload($first, [
            'id' => 'evt_dup_1',
            'data' => ['id' => 'gen_first'],
        ]))->assertOk()->assertJsonPath('outcome', 'duplicate');

        $this->assertSame(1, CreditTransaction::query()
            ->where('generation_id', $first->id)
            ->where('type', CreditTransaction::TYPE_SETTLEMENT)
            ->count());

        $this->assertSame(Generation::STATUS_PENDING, $second->fresh()->status);
    }
}
