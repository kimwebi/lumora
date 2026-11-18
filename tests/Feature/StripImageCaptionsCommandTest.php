<?php

namespace Tests\Feature;

use App\Models\Generation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StripImageCaptionsCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function images_mirrored_before_caption_stripping_are_cleaned_up(): void
    {
        config([
            'lumora.generation.image_disk' => 'public',
            'lumora.generation.strip_captions' => true,
        ]);
        Storage::fake('public');

        $generation = Generation::factory()->create([
            'status' => Generation::STATUS_SUCCEEDED,
            'image_url' => '/storage/generations/pending.png',
            'thumbnail_url' => '/storage/generations/pending.png',
            'meta' => null,
        ]);

        $path = 'generations/'.$generation->uuid.'.png';
        $generation->forceFill(['image_url' => '/storage/'.$path, 'thumbnail_url' => '/storage/'.$path])->save();
        Storage::disk('public')->put($path, $this->captionedImage());

        $this->artisan('lumora:strip-image-captions')
            ->expectsOutputToContain('Stripped captions from 1 image(s)')
            ->assertSuccessful();

        $image = imagecreatefromstring(Storage::disk('public')->get($path));
        $color = imagecolorat($image, 21, 90);
        $brightness = min(($color >> 16) & 0xFF, ($color >> 8) & 0xFF, $color & 0xFF);

        $this->assertLessThan(140, $brightness);
        $this->assertNotNull($generation->fresh()->meta['captions_stripped_at']);
    }

    #[Test]
    public function generations_without_a_mirrored_file_are_skipped(): void
    {
        config(['lumora.generation.image_disk' => 'public']);
        Storage::fake('public');

        Generation::factory()->create([
            'status' => Generation::STATUS_SUCCEEDED,
            'image_url' => 'https://images.example.com/gen_123.png',
            'thumbnail_url' => 'https://images.example.com/gen_123.png',
        ]);

        $this->artisan('lumora:strip-image-captions')
            ->expectsOutputToContain('Stripped captions from 0 image(s). Skipped 1.')
            ->assertSuccessful();
    }

    #[Test]
    public function renders_that_opted_in_are_cleaned_up_even_when_the_default_is_off(): void
    {
        config(['lumora.generation.image_disk' => 'public']);
        Storage::fake('public');

        $generation = Generation::factory()->create([
            'status' => Generation::STATUS_SUCCEEDED,
            'meta' => ['remove_captions' => true],
        ]);

        $path = 'generations/'.$generation->uuid.'.png';
        $generation->forceFill(['image_url' => '/storage/'.$path, 'thumbnail_url' => '/storage/'.$path])->save();
        Storage::disk('public')->put($path, $this->captionedImage());

        $this->artisan('lumora:strip-image-captions')
            ->expectsOutputToContain('Stripped captions from 1 image(s)')
            ->assertSuccessful();

        $this->assertNotNull($generation->fresh()->meta['captions_stripped_at']);
    }

    #[Test]
    public function renders_that_explicitly_keep_their_caption_are_skipped(): void
    {
        config(['lumora.generation.image_disk' => 'public']);
        Storage::fake('public');

        $generation = Generation::factory()->create([
            'status' => Generation::STATUS_SUCCEEDED,
            'meta' => ['remove_captions' => false],
        ]);

        $path = 'generations/'.$generation->uuid.'.png';
        $generation->forceFill(['image_url' => '/storage/'.$path, 'thumbnail_url' => '/storage/'.$path])->save();
        $captioned = $this->captionedImage();
        Storage::disk('public')->put($path, $captioned);

        $this->artisan('lumora:strip-image-captions')
            ->expectsOutputToContain('Stripped captions from 0 image(s). Skipped 1.')
            ->assertSuccessful();

        $this->assertSame($captioned, Storage::disk('public')->get($path));
    }

    private function captionedImage(): string
    {
        $image = imagecreatetruecolor(400, 300);
        imagefilledrectangle($image, 0, 0, 399, 299, imagecolorallocate($image, 30, 20, 60));
        $ink = imagecolorallocate($image, 255, 255, 255);

        foreach ([20, 60, 100, 140] as $x) {
            imagefilledrectangle($image, $x, 82, $x + 2, 98, $ink);
        }

        ob_start();
        imagepng($image);
        $contents = (string) ob_get_clean();
        imagedestroy($image);

        return $contents;
    }
}
