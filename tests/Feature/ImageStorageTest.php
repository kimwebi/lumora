<?php

namespace Tests\Feature;

use App\Jobs\MirrorGenerationImage;
use App\Models\Generation;
use App\Models\User;
use App\Services\Generations\ImageCaptionStripper;
use App\Services\Images\GenerationImageStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImageStorageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    #[Test]
    public function guests_are_redirected_and_non_admins_are_forbidden(): void
    {
        $this->get('/admin/images')->assertRedirect(route('login'));

        $user = User::factory()->create();
        $generation = Generation::factory()->succeeded()->create([
            'image_url' => '/storage/generations/missing.png',
        ]);

        $this->actingAs($user)->get('/admin/images')->assertForbidden();
        $this->actingAs($user)->post('/admin/images')->assertForbidden();
        $this->actingAs($user)->post("/admin/images/{$generation->uuid}/refetch")->assertForbidden();
    }

    #[Test]
    public function the_page_lists_completed_renders_with_missing_files(): void
    {
        $generation = Generation::factory()->succeeded()->create([
            'image_url' => '/storage/generations/missing.png',
        ]);

        Generation::factory()->succeeded()->create([
            'image_url' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe',
        ]);

        $this->actingAs($this->admin())
            ->get('/admin/images')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('features/admin/pages/AdminImages')
                ->where('storage.missing_count', 1)
                ->where('storage.stored_count', 1)
                ->where('storage.missing.0.uuid', $generation->uuid)
                ->where('storage.missing.0.filename', 'missing.png'));
    }

    #[Test]
    public function an_admin_can_restore_an_uploaded_image(): void
    {
        $uuid = (string) Str::uuid();

        $generation = Generation::factory()->succeeded()->create([
            'uuid' => $uuid,
            'image_url' => '/storage/generations/'.$uuid.'.png',
            'thumbnail_url' => '/storage/generations/'.$uuid.'.png',
            'meta' => null,
        ]);

        $file = UploadedFile::fake()->image($generation->uuid.'.png', 24, 24);

        $this->actingAs($this->admin())
            ->post('/admin/images', ['images' => [$file]])
            ->assertRedirect()
            ->assertSessionHas('success');

        $generation->refresh();

        Storage::disk('public')->assertExists('generations/'.$generation->uuid.'.png');
        $this->assertSame('/storage/generations/'.$generation->uuid.'.png', $generation->image_url);
        $this->assertSame('generations/'.$generation->uuid.'.png', $generation->meta['image_path']);
        $this->assertSame('upload', $generation->meta['image_restore_source']);
    }

    #[Test]
    public function uploads_that_do_not_match_a_completed_render_are_reported(): void
    {
        $file = UploadedFile::fake()->image('not-a-generation.png', 16, 16);

        $this->actingAs($this->admin())
            ->post('/admin/images', ['images' => [$file]])
            ->assertRedirect()
            ->assertSessionHas('warning');

        $this->assertSame([], Storage::disk('public')->allFiles('generations'));
    }

    #[Test]
    public function the_verify_command_reports_missing_images(): void
    {
        Generation::factory()->succeeded()->create([
            'image_url' => '/storage/generations/missing.png',
        ]);

        Artisan::call('lumora:verify-images', ['--json' => true]);
        $report = json_decode(Artisan::output(), true);

        $this->assertSame(1, $report['missing']);
        $this->assertSame('missing.png', $report['generations'][0]['filename']);
    }

    #[Test]
    public function the_restore_command_restores_matching_files_from_a_directory(): void
    {
        $generation = Generation::factory()->succeeded()->create([
            'image_url' => '/storage/generations/missing.png',
        ]);

        $directory = storage_path('framework/testing/restore-'.uniqid());
        File::ensureDirectoryExists($directory);
        File::put($directory.'/'.$generation->uuid.'.png', $this->png());

        try {
            $this->artisan('lumora:restore-images', ['path' => $directory])
                ->assertSuccessful();
        } finally {
            File::deleteDirectory($directory);
        }

        Storage::disk('public')->assertExists('generations/'.$generation->uuid.'.png');
        $this->assertSame(
            '/storage/generations/'.$generation->uuid.'.png',
            $generation->fresh()->image_url,
        );
    }

    #[Test]
    public function a_missing_file_is_redownloaded_from_the_provider_url_when_mirroring(): void
    {
        config(['lumora.generation.mirror_images' => true]);
        Http::fake([
            'images.example.com/*' => Http::response($this->png(), 200, ['Content-Type' => 'image/png']),
        ]);

        $generation = Generation::factory()->succeeded()->create([
            'uuid' => $uuid = (string) Str::uuid(),
            'image_url' => '/storage/generations/'.$uuid.'.png',
            'meta' => [
                'provider_image_url' => 'https://images.example.com/gen_123.png',
            ],
        ]);

        (new MirrorGenerationImage($generation->id))->handle(
            app(ImageCaptionStripper::class),
            app(GenerationImageStore::class),
        );

        Storage::disk('public')->assertExists('generations/'.$generation->uuid.'.png');
        $this->assertSame('https://images.example.com/gen_123.png', $generation->fresh()->meta['provider_image_url']);
    }

    #[Test]
    public function an_admin_can_redownload_an_image_from_pixelforge(): void
    {
        Http::fake([
            'images.example.com/*' => Http::response($this->png(), 200, ['Content-Type' => 'image/png']),
        ]);

        $generation = Generation::factory()->succeeded()->create([
            'image_url' => '/storage/generations/missing.png',
            'meta' => [
                'provider_image_url' => 'https://images.example.com/gen_123.png',
            ],
        ]);

        $this->actingAs($this->admin())
            ->post("/admin/images/{$generation->uuid}/refetch")
            ->assertRedirect()
            ->assertSessionHas('success');

        Storage::disk('public')->assertExists('generations/'.$generation->uuid.'.png');
        $this->assertSame('provider', $generation->fresh()->meta['image_restore_source']);
    }

    #[Test]
    public function the_database_driver_stores_images_and_serves_them_from_media_urls(): void
    {
        config(['lumora.generation.image_store' => 'database']);

        $generation = Generation::factory()->succeeded()->create([
            'image_url' => '/storage/generations/'.$this->uuid().'.png',
        ]);

        $png = $this->png();
        app(GenerationImageStore::class)->store($generation, $png, 'png');
        $generation->refresh();

        $this->assertSame('/media/generations/'.$generation->uuid, $generation->image_url);
        $this->assertSame('/media/generations/'.$generation->uuid, $generation->thumbnail_url);
        $this->assertSame('database', $generation->meta['image_storage']);

        $this->assertDatabaseHas('generation_images', [
            'generation_id' => $generation->id,
            'mime' => 'image/png',
        ]);

        $response = $this->get($generation->image_url)->assertOk();

        $this->assertSame('image/png', $response->headers->get('Content-Type'));
        $this->assertSame($png, $response->getContent());
        $this->assertStringContainsString('max-age=604800', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('public', (string) $response->headers->get('Cache-Control'));
    }

    #[Test]
    public function the_media_route_returns_the_etag_and_handles_conditional_requests(): void
    {
        config(['lumora.generation.image_store' => 'database']);

        $generation = Generation::factory()->succeeded()->create();
        app(GenerationImageStore::class)->store($generation, $this->png(), 'png');
        $generation->refresh();

        $etag = $this->get($generation->image_url)->headers->get('ETag');

        $this->assertNotNull($etag);

        $this->withHeader('If-None-Match', $etag)
            ->get($generation->image_url)
            ->assertStatus(304);
    }

    #[Test]
    public function the_media_route_returns_404_when_no_image_is_stored(): void
    {
        $generation = Generation::factory()->succeeded()->create([
            'image_url' => '/media/generations/'.$this->uuid(),
        ]);

        $this->get('/media/generations/'.$generation->uuid)->assertNotFound();
        $this->get('/media/generations/'.$this->uuid())->assertNotFound();
    }

    #[Test]
    public function the_database_driver_reports_renders_without_a_stored_copy(): void
    {
        config(['lumora.generation.image_store' => 'database']);

        $missing = Generation::factory()->succeeded()->create([
            'image_url' => '/storage/generations/'.$this->uuid().'.png',
        ]);

        Generation::factory()->succeeded()->create([
            'image_url' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe',
        ]);

        $this->actingAs($this->admin())
            ->get('/admin/images')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('storage.mode', 'database')
                ->where('storage.missing_count', 1)
                ->where('storage.missing.0.uuid', $missing->uuid));

        app(GenerationImageStore::class)->store($missing, $this->png(), 'png');

        $this->actingAs($this->admin())
            ->get('/admin/images')
            ->assertInertia(fn (Assert $page) => $page
                ->where('storage.missing_count', 0));
    }

    #[Test]
    public function an_uploaded_image_is_restored_into_the_database(): void
    {
        config(['lumora.generation.image_store' => 'database']);

        $uuid = $this->uuid();
        $generation = Generation::factory()->succeeded()->create([
            'uuid' => $uuid,
            'image_url' => '/storage/generations/'.$uuid.'.png',
        ]);

        $file = UploadedFile::fake()->image($uuid.'.png', 24, 24);

        $this->actingAs($this->admin())
            ->post('/admin/images', ['images' => [$file]])
            ->assertRedirect()
            ->assertSessionHas('success');

        $generation->refresh();

        $this->assertSame('/media/generations/'.$uuid, $generation->image_url);
        $this->assertDatabaseHas('generation_images', ['generation_id' => $generation->id]);
        $this->get($generation->image_url)->assertOk();
    }

    #[Test]
    public function mirroring_writes_to_the_database_when_it_is_the_configured_driver(): void
    {
        config([
            'lumora.generation.mirror_images' => true,
            'lumora.generation.image_store' => 'database',
        ]);
        Http::fake([
            'images.example.com/*' => Http::response($this->png(), 200, ['Content-Type' => 'image/png']),
        ]);

        $generation = Generation::factory()->succeeded()->create([
            'image_url' => 'https://images.example.com/gen_123.png',
        ]);

        (new MirrorGenerationImage($generation->id))->handle(
            app(ImageCaptionStripper::class),
            app(GenerationImageStore::class),
        );

        $generation->refresh();

        $this->assertSame('/media/generations/'.$generation->uuid, $generation->image_url);
        $this->assertDatabaseHas('generation_images', [
            'generation_id' => $generation->id,
            'extension' => 'png',
        ]);
        $this->assertTrue(app(GenerationImageStore::class)->exists($generation));
    }

    private function admin(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'credit_balance_micros' => 0,
        ]);
    }

    private function uuid(): string
    {
        return (string) Str::uuid();
    }

    private function png(): string
    {
        $image = imagecreatetruecolor(24, 24);
        ob_start();
        imagepng($image);
        $contents = (string) ob_get_clean();
        imagedestroy($image);

        return $contents;
    }
}
