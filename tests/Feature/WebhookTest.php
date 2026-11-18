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
            'quality' => 'medium',
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
            'object' => 'event',
            'type' => 'generation.succeeded',
            'created_at' => now()->toIso8601String(),
            'data' => [
                'object' => [
                    'id' => 'gen_123',
                    'object' => 'generation',
                    'status' => 'succeeded',
                    'metadata' => ['generation_uuid' => $generation->uuid],
                    'image_url' => 'https://cdn.example.com/rendered.png',
                    'usage' => [
                        'input_text_tokens' => 10,
                        'input_image_tokens' => 0,
                        'output_tokens' => 1_100,
                        'reasoning_tokens' => 200,
                        'total_tokens' => 1_310,
                        'cost_usd' => 0.01,
                    ],
                ],
            ],
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
            'amount_micros' => $generation->estimated_user_charge_micros - 12_500,
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
            'object' => 'event',
            'type' => 'generation.failed',
            'created_at' => now()->toIso8601String(),
            'data' => [
                'object' => [
                    'id' => 'gen_123',
                    'object' => 'generation',
                    'status' => 'failed',
                    'metadata' => ['generation_uuid' => $generation->uuid],
                    'error' => ['code' => 'content_policy', 'message' => 'Prompt rejected by the provider.'],
                ],
            ],
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
            'amount_micros' => $generation->estimated_user_charge_micros,
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
            'object' => 'event',
            'type' => 'generation.failed',
            'created_at' => now()->subMinutes(5)->toIso8601String(),
            'data' => [
                'object' => [
                    'id' => 'gen_123',
                    'object' => 'generation',
                    'status' => 'failed',
                    'metadata' => ['generation_uuid' => $generation->uuid],
                ],
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
        $this->assertSame(100_000 - $generation->estimated_user_charge_micros, $user->fresh()->credit_balance_micros);
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
            'object' => 'event',
            'type' => 'generation.succeeded',
            'created_at' => now()->toIso8601String(),
            'data' => [
                'object' => [
                    'id' => 'gen_unknown',
                    'object' => 'generation',
                    'status' => 'succeeded',
                    'metadata' => ['generation_uuid' => '00000000-0000-4000-8000-000000000000'],
                ],
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
    public function provider_ping_events_are_acknowledged_without_replay_noise(): void
    {
        $payload = [
            'id' => 'evt_ping_1',
            'object' => 'event',
            'type' => 'ping',
            'created_at' => now()->toIso8601String(),
            'data' => ['object' => ['object' => 'ping', 'message' => 'Hello from Pixelforge.']],
        ];

        $this->signedPost($payload)
            ->assertOk()
            ->assertJsonPath('outcome', 'ignored');

        $event = WebhookEvent::query()->firstOrFail();

        $this->assertTrue($event->processed);
        $this->assertTrue($event->ignored);
        $this->assertNull($event->error);
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

    #[Test]
    public function a_signature_in_a_differently_named_header_settles_the_generation(): void
    {
        $user = $this->makeUser();
        $generation = $this->makeGeneration($user);
        $payload = $this->successPayload($generation);
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $timestamp = now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, self::SECRET);

        $response = $this->call('POST', '/webhooks/pixelforge', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_WEBHOOK_SIGNATURE' => "t={$timestamp},v1={$signature}",
        ], $body);

        $response->assertOk()->assertJsonPath('outcome', 'processed');
        $this->assertSame(Generation::STATUS_SUCCEEDED, $generation->fresh()->status);
    }

    #[Test]
    public function a_raw_body_signature_without_a_timestamp_settles_the_generation(): void
    {
        $user = $this->makeUser();
        $generation = $this->makeGeneration($user);
        $payload = $this->successPayload($generation);
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', $body, self::SECRET);

        $response = $this->call('POST', '/webhooks/pixelforge', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_SIGNATURE' => $signature,
        ], $body);

        $response->assertOk()->assertJsonPath('outcome', 'processed');
        $this->assertSame(Generation::STATUS_SUCCEEDED, $generation->fresh()->status);
    }
}
