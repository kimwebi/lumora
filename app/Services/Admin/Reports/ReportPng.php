<?php

namespace App\Services\Admin\Reports;

use App\Support\Money;
use Carbon\CarbonImmutable;
use GdImage;
use RuntimeException;

/**
 * Renders the operator report as a print-friendly PNG using GD.
 */
class ReportPng
{
    private const WIDTH = 1600;

    private const MARGIN = 64;

    private const ROW_HEIGHT = 32;

    private const MAX_HEIGHT = 9000;

    private const INK = [24, 24, 27];

    private const SUBTLE = [113, 113, 122];

    private const LINE = [228, 228, 231];

    private const SURFACE = [248, 250, 252];

    private const BRAND = [91, 33, 182];

    private const BRAND_DARK = [30, 27, 75];

    private const SUCCESS = [5, 150, 105];

    private const DANGER = [220, 38, 38];

    private const PENDING = [217, 119, 6];

    private GdImage $image;

    private int $height = 0;

    private int $y = 0;

    private string $font = '';

    /** @var array<string, int> */
    private array $palette = [];

    /**
     * @param  array<string, mixed>  $report
     */
    public function render(array $report): string
    {
        $this->font = $this->fontPath();

        $dailyRows = count($report['daily']);
        $visibleUsers = $this->visibleUserCount($report);
        $this->height = $this->estimateHeight($dailyRows, $visibleUsers);

        $image = imagecreatetruecolor(self::WIDTH, $this->height);

        if ($image === false) {
            throw new RuntimeException('Unable to create the report image.');
        }

        $this->image = $image;
        imageantialias($this->image, true);
        $this->palette = [];

        $this->header($report);
        $this->summary($report);
        $this->reconciliation($report);
        $this->daily($report);
        $this->users($report, $visibleUsers);
        $this->footer($report);

        ob_start();
        imagepng($this->image, null, 6);
        $binary = ob_get_clean();
        imagedestroy($this->image);

        if (! is_string($binary)) {
            throw new RuntimeException('Unable to encode the report image.');
        }

        return $binary;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function header(array $report): void
    {
        $this->fillRect(0, 0, self::WIDTH, 150, self::BRAND_DARK);

        $this->text(self::MARGIN, 45, 'LUMORA', 15, true, [196, 181, 253]);
        $this->text(self::MARGIN, 96, 'Operator report', 38, true, [255, 255, 255]);
        $this->text(self::MARGIN, 126, 'Customer charges, provider costs and profit', 17, false, [196, 181, 253]);

        $this->text(
            self::WIDTH - self::MARGIN,
            55,
            CarbonImmutable::parse($report['from'])->format('M j, Y').' - '.CarbonImmutable::parse($report['to'])->format('M j, Y'),
            15,
            true,
            [255, 255, 255],
            'right',
        );
        $this->text(self::WIDTH - self::MARGIN, 79, $report['range_days'].' day range', 13, false, [196, 181, 253], 'right');
        $this->text(self::WIDTH - self::MARGIN, 103, 'Generated '.$this->timestamp($report['generated_at'] ?? null), 12, false, [196, 181, 253], 'right');

        $this->y = 190;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function summary(array $report): void
    {
        $totals = $report['totals'];
        $reconciliation = $report['reconciliation'];
        $usageRecords = (int) ($reconciliation['records'] ?? 0);

        $cards = [
            [
                'Customer charges',
                $this->money($totals['charge_micros']),
                $this->credits($totals['charge_micros']).' · '.number_format($totals['generations']).' renders',
                self::INK,
            ],
            [
                'Provider cost · ours',
                $this->money($totals['provider_micros']),
                'Settled provider records',
                self::INK,
            ],
            [
                'Provider cost · API',
                $usageRecords > 0 ? $this->money($totals['provider_usage_micros']) : 'Not synced',
                $usageRecords > 0 ? 'GET /v1/usage · '.number_format($usageRecords).' records' : 'Run Sync provider usage',
                $usageRecords > 0 ? self::INK : self::PENDING,
            ],
            [
                'Variance · API minus ours',
                $totals['variance_micros'] === null ? 'Not synced' : $this->signedMoney($totals['variance_micros']),
                $totals['variance_micros'] === null ? 'Waiting for usage data' : ($totals['variance_micros'] === 0 ? 'Balanced' : 'Review required'),
                $totals['variance_micros'] === null ? self::PENDING : ($totals['variance_micros'] === 0 ? self::SUCCESS : self::DANGER),
            ],
            [
                'Gross profit · ours',
                $this->money($totals['profit_micros']),
                number_format($totals['margin_percent'], 1).'% margin',
                self::INK,
            ],
            [
                'Gross profit · API',
                $usageRecords > 0 ? $this->money($totals['provider_usage_profit_micros']) : 'Not synced',
                'Uses provider API costs',
                $usageRecords > 0 ? self::INK : self::PENDING,
            ],
        ];

        $gap = 16;
        $width = (int) ((self::WIDTH - self::MARGIN * 2 - $gap * 2) / 3);
        $height = 82;

        foreach ($cards as $index => $card) {
            $column = $index % 3;
            $row = intdiv($index, 3);
            $x = self::MARGIN + $column * ($width + $gap);
            $top = $this->y + $row * ($height + $gap);

            $this->fillRect($x, $top, $width, $height, self::SURFACE);
            $this->strokeRect($x, $top, $width, $height, self::LINE);

            $this->text($x + 16, $top + 22, strtoupper($card[0]), 10, true, self::SUBTLE);
            $this->text($x + 16, $top + 54, $card[1], 23, true, $card[3]);
            $this->text($x + 16, $top + 72, $card[2], 10, false, self::SUBTLE);
        }

        $this->y += 2 * $height + $gap + 34;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function reconciliation(array $report): void
    {
        $reconciliation = $report['reconciliation'];
        $records = (int) ($reconciliation['records'] ?? 0);
        $variance = $report['totals']['variance_micros'];
        $balanced = $variance === 0;

        $this->fillRect(self::MARGIN, $this->y, self::WIDTH - self::MARGIN * 2, 58, [245, 243, 255]);
        $this->strokeRect(self::MARGIN, $this->y, self::WIDTH - self::MARGIN * 2, 58, [221, 214, 254]);

        $this->text(self::MARGIN + 16, $this->y + 22, 'PROVIDER RECONCILIATION', 10, true, self::BRAND);
        $this->text(
            self::MARGIN + 16,
            $this->y + 44,
            $records > 0
                ? number_format($records).' usage record(s) · '.number_format((int) ($reconciliation['linked_records'] ?? 0)).' linked · '
                    .number_format((int) ($reconciliation['active_days'] ?? 0)).' day(s) with spend · last sync '
                    .$this->timestamp($reconciliation['last_synced_at'] ?? null)
                : 'No provider usage synced for this range. Use Sync provider usage before reconciling.',
            11,
            false,
            self::SUBTLE,
        );

        $this->statusPill(
            self::WIDTH - self::MARGIN - 190,
            $this->y + 19,
            $records === 0 ? 'Not synced' : ($balanced ? 'Balanced' : 'Review'),
            $records === 0 ? self::PENDING : ($balanced ? self::SUCCESS : self::DANGER),
        );

        $this->y += 82;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function daily(array $report): void
    {
        $this->sectionTitle('Daily breakdown', $this->y);
        $this->y += 34;

        $widths = [260, 110, 180, 190, 190, 160, 190, 192];
        $headers = ['Date', 'Renders', 'Charges', 'Provider (ours)', 'Provider (API)', 'Variance', 'Profit (ours)', 'Profit (API)'];
        $aligns = ['left', 'right', 'right', 'right', 'right', 'right', 'right', 'right'];

        $this->tableHeader($headers, $widths, $aligns);

        foreach ($report['daily'] as $index => $day) {
            $values = [
                $day['label'].' · '.$day['date'],
                number_format($day['generations']),
                $this->money($day['charge_micros']),
                $this->money($day['provider_micros']),
                $day['usage_provider_micros'] === null ? 'Not synced' : $this->money($day['usage_provider_micros']),
                $day['variance_micros'] === null ? '—' : $this->signedMoney($day['variance_micros']),
                $this->money($day['profit_micros']),
                $day['provider_usage_profit_micros'] === null ? '—' : $this->money($day['provider_usage_profit_micros']),
            ];

            $this->tableRow($values, $widths, $aligns, $index % 2 === 1);
        }

        $totals = $report['totals'];
        $records = (int) ($report['reconciliation']['records'] ?? 0);
        $this->tableRow([
            'Total',
            number_format($totals['generations']),
            $this->money($totals['charge_micros']),
            $this->money($totals['provider_micros']),
            $records > 0 ? $this->money($totals['provider_usage_micros']) : 'Not synced',
            $totals['variance_micros'] === null ? '—' : $this->signedMoney($totals['variance_micros']),
            $this->money($totals['profit_micros']),
            $records > 0 ? $this->money($totals['provider_usage_profit_micros']) : '—',
        ], $widths, $aligns, false, true);

        $this->y += 34;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function users(array $report, int $visibleUsers): void
    {
        $this->sectionTitle('Per-user breakdown', $this->y);
        $this->y += 34;

        $widths = [420, 100, 170, 180, 180, 150, 170, 102];
        $headers = ['Creator', 'Renders', 'Charges', 'Provider (ours)', 'Provider (API)', 'Variance', 'Profit (ours)', 'Balance'];
        $aligns = ['left', 'right', 'right', 'right', 'right', 'right', 'right', 'right'];

        $this->tableHeader($headers, $widths, $aligns);

        foreach (array_slice($report['users'], 0, $visibleUsers) as $index => $user) {
            $this->tableRow([
                $user['name'].' · '.$user['email'],
                number_format($user['generations']),
                $this->money($user['charge_micros']),
                $this->money($user['provider_micros']),
                $user['usage_provider_micros'] === null ? 'Not synced' : $this->money($user['usage_provider_micros']),
                $user['variance_micros'] === null ? '—' : $this->signedMoney($user['variance_micros']),
                $this->money($user['profit_micros']),
                $this->money($user['balance_micros']),
            ], $widths, $aligns, $index % 2 === 1);
        }

        $hidden = count($report['users']) - $visibleUsers;

        if ($hidden > 0) {
            $this->text(
                self::MARGIN,
                $this->y + 22,
                $hidden.' more user(s) are available in the PDF and Excel exports.',
                11,
                false,
                self::PENDING,
            );
            $this->y += 32;
        }
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function footer(array $report): void
    {
        $this->y = max($this->y, $this->height - 76);

        $this->line(self::MARGIN, $this->y, self::WIDTH - self::MARGIN, self::LINE);
        $this->text(
            self::MARGIN,
            $this->y + 24,
            'Charges are what users paid. Provider (ours) is our settled cost. Provider (API) comes from GET /v1/usage; per-user API cost is allocated from daily totals. Variance = API - ours.',
            10,
            false,
            self::SUBTLE,
        );
        $this->text(
            self::WIDTH - self::MARGIN,
            $this->y + 24,
            'Lumora · '.$report['from'].' to '.$report['to'],
            10,
            false,
            self::SUBTLE,
            'right',
        );
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, int>  $widths
     * @param  array<int, string>  $aligns
     */
    private function tableHeader(array $headers, array $widths, array $aligns): void
    {
        $this->fillRect(self::MARGIN, $this->y, self::WIDTH - self::MARGIN * 2, 38, self::BRAND);

        $x = self::MARGIN;
        foreach ($headers as $index => $header) {
            $width = $widths[$index];
            $align = $aligns[$index];
            $this->text(
                $align === 'right' ? $x + $width - 12 : $x + 12,
                $this->y + 25,
                $this->fit($header, $width - 24, 12, true),
                12,
                true,
                [255, 255, 255],
                $align,
            );
            $x += $width;
        }

        $this->y += 38;
    }

    /**
     * @param  array<int, string>  $values
     * @param  array<int, int>  $widths
     * @param  array<int, string>  $aligns
     */
    private function tableRow(array $values, array $widths, array $aligns, bool $shaded, bool $bold = false): void
    {
        if ($shaded) {
            $this->fillRect(self::MARGIN, $this->y, self::WIDTH - self::MARGIN * 2, self::ROW_HEIGHT, self::SURFACE);
        }

        $this->line(self::MARGIN, $this->y + self::ROW_HEIGHT, self::WIDTH - self::MARGIN, self::LINE);

        $x = self::MARGIN;
        foreach ($values as $index => $value) {
            $width = $widths[$index];
            $align = $aligns[$index];
            $this->text(
                $align === 'right' ? $x + $width - 12 : $x + 12,
                $this->y + 21,
                $this->fit($value, $width - 24, 12, $bold),
                12,
                $bold,
                self::INK,
                $align,
            );
            $x += $width;
        }

        $this->y += self::ROW_HEIGHT;
    }

    private function sectionTitle(string $title, int $top): void
    {
        $this->text(self::MARGIN, $top + 22, $title, 20, true, self::INK);
    }

    private function statusPill(int $x, int $top, string $label, array $color): void
    {
        $width = 150;
        $height = 26;
        $this->fillRect($x, $top, $width, $height, $color);
        $this->text($x + $width / 2, $top + 18, $label, 11, true, [255, 255, 255], 'center');
    }

    private function fillRect(int $x, int $top, int $width, int $height, array $color): void
    {
        imagefilledrectangle($this->image, $x, $top, $x + $width, $top + $height, $this->color($color));
    }

    private function strokeRect(int $x, int $top, int $width, int $height, array $color): void
    {
        imagerectangle($this->image, $x, $top, $x + $width, $top + $height, $this->color($color));
    }

    private function line(int $x1, int $y1, int $x2, array $color): void
    {
        imageline($this->image, $x1, $y1, $x2, $y1, $this->color($color));
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $color
     */
    private function text(
        int|float $x,
        int $baseline,
        string $text,
        int $size,
        bool $bold,
        array $color,
        string $align = 'left',
    ): void {
        $width = $this->textWidth($text, $size, $bold);

        if ($align === 'right') {
            $x -= $width;
        } elseif ($align === 'center') {
            $x -= $width / 2;
        }

        $paint = $this->color($color);
        imagettftext($this->image, $size, 0, (int) round($x), $baseline, $paint, $this->font, $text);

        if ($bold) {
            imagettftext($this->image, $size, 0, (int) round($x) + 1, $baseline, $paint, $this->font, $text);
        }
    }

    private function textWidth(string $text, int $size, bool $bold = false): float
    {
        $box = imagettfbbox($size, 0, $this->font, $text);

        if ($box === false) {
            return mb_strlen($text) * $size * 0.5;
        }

        return (float) abs($box[2] - $box[0]) + ($bold ? 1 : 0);
    }

    private function fit(string $text, int $maxWidth, int $size, bool $bold = false): string
    {
        if ($this->textWidth($text, $size, $bold) <= $maxWidth) {
            return $text;
        }

        $trimmed = $text;

        while (mb_strlen($trimmed) > 1 && $this->textWidth($trimmed.'…', $size, $bold) > $maxWidth) {
            $trimmed = mb_substr($trimmed, 0, -1);
        }

        return $trimmed.'…';
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $color
     */
    private function color(array $color): int
    {
        $key = implode('-', $color);

        return $this->palette[$key] ??= imagecolorallocate($this->image, $color[0], $color[1], $color[2]);
    }

    private function estimateHeight(int $dailyRows, int $visibleUsers): int
    {
        $height = 190
            + (2 * 82 + 16 + 34)
            + 82
            + 34
            + 38
            + ($dailyRows + 1) * self::ROW_HEIGHT
            + 34
            + 34
            + 38
            + $visibleUsers * self::ROW_HEIGHT
            + 120;

        return max(900, min(self::MAX_HEIGHT, $height));
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function visibleUserCount(array $report): int
    {
        $dailyRows = count($report['daily']);

        $reserved = 190
            + (2 * 82 + 16 + 34)
            + 82
            + 34
            + 38
            + ($dailyRows + 1) * self::ROW_HEIGHT
            + 34
            + 34
            + 38
            + 120;

        $available = max(0, self::MAX_HEIGHT - $reserved);

        return min(count($report['users']), (int) floor($available / self::ROW_HEIGHT));
    }

    private function fontPath(): string
    {
        $path = resource_path('fonts/Outfit-Variable.ttf');

        if (! is_file($path)) {
            throw new RuntimeException('The report font is missing. Expected resources/fonts/Outfit-Variable.ttf.');
        }

        return $path;
    }

    private function timestamp(?string $value): string
    {
        return $value ? CarbonImmutable::parse($value)->format('M j, Y H:i') : 'never';
    }

    private function money(?int $micros): string
    {
        return $micros === null ? '—' : Money::format($micros, 2);
    }

    private function signedMoney(int $micros): string
    {
        return ($micros > 0 ? '+' : '').Money::format($micros, 2);
    }

    private function credits(int $micros): string
    {
        return number_format($micros / Money::CREDIT_MICROS, 2).' credits';
    }
}
