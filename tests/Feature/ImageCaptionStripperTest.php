<?php

namespace Tests\Feature;

use App\Services\Generations\ImageCaptionStripper;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImageCaptionStripperTest extends TestCase
{
    #[Test]
    public function caption_ink_is_painted_out_while_the_watermark_and_artwork_survive(): void
    {
        $contents = $this->captionedImage();

        $stripped = (new ImageCaptionStripper)->strip($contents);
        $image = imagecreatefromstring($stripped);

        $this->assertNotSame($contents, $stripped);
        $this->assertSame(400, imagesx($image));
        $this->assertSame(300, imagesy($image));

        // The narrow glyph strokes are replaced with the surrounding background.
        foreach ([21, 61, 101, 141] as $x) {
            $this->assertLessThan(140, $this->brightness(imagecolorat($image, $x, 90)), "Caption stroke at x={$x} was not removed.");
        }

        // The PIXELFORGE watermark in the protected top rows is untouched.
        $watermarkPixels = 0;

        for ($y = 44; $y <= 70; $y++) {
            for ($x = 20; $x < 200; $x++) {
                if ($this->brightness(imagecolorat($image, $x, $y)) >= 200) {
                    $watermarkPixels++;
                }
            }
        }

        $this->assertGreaterThan(0, $watermarkPixels, 'The provider watermark was removed.');

        // A wide bright shape is artwork, not a font stroke, and must survive.
        $this->assertSame(255, $this->brightness(imagecolorat($image, 220, 160)));
    }

    #[Test]
    public function an_image_without_captions_is_returned_byte_for_byte(): void
    {
        $image = imagecreatetruecolor(120, 80);
        imagefilledrectangle($image, 0, 0, 119, 79, imagecolorallocate($image, 30, 20, 60));

        ob_start();
        imagepng($image);
        $contents = (string) ob_get_clean();
        imagedestroy($image);

        $this->assertSame($contents, (new ImageCaptionStripper)->strip($contents));
    }

    #[Test]
    public function bytes_that_are_not_an_image_are_returned_unchanged(): void
    {
        $this->assertSame('fake-png-bytes', (new ImageCaptionStripper)->strip('fake-png-bytes'));
    }

    private function captionedImage(): string
    {
        $image = imagecreatetruecolor(400, 300);
        $background = imagecolorallocate($image, 30, 20, 60);
        $ink = imagecolorallocate($image, 255, 255, 255);

        imagefilledrectangle($image, 0, 0, 399, 299, $background);
        imagestring($image, 5, 20, 48, 'PIXELFORGE', $ink);

        // Four thin caption strokes, like the letters of a prompt line.
        foreach ([20, 60, 100, 140] as $x) {
            imagefilledrectangle($image, $x, 82, $x + 2, 98, $ink);
        }

        // A bright artwork highlight, much wider than a glyph stroke.
        imagefilledrectangle($image, 100, 150, 350, 170, $ink);

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
