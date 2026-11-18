<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CodeExplanationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guests_can_read_the_unlisted_reference(): void
    {
        $this->withoutVite();

        $response = $this->get('/code-explanation');

        $response->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertInertia(fn (Assert $page) => $page
                ->component('features/docs/pages/CodeExplanation')
                ->where('seo.title', 'Code Explanation — Lumora')
                ->where('seo.robots', 'noindex, nofollow')
                ->where('seo.json_ld', null)
            );
    }

    #[Test]
    public function signed_in_users_can_read_the_unlisted_reference(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/code-explanation')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('features/docs/pages/CodeExplanation')
            );
    }

    #[Test]
    public function the_reference_is_absent_from_the_sitemap(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertDontSee('/code-explanation', false);
    }

    #[Test]
    public function the_reference_is_absent_from_robots_txt(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertDontSee('code-explanation', false);
    }
}
