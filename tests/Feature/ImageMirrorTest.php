<?php

namespace Tests\Feature;

use App\Jobs\MirrorGenerationImage;
use App\Models\Generation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImageMirrorTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_succeeded_render_is_copied_off_the_expiring_provider_url(): void
    {
        config(['lumora.generation.mirror_images' => true]);
        Storage::fake('public');
        Http::fake([
            'images.example.com/*' => Http::response('fake-png-bytes', 200, ['Content-Type' => 'image/png']),
        ]);

        $generation = Generation::factory()->create([
            'status' => Generation::STATUS_SUCCEEDED,
            'image_url' => 'https://images.example.com/gen_123.png?expires=1&signature=abc',
            'thumbnail_url' => 'https://images.example.com/gen_123.png?expires=1&signature=abc',
            'meta' => null,
        ]);

        (new MirrorGenerationImage($generation->id))->handle();

        $generation->refresh();
        $path = 'generations/'.$generation->uuid.'.png';

        Storage::disk('public')->assertExists($path);
        $this->assertSame('fake-png-bytes', Storage::disk('public')->get($path));
        $this->assertSame('/storage/'.$path, $generation->image_url);
        $this->assertSame('/storage/'.$path, $generation->thumbnail_url);
        $this->assertSame(
            'https://images.example.com/gen_123.png?expires=1&signature=abc',
            $generation->meta['provider_image_url'],
        );
    }

    #[Test]
    public function an_image_that_is_already_stored_is_left_alone(): void
    {
        config(['lumora.generation.mirror_images' => true]);
        Storage::fake('public');
        Http::preventStrayRequests();

        $generation = Generation::factory()->create([
            'status' => Generation::STATUS_SUCCEEDED,
            'image_url' => '/storage/generations/already-here.png',
            'thumbnail_url' => '/storage/generations/already-here.png',
        ]);

        (new MirrorGenerationImage($generation->id))->handle();

        $this->assertSame('/storage/generations/already-here.png', $generation->fresh()->image_url);
    }
}
