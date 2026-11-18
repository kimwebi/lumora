<?php

namespace App\Services\Generations;

use GdImage;

/**
 * Pixelforge renders are simulated placeholders: the provider draws the
 * generation id, quality and prompt onto every image. Those captions are kept by
 * default; when `lumora.generation.strip_captions` is enabled, mirrored files are
 * cleaned before they are stored so the studio and gallery show only the artwork.
 *
 * Caption ink is near-white on artwork that is much darker, so glyph strokes can
 * be found by luminance. Each stroke is replaced with a horizontal blend of the
 * background on either side of it, which keeps the gradient behind the text
 * intact. The PIXELFORGE watermark in the top-left corner is protected.
 */
class ImageCaptionStripper
{
    /**
     * Rows above this line only contain the provider watermark.
     */
    private const PROTECTED_TOP_ROWS = 78;

    /**
     * Solid caption ink is drawn in near-white.
     */
    private const MIN_GLYPH_LUMINANCE = 140;

    /**
     * Rows with at least this many near-white pixels belong to a caption.
     */
    private const MIN_CAPTION_PIXELS = 8;

    /**
     * Caption lines are at most this many rows apart.
     */
    private const MERGE_GAP_ROWS = 12;

    /**
     * Neutral pixels only, so saturated artwork highlights are left alone.
     */
    private const MAX_SATURATION = 60;

    /**
     * Antialiasing around a stroke is grown by this many pixels before the
     * stroke is replaced, so no ghost outline is left behind.
     */
    private const DILATION = 2;

    /**
     * A bright horizontal run longer than this is artwork, not a glyph stroke.
     */
    private const MAX_GLYPH_RUN = 120;

    /**
     * Caption stripping is a per-pixel pass; anything larger is left untouched
     * rather than holding a queue worker for seconds.
     */
    private const MAX_PIXELS = 12_000_000;

    public function strip(string $contents): string
    {
        if ($contents === '' || ! function_exists('imagecreatefromstring')) {
            return $contents;
        }

        $image = @imagecreatefromstring($contents);

        if (! $image instanceof GdImage) {
            return $contents;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if ($width * $height > self::MAX_PIXELS) {
            imagedestroy($image);

            return $contents;
        }

        if (! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        $changed = 0;

        foreach ($this->captionBands($image, $width, $height) as [$from, $to]) {
            $changed += $this->stripBand($image, $from, $to, $width);
        }

        if ($changed === 0) {
            imagedestroy($image);

            return $contents;
        }

        ob_start();
        imagepng($image);
        $stripped = (string) ob_get_clean();
        imagedestroy($image);

        return $stripped !== '' ? $stripped : $contents;
    }

    /**
     * Find the row ranges that hold caption lines, so artwork elsewhere in the
     * image is never touched.
     *
     * @return list<array{int, int}>
     */
    private function captionBands(GdImage $image, int $width, int $height): array
    {
        $bands = [];
        $start = null;
        $last = null;

        for ($y = min(self::PROTECTED_TOP_ROWS, $height); $y < $height; $y++) {
            $solid = 0;

            for ($x = 0; $x < $width; $x++) {
                if ($this->isSolidGlyph(imagecolorat($image, $x, $y))) {
                    $solid++;
                }
            }

            if ($solid < self::MIN_CAPTION_PIXELS) {
                continue;
            }

            if ($start !== null && $y - $last > self::MERGE_GAP_ROWS) {
                $bands[] = [$start, $last];
                $start = null;
            }

            $start ??= $y;
            $last = $y;
        }

        if ($start !== null && $last !== null) {
            $bands[] = [$start, $last];
        }

        return $bands;
    }

    /**
     * Replace every caption stroke inside one band of rows.
     *
     * @return int number of pixels that were replaced
     */
    private function stripBand(GdImage $image, int $from, int $to, int $width): int
    {
        $masks = [];

        for ($y = $from; $y <= $to; $y++) {
            $masks[$y] = $this->glyphMask($image, $y, $width);
        }

        $changed = 0;

        foreach ($masks as $y => $row) {
            $mask = $this->dilate($row, $masks[$y - 1] ?? null, $masks[$y + 1] ?? null, $width);

            if ($mask === null) {
                continue;
            }

            $changed += $this->eraseRow($image, $y, $width, $mask);
        }

        return $changed;
    }

    /**
     * One character per pixel: "1" where the pixel is solid caption ink.
     */
    private function glyphMask(GdImage $image, int $y, int $width): string
    {
        $mask = '';

        for ($x = 0; $x < $width; $x++) {
            $mask .= $this->isSolidGlyph(imagecolorat($image, $x, $y)) ? '1' : '0';
        }

        return $mask;
    }

    /**
     * Grow the stroke mask sideways and one row up and down so faint
     * antialiasing is removed with the glyph. Returns null for empty rows.
     */
    private function dilate(string $row, ?string $above, ?string $below, int $width): ?string
    {
        $mask = str_repeat('0', $width);
        $touched = false;

        for ($x = 0; $x < $width; $x++) {
            if ($row[$x] !== '1'
                && ($above[$x] ?? '0') !== '1'
                && ($below[$x] ?? '0') !== '1') {
                continue;
            }

            $from = max(0, $x - self::DILATION);
            $to = min($width - 1, $x + self::DILATION);

            for ($pixel = $from; $pixel <= $to; $pixel++) {
                if ($mask[$pixel] === '1') {
                    continue;
                }

                $mask[$pixel] = '1';
                $touched = true;
            }
        }

        return $touched ? $mask : null;
    }

    /**
     * Replace every masked run on one row with the background around it.
     */
    private function eraseRow(GdImage $image, int $y, int $width, string $mask): int
    {
        $colors = [];

        for ($x = 0; $x < $width; $x++) {
            $colors[$x] = imagecolorat($image, $x, $y);
        }

        $changed = 0;
        $x = 0;

        while ($x < $width) {
            if ($mask[$x] !== '1') {
                $x++;

                continue;
            }

            $start = $x;

            while ($x < $width && $mask[$x] === '1') {
                $x++;
            }

            $end = $x - 1;
            $length = $end - $start + 1;

            // Long bright runs are artwork (a highlight, not a font stroke).
            if ($length > self::MAX_GLYPH_RUN) {
                continue;
            }

            $left = $start > 0 ? $colors[$start - 1] : null;
            $right = $end < $width - 1 ? $colors[$end + 1] : null;

            if ($left === null && $right === null) {
                continue;
            }

            for ($pixel = $start; $pixel <= $end; $pixel++) {
                $ratio = ($pixel - $start + 1) / ($length + 1);

                imagesetpixel($image, $pixel, $y, $this->blend($left ?? $right, $right ?? $left, $ratio));
                $changed++;
            }
        }

        return $changed;
    }

    private function isSolidGlyph(int $color): bool
    {
        $red = ($color >> 16) & 0xFF;
        $green = ($color >> 8) & 0xFF;
        $blue = $color & 0xFF;

        return min($red, $green, $blue) >= self::MIN_GLYPH_LUMINANCE
            && max($red, $green, $blue) - min($red, $green, $blue) <= self::MAX_SATURATION;
    }

    /**
     * Linear interpolation between the background on both sides of a stroke.
     */
    private function blend(?int $from, ?int $to, float $ratio): int
    {
        $from ??= $to;
        $to ??= $from;

        $channel = static fn (int $color, int $shift): int => ($color >> $shift) & 0xFF;

        $mix = static fn (int $start, int $end): int => (int) round($start + ($end - $start) * $ratio);

        return ($mix($channel($from, 16), $channel($to, 16)) << 16)
            | ($mix($channel($from, 8), $channel($to, 8)) << 8)
            | $mix($channel($from, 0), $channel($to, 0));
    }
}
