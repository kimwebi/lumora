<?php

namespace Tests\Feature;

use App\Jobs\SubmitGenerationJob;
use App\Models\Generation;
use App\Models\User;
use App\Services\Billing\CreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function generationFor(User $user, array $attributes = []): Generation
    {
        $generation = Generation::factory()->create(array_merge([
            'user_id' => $user->id,
            'status' => Generation::STATUS_PENDING,
        ], $attributes));

        app(CreditService::class)->holdForGeneration(
            $user->fresh(),
            $generation,
        );

        return $generation->fresh();
    }

    #[Test]
    public function guests_cannot_reach_authenticated_pages(): void
    {
        foreach (['/dashboard', '/generate', '/creations', '/billing', '/settings'] as $path) {
            $this->get($path)->assertRedirect(route('login'));
        }
    }

    #[Test]
    public function users_cannot_view_or_delete_another_users_generation(): void
    {
        $owner = User::factory()->create(['credit_balance_micros' => 100_000]);
        $intruder = User::factory()->create(['credit_balance_micros' => 100_000]);

        $generation = $this->generationFor($owner);

        $this->actingAs($intruder)
            ->getJson(route('generations.status', $generation))
            ->assertForbidden();

        $this->actingAs($intruder)
            ->delete(route('generations.destroy', $generation))
            ->assertForbidden();

        $this->assertNotSoftDeleted('generations', ['id' => $generation->id]);
    }

    #[Test]
    public function owners_can_poll_their_generation_status(): void
    {
        $owner = User::factory()->create(['credit_balance_micros' => 100_000]);
        $generation = $this->generationFor($owner, ['status' => Generation::STATUS_PROCESSING]);

        $this->actingAs($owner)
            ->getJson(route('generations.status', $generation))
            ->assertOk()
            ->assertJsonPath('generation.uuid', $generation->uuid)
            ->assertJsonPath('generation.is_terminal', false)
            ->assertJsonPath('balance_micros', 75_000);
    }

    #[Test]
    public function in_progress_generations_cannot_be_deleted(): void
    {
        $owner = User::factory()->create(['credit_balance_micros' => 100_000]);
        $generation = $this->generationFor($owner);

        $this->actingAs($owner)
            ->from('/creations')
            ->delete(route('generations.destroy', $generation))
            ->assertRedirect('/creations')
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted('generations', ['id' => $generation->id]);
    }

    #[Test]
    public function finished_generations_can_be_soft_deleted_by_their_owner(): void
    {
        $owner = User::factory()->create(['credit_balance_micros' => 100_000]);
        $generation = $this->generationFor($owner, [
            'status' => Generation::STATUS_SUCCEEDED,
            'settled_at' => now(),
        ]);

        $this->actingAs($owner)
            ->from('/creations')
            ->delete(route('generations.destroy', $generation))
            ->assertRedirect('/creations')
            ->assertSessionHas('success');

        $this->assertSoftDeleted('generations', ['id' => $generation->id]);
    }

    #[Test]
    public function creating_a_generation_dispatches_a_unique_provider_job(): void
    {
        Queue::fake();

        $user = User::factory()->create(['credit_balance_micros' => 100_000]);

        $this->actingAs($user)
            ->post('/generations', [
                'prompt' => 'A glass forest under violet moonlight',
                'size' => '768x768',
                'quality' => 'hd',
            ])
            ->assertRedirect(route('generate'));

        Queue::assertPushed(SubmitGenerationJob::class, 1);
    }
}
