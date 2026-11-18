<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DocumentationTest extends TestCase
{
    #[Test]
    public function guests_can_read_the_user_guide(): void
    {
        $this->withoutVite();

        $this->get('/guide')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('features/docs/pages/UserGuide')
                ->where('seo.title', 'User Guide — Lumora')
                ->where('seo.robots', 'index, follow, max-image-preview:large')
            );
    }

    #[Test]
    public function guests_can_read_the_developer_guide(): void
    {
        $this->withoutVite();

        $this->get('/developer-guide')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('features/docs/pages/DeveloperGuide')
                ->where('seo.title', 'Developer Guide — Lumora')
                ->where('seo.robots', 'index, follow, max-image-preview:large')
            );
    }

    #[Test]
    public function the_spaced_developer_guide_url_redirects_to_the_canonical_page(): void
    {
        $this->get('/developer guide')->assertRedirect('/developer-guide');
    }

    #[Test]
    public function documentation_pages_are_listed_in_the_sitemap(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertSee('/guide', false);
        $response->assertSee('/developer-guide', false);
        $response->assertSee('/terms', false);
        $response->assertSee('/privacy', false);
    }
}
