<?php

namespace Tests\Feature;

use App\Jobs\MirrorGenerationImage;
use App\Models\Generation;
use App\Services\Generations\ImageCaptionStripper;
use App\Services\Images\GenerationImageStore;
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

        $this->mirror($generation);

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
    public function provider_captions_are_painted_out_when_a_render_is_mirrored(): void
    {
        config([
            'lumora.generation.mirror_images' => true,
            'lumora.generation.strip_captions' => true,
        ]);
        Storage::fake('public');
        $captioned = $this->captionedImage();
        Http::fake([
            'images.example.com/*' => Http::response($captioned, 200, ['Content-Type' => 'image/png']),
        ]);

        $generation = Generation::factory()->create([
            'status' => Generation::STATUS_SUCCEEDED,
            'image_url' => 'https://images.example.com/gen_123.png?expires=1&signature=abc',
            'thumbnail_url' => 'https://images.example.com/gen_123.png?expires=1&signature=abc',
            'meta' => null,
        ]);

        $this->mirror($generation);

        $generation->refresh();
        $stored = Storage::disk('public')->get('generations/'.$generation->uuid.'.png');
        $image = imagecreatefromstring($stored);

        $this->assertNotSame($captioned, $stored);
        $this->assertLessThan(140, $this->brightness(imagecolorat($image, 21, 90)));
        $this->assertNotNull($generation->meta['captions_stripped_at']);
    }

    #[Test]
    public function provider_captions_are_kept_by_default(): void
    {
        config(['lumora.generation.mirror_images' => true]);
        Storage::fake('public');
        $captioned = $this->captionedImage();
        Http::fake([
            'images.example.com/*' => Http::response($captioned, 200, ['Content-Type' => 'image/png']),
        ]);

        $generation = Generation::factory()->create([
            'status' => Generation::STATUS_SUCCEEDED,
            'image_url' => 'https://images.example.com/gen_123.png?expires=1&signature=abc',
            'thumbnail_url' => 'https://images.example.com/gen_123.png?expires=1&signature=abc',
            'meta' => null,
        ]);

        $this->mirror($generation);

        $generation->refresh();

        $this->assertSame($captioned, Storage::disk('public')->get('generations/'.$generation->uuid.'.png'));
        $this->assertArrayNotHasKey('captions_stripped_at', $generation->meta ?? []);
    }

    #[Test]
    public function a_per_render_preference_can_remove_captions_even_when_the_global_default_is_off(): void
    {
        config([
            'lumora.generation.mirror_images' => true,
            'lumora.generation.strip_captions' => false,
        ]);
        Storage::fake('public');
        $captioned = $this->captionedImage();
        Http::fake([
            'images.example.com/*' => Http::response($captioned, 200, ['Content-Type' => 'image/png']),
        ]);

        $generation = Generation::factory()->create([
            'status' => Generation::STATUS_SUCCEEDED,
            'image_url' => 'https://images.example.com/gen_123.png?expires=1&signature=abc',
            'thumbnail_url' => 'https://images.example.com/gen_123.png?expires=1&signature=abc',
            'meta' => ['remove_captions' => true],
        ]);

        $this->mirror($generation);

        $generation->refresh();
        $stored = Storage::disk('public')->get('generations/'.$generation->uuid.'.png');
        $image = imagecreatefromstring($stored);

        $this->assertNotSame($captioned, $stored);
        $this->assertLessThan(140, $this->brightness(imagecolorat($image, 21, 90)));
        $this->assertNotNull($generation->meta['captions_stripped_at']);
    }

    #[Test]
    public function a_per_render_preference_can_keep_captions_even_when_the_global_default_is_on(): void
    {
        config([
            'lumora.generation.mirror_images' => true,
            'lumora.generation.strip_captions' => true,
        ]);
        Storage::fake('public');
        $captioned = $this->captionedImage();
        Http::fake([
            'images.example.com/*' => Http::response($captioned, 200, ['Content-Type' => 'image/png']),
        ]);

        $generation = Generation::factory()->create([
            'status' => Generation::STATUS_SUCCEEDED,
            'image_url' => 'https://images.example.com/gen_123.png?expires=1&signature=abc',
            'thumbnail_url' => 'https://images.example.com/gen_123.png?expires=1&signature=abc',
            'meta' => ['remove_captions' => false],
        ]);

        $this->mirror($generation);

        $generation->refresh();

        $this->assertSame($captioned, Storage::disk('public')->get('generations/'.$generation->uuid.'.png'));
        $this->assertArrayNotHasKey('captions_stripped_at', $generation->meta ?? []);
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

        $this->mirror($generation);

        $this->assertSame('/storage/generations/already-here.png', $generation->fresh()->image_url);
    }

    private function mirror(Generation $generation): void
    {
        (new MirrorGenerationImage($generation->id))->handle(
            app(ImageCaptionStripper::class),
            app(GenerationImageStore::class),
        );
    }

    private function captionedImage(): string
    {
        $image = imagecreatetruecolor(400, 300);
        $background = imagecolorallocate($image, 30, 20, 60);
        $ink = imagecolorallocate($image, 255, 255, 255);

        imagefilledrectangle($image, 0, 0, 399, 299, $background);
        imagestring($image, 5, 20, 48, 'PIXELFORGE', $ink);

        foreach ([20, 60, 100, 140] as $x) {
            imagefilledrectangle($image, $x, 82, $x + 2, 98, $ink);
        }

        ob_start();
        imagepng($image);
        $contents = (string) ob_get_clean();
        imagedestroy($image);

        return $contents;
    }

    private function brightness(int $color): int
    {
        return min(($color >> 16) & 0xFF, ($color >> 8) & 0xFF, $color & 0xFF);
    }
}
