<?php

namespace App\Services\Admin\Reports;

use App\Support\Money;
use Carbon\CarbonImmutable;

/**
 * Dependency-free PDF writer for the operator report.
 *
 * The report only needs text and simple table rules, so it uses the core
 * Helvetica fonts instead of pulling a PDF package into the application.
 */
class ReportPdf
{
    private const PAGE_WIDTH = 792.0;

    private const PAGE_HEIGHT = 612.0;

    private const MARGIN = 36.0;

    /** @var array<int, string> */
    private array $pages = [];

    private string $buffer = '';

    private float $y = 0.0;

    /** @var array<string, mixed> */
    private array $report = [];

    /**
     * @param  array<string, mixed>  $report
     */
    public function render(array $report): string
    {
        $this->report = $report;
        $this->pages = [];
        $this->buffer = '';
        $this->newPage();

        $this->summary($report);
        $this->sectionTitle('Daily breakdown');
        $this->dailyTable($report);
        $this->sectionTitle('Per-user breakdown');
        $this->userTable($report);
        $this->notes($report);

        if ($this->buffer !== '') {
            $this->pages[] = $this->buffer;
            $this->buffer = '';
        }

        foreach ($this->pages as $index => &$page) {
            $footer = 'Lumora operator report · '.$report['from'].' to '.$report['to'].' · Page '.($index + 1).' of '.count($this->pages);
            $page .= $this->textCommand(self::MARGIN, self::PAGE_HEIGHT - 24, $footer, 8, false, [0.45, 0.45, 0.5]);
        }

        return $this->assemble();
    }

    private function newPage(): void
    {
        if ($this->buffer !== '') {
            $this->pages[] = $this->buffer;
        }

        $this->buffer = '';
        $this->buffer .= $this->textCommand(self::MARGIN, 48, 'LUMORA', 9, true, [0.36, 0.16, 0.72]);
        $this->buffer .= $this->textCommand(self::MARGIN, 72, 'Operator report', 20, true, [0.08, 0.08, 0.12]);
        $this->buffer .= $this->textCommand(self::MARGIN, 89, 'Customer charges, provider costs and profit', 9, false, [0.35, 0.35, 0.42]);
        $this->buffer .= $this->textCommand(
            self::PAGE_WIDTH - self::MARGIN,
            58,
            $this->rangeLabel($this->report),
            9,
            true,
            [0.12, 0.12, 0.16],
            'right',
        );
        $this->buffer .= $this->textCommand(
            self::PAGE_WIDTH - self::MARGIN,
            73,
            'Generated '.$this->timestamp($this->report['generated_at'] ?? null),
            8,
            false,
            [0.45, 0.45, 0.5],
            'right',
        );
        $this->buffer .= $this->lineCommand(self::MARGIN, 100, self::PAGE_WIDTH - self::MARGIN, 100, [0.88, 0.88, 0.91]);

        $this->y = 122;
    }

    private function pageBreak(): void
    {
        $this->newPage();
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function summary(array $report): void
    {
        $totals = $report['totals'];
        $reconciliation = $report['reconciliation'];
        $usageRecords = (int) ($reconciliation['records'] ?? 0);

        $items = [
            ['Customer charges', $this->money($totals['charge_micros']).'  ·  '.$this->credits($totals['charge_micros'])],
            ['Provider cost · our records', $this->money($totals['provider_micros'])],
            ['Provider cost · Pixelforge API', $usageRecords > 0 ? $this->money($totals['provider_usage_micros']) : 'Not synced'],
            ['Variance · API minus ours', $totals['variance_micros'] === null ? 'Not synced' : $this->signedMoney($totals['variance_micros'])],
            ['Gross profit · our records', $this->money($totals['profit_micros'])],
            ['Gross profit · API costs', $usageRecords > 0 ? $this->money($totals['provider_usage_profit_micros']) : 'Not synced'],
            ['Renders', number_format($totals['generations']).'  ·  '.number_format($totals['succeeded']).' succeeded  ·  '.number_format($totals['failed']).' failed'],
            ['Margin · our records', number_format($totals['margin_percent'], 1).'%'],
        ];

        $columnWidth = (self::PAGE_WIDTH - self::MARGIN * 2 - 12) / 2;

        foreach ($items as $index => $item) {
            $column = $index % 2;
            $row = intdiv($index, 2);
            $x = self::MARGIN + ($column * ($columnWidth + 12));
            $top = $this->y + ($row * 43);

            $this->buffer .= $this->rectCommand($x, $top, $columnWidth, 36, [0.97, 0.97, 0.99]);
            $this->buffer .= $this->textCommand($x + 8, $top + 13, $item[0], 7.5, true, [0.4, 0.4, 0.48]);
            $this->buffer .= $this->textCommand($x + 8, $top + 27, $item[1], 10, true, [0.08, 0.08, 0.12]);
        }

        $this->y += 4 * 43 + 2;

        $this->buffer .= $this->textCommand(
            self::MARGIN,
            $this->y,
            'API costs come from GET /v1/usage. Variance = provider API cost minus our settled records. Last sync: '
                .($reconciliation['last_synced_at'] ? $this->timestamp($reconciliation['last_synced_at']) : 'never'),
            8,
            false,
            [0.35, 0.35, 0.42],
        );
        $this->buffer .= $this->textCommand(
            self::MARGIN,
            $this->y + 12,
            number_format($usageRecords).' usage record(s) · '.number_format((int) ($reconciliation['linked_records'] ?? 0)).' linked · '
                .number_format((int) ($reconciliation['active_days'] ?? 0)).' day(s) with spend',
            8,
            false,
            [0.35, 0.35, 0.42],
        );

        $this->y += 30;
    }

    private function sectionTitle(string $title): void
    {
        if ($this->y > self::PAGE_HEIGHT - 90) {
            $this->pageBreak();
        }

        $this->buffer .= $this->textCommand(self::MARGIN, $this->y, $title, 12, true, [0.08, 0.08, 0.12]);
        $this->y += 8;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function dailyTable(array $report): void
    {
        $rows = array_map(fn (array $day): array => [
            $day['label'].' ('.$day['date'].')',
            number_format($day['generations']),
            $this->money($day['charge_micros']),
            $this->money($day['provider_micros']),
            $day['usage_provider_micros'] === null ? 'Not synced' : $this->money($day['usage_provider_micros']),
            $day['variance_micros'] === null ? '—' : $this->signedMoney($day['variance_micros']),
            $this->money($day['profit_micros']),
            $day['provider_usage_profit_micros'] === null ? '—' : $this->money($day['provider_usage_profit_micros']),
        ], $report['daily']);

        $rows[] = [
            'Total',
            number_format($report['totals']['generations']),
            $this->money($report['totals']['charge_micros']),
            $this->money($report['totals']['provider_micros']),
            (int) $report['reconciliation']['records'] > 0 ? $this->money($report['totals']['provider_usage_micros']) : 'Not synced',
            $report['totals']['variance_micros'] === null ? '—' : $this->signedMoney($report['totals']['variance_micros']),
            $this->money($report['totals']['profit_micros']),
            (int) $report['reconciliation']['records'] > 0 ? $this->money($report['totals']['provider_usage_profit_micros']) : '—',
        ];

        $this->table(
            ['Date', 'Renders', 'Charges', 'Provider (ours)', 'Provider (API)', 'Variance', 'Profit (ours)', 'Profit (API)'],
            $rows,
            [130, 48, 78, 88, 88, 72, 84, 84],
            ['left', 'right', 'right', 'right', 'right', 'right', 'right', 'right'],
        );
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function userTable(array $report): void
    {
        $rows = array_map(fn (array $user): array => [
            $user['name'].' · '.$user['email'],
            number_format($user['generations']),
            $this->money($user['charge_micros']),
            $this->money($user['provider_micros']),
            $user['usage_provider_micros'] === null ? 'Not synced' : $this->money($user['usage_provider_micros']),
            $user['variance_micros'] === null ? '—' : $this->signedMoney($user['variance_micros']),
            $this->money($user['profit_micros']),
            $this->money($user['balance_micros']),
        ], $report['users']);

        $this->table(
            ['Creator', 'Renders', 'Charges', 'Provider (ours)', 'Provider (API)', 'Variance', 'Profit (ours)', 'Balance'],
            $rows,
            [190, 48, 78, 88, 88, 72, 84, 84],
            ['left', 'right', 'right', 'right', 'right', 'right', 'right', 'right'],
        );
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function notes(array $report): void
    {
        if ($this->y > self::PAGE_HEIGHT - 74) {
            $this->pageBreak();
        }

        $this->buffer .= $this->textCommand(
            self::MARGIN,
            $this->y + 8,
            'Charges are what users paid in credits and USD. Provider (ours) is our settled provider cost. '
                .'Provider (API) is the Pixelforge usage total for the same range; per-user API cost is allocated '
                .'by each account\'s share of the local daily provider cost because the provider reports daily totals.',
            8,
            false,
            [0.35, 0.35, 0.42],
        );
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, string>>  $rows
     * @param  array<int, float>  $widths
     * @param  array<int, string>  $aligns
     */
    private function table(array $headers, array $rows, array $widths, array $aligns): void
    {
        $rowHeight = 18.0;

        $this->ensureSpace(24);
        $this->tableHeader($headers, $widths, $aligns);

        foreach (array_values($rows) as $index => $row) {
            if ($this->y + $rowHeight > self::PAGE_HEIGHT - 44) {
                $this->pageBreak();
                $this->tableHeader($headers, $widths, $aligns);
            }

            $background = $index % 2 === 0 ? [0.98, 0.98, 0.99] : [1.0, 1.0, 1.0];
            $this->buffer .= $this->rectCommand(self::MARGIN, $this->y, array_sum($widths), $rowHeight, $background);

            $x = self::MARGIN;
            foreach ($row as $column => $value) {
                $width = $widths[$column] ?? 80;
                $align = $aligns[$column] ?? 'left';
                $textX = $align === 'right' ? $x + $width - 4 : $x + 4;
                $this->buffer .= $this->textCommand(
                    $textX,
                    $this->y + 12,
                    $this->fit((string) $value, $width - 8, 8),
                    8,
                    false,
                    [0.12, 0.12, 0.16],
                    $align,
                );
                $x += $width;
            }

            $this->buffer .= $this->lineCommand(self::MARGIN, $this->y + $rowHeight, self::MARGIN + array_sum($widths), $this->y + $rowHeight, [0.9, 0.9, 0.93]);
            $this->y += $rowHeight;
        }

        $this->y += 18;
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, float>  $widths
     * @param  array<int, string>  $aligns
     */
    private function tableHeader(array $headers, array $widths, array $aligns): void
    {
        $height = 20.0;
        $this->buffer .= $this->rectCommand(self::MARGIN, $this->y, array_sum($widths), $height, [0.36, 0.16, 0.72]);

        $x = self::MARGIN;
        foreach ($headers as $column => $header) {
            $width = $widths[$column] ?? 80;
            $align = $aligns[$column] ?? 'left';
            $textX = $align === 'right' ? $x + $width - 4 : $x + 4;
            $this->buffer .= $this->textCommand($textX, $this->y + 13, $this->fit($header, $width - 8, 7.5), 7.5, true, [1, 1, 1], $align);
            $x += $width;
        }

        $this->y += $height;
    }

    private function ensureSpace(float $height): void
    {
        if ($this->y + $height > self::PAGE_HEIGHT - 44) {
            $this->pageBreak();
        }
    }

    /**
     * @param  array{0: float, 1: float, 2: float}  $color
     */
    private function textCommand(
        float $x,
        float $top,
        string $text,
        float $size,
        bool $bold,
        array $color,
        string $align = 'left',
    ): string {
        $text = $this->pdfString($text);
        $width = $this->approximateWidth($text, $size, $bold);

        if ($align === 'right') {
            $x -= $width;
        } elseif ($align === 'center') {
            $x -= $width / 2;
        }

        return sprintf(
            "BT /%s %.2F Tf %.3F %.3F %.3F rg %.2F %.2F Td (%s) Tj ET\n",
            $bold ? 'F2' : 'F1',
            $size,
            $color[0],
            $color[1],
            $color[2],
            $x,
            self::PAGE_HEIGHT - $top,
            $text,
        );
    }

    /**
     * @param  array{0: float, 1: float, 2: float}  $color
     */
    private function lineCommand(float $x1, float $top1, float $x2, float $top2, array $color): string
    {
        return sprintf(
            "%.3F %.3F %.3F RG 0.5 w %.2F %.2F m %.2F %.2F l S\n",
            $color[0],
            $color[1],
            $color[2],
            $x1,
            self::PAGE_HEIGHT - $top1,
            $x2,
            self::PAGE_HEIGHT - $top2,
        );
    }

    /**
     * @param  array{0: float, 1: float, 2: float}  $color
     */
    private function rectCommand(float $x, float $top, float $width, float $height, array $color): string
    {
        return sprintf(
            "%.3F %.3F %.3F rg %.2F %.2F %.2F %.2F re f\n",
            $color[0],
            $color[1],
            $color[2],
            $x,
            self::PAGE_HEIGHT - $top - $height,
            $width,
            $height,
        );
    }

    private function approximateWidth(string $text, float $size, bool $bold): float
    {
        return mb_strlen($text) * $size * ($bold ? 0.54 : 0.5);
    }

    private function fit(string $text, float $maxWidth, float $size): string
    {
        if ($this->approximateWidth($text, $size, false) <= $maxWidth) {
            return $text;
        }

        $limit = max(4, (int) floor($maxWidth / ($size * 0.5)) - 1);

        return mb_strimwidth($text, 0, $limit, '…');
    }

    private function pdfString(string $text): string
    {
        $text = str_replace(
            ['—', '–', '·', '…', '’', '“', '”'],
            ['-', '-', '|', '...', "'", '"', '"'],
            $text,
        );

        $text = mb_convert_encoding($text, 'Windows-1252', 'UTF-8') ?: $text;

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private function rangeLabel(array $report): string
    {
        return CarbonImmutable::parse($report['from'])->format('M j, Y')
            .' - '
            .CarbonImmutable::parse($report['to'])->format('M j, Y')
            .' · '
            .$report['range_days'].' days';
    }

    private function timestamp(?string $value): string
    {
        if (! $value) {
            return 'never';
        }

        return CarbonImmutable::parse($value)->format('M j, Y H:i');
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
        return number_format($micros / 10_000, 2).' credits';
    }

    private function assemble(): string
    {
        $pageCount = count($this->pages);
        $objects = [];

        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', array_map(fn (int $i): string => (5 + $i * 2).' 0 R', range(0, $pageCount - 1))).'] /Count '.$pageCount.' >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        foreach ($this->pages as $index => $content) {
            $pageObject = 5 + $index * 2;
            $contentObject = $pageObject + 1;

            $objects[$pageObject] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 792 612] '
                .'/Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents '.$contentObject.' 0 R >>';
            $objects[$contentObject] = '<< /Length '.strlen($content)." >>\nstream\n".$content.'endstream';
        }

        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0 => 0];

        foreach ($objects as $number => $object) {
            $offsets[$number] = strlen($pdf);
            $pdf .= $number." 0 obj\n".$object."\nendobj\n";
        }

        $xref = strlen($pdf);
        $size = max(array_keys($objects)) + 1;
        $pdf .= "xref\n0 ".$size."\n0000000000 65535 f \n";

        for ($number = 1; $number < $size; $number++) {
            $pdf .= isset($offsets[$number])
                ? sprintf("%010d 00000 n \n", $offsets[$number])
                : "0000000000 65535 f \n";
        }

        $pdf .= "trailer\n<< /Size ".$size." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";

        return $pdf;
    }
}
