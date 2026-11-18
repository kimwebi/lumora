<?php

namespace App\Services\Admin\Reports;

use App\Support\Money;
use Carbon\CarbonImmutable;
use RuntimeException;
use ZipArchive;

/**
 * Small XLSX writer for the operator report. It creates a standard OOXML
 * workbook with Summary, Daily and Users sheets without an extra package.
 */
class ReportXlsx
{
    /**
     * @param  array<string, mixed>  $report
     */
    public function render(array $report): string
    {
        $sheets = [
            $this->summarySheet($report),
            $this->dailySheet($report),
            $this->userSheet($report),
        ];

        $files = [
            '[Content_Types].xml' => $this->contentTypesXml(),
            '_rels/.rels' => $this->rootRelsXml(),
            'docProps/core.xml' => $this->coreXml($report),
            'docProps/app.xml' => $this->appXml(),
            'xl/workbook.xml' => $this->workbookXml($sheets),
            'xl/_rels/workbook.xml.rels' => $this->workbookRelsXml($sheets),
            'xl/styles.xml' => $this->stylesXml(),
        ];

        foreach ($sheets as $index => $sheet) {
            $files['xl/worksheets/sheet'.($index + 1).'.xml'] = $this->sheetXml($sheet);
        }

        $path = tempnam(sys_get_temp_dir(), 'lumora-report-');

        if ($path === false) {
            throw new RuntimeException('Unable to create a temporary file for the report.');
        }

        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create the Excel workbook.');
        }

        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }

        $zip->close();

        $binary = file_get_contents($path);
        @unlink($path);

        if ($binary === false) {
            throw new RuntimeException('Unable to read the generated Excel workbook.');
        }

        return $binary;
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array{name: string, rows: array<int, array<int, array<string, mixed>>>, widths: array<int, float>, freeze: bool}
     */
    private function summarySheet(array $report): array
    {
        $totals = $report['totals'];
        $reconciliation = $report['reconciliation'];
        $usageRecords = (int) ($reconciliation['records'] ?? 0);

        $rows = [
            [$this->string('Lumora operator report', 6)],
            [$this->string('Generated'), $this->string($this->timestamp($report['generated_at'] ?? null))],
            [$this->string('Range'), $this->string($report['from'].' to '.$report['to'].' ('.$report['range_days'].' days)')],
            [],
            [$this->string('Metric', 1), $this->string('Value', 1), $this->string('Notes', 1)],
            [$this->string('Customer charges'), $this->money($totals['charge_micros']), $this->string($this->credits($totals['charge_micros']))],
            [$this->string('Provider cost · our records'), $this->money($totals['provider_micros']), $this->string('Settled generations')],
            [$this->string('Provider cost · Pixelforge API'), $usageRecords > 0 ? $this->money($totals['provider_usage_micros']) : $this->string('Not synced'), $this->string('GET /v1/usage · daily totals')],
            [$this->string('Variance · API minus ours'), $totals['variance_micros'] === null ? $this->string('Not synced') : $this->money($totals['variance_micros']), $this->string('Should be $0.00 when balanced')],
            [$this->string('Gross profit · our records'), $this->money($totals['profit_micros']), $this->string('Charges minus our provider cost')],
            [$this->string('Gross profit · API costs'), $usageRecords > 0 ? $this->money($totals['provider_usage_profit_micros']) : $this->string('Not synced'), $this->string('Charges minus provider API cost')],
            [$this->string('Margin · our records'), $this->number($totals['margin_percent'] / 100, 5), $this->string('Gross profit / customer charges')],
            [],
            [$this->string('Renders', 2), $this->integer($totals['generations'], 4)],
            [$this->string('Succeeded', 2), $this->integer($totals['succeeded'], 4)],
            [$this->string('Failed', 2), $this->integer($totals['failed'], 4)],
            [$this->string('Users', 2), $this->integer($totals['users'], 4)],
            [$this->string('Active users', 2), $this->integer($totals['active_users'], 4)],
            [],
            [$this->string('Provider usage records', 2), $this->integer($usageRecords, 4)],
            [$this->string('Linked usage records', 2), $this->integer((int) ($reconciliation['linked_records'] ?? 0), 4)],
            [$this->string('Days with provider spend', 2), $this->integer((int) ($reconciliation['active_days'] ?? 0), 4)],
            [$this->string('Last synced', 2), $this->string($this->timestamp($reconciliation['last_synced_at'] ?? null))],
        ];

        return [
            'name' => 'Summary',
            'rows' => $rows,
            'widths' => [38, 20, 44],
            'freeze' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array{name: string, rows: array<int, array<int, array<string, mixed>>>, widths: array<int, float>, freeze: bool}
     */
    private function dailySheet(array $report): array
    {
        $rows = [[
            $this->string('Date', 1),
            $this->string('Renders', 1),
            $this->string('Charges', 1),
            $this->string('Provider (ours)', 1),
            $this->string('Provider (API)', 1),
            $this->string('Variance', 1),
            $this->string('Profit (ours)', 1),
            $this->string('Profit (API)', 1),
            $this->string('Usage records', 1),
        ]];

        foreach ($report['daily'] as $day) {
            $rows[] = [
                $this->string($day['date']),
                $this->integer($day['generations'], 4),
                $this->money($day['charge_micros']),
                $this->money($day['provider_micros']),
                $day['usage_provider_micros'] === null ? $this->string('Not synced') : $this->money($day['usage_provider_micros']),
                $day['variance_micros'] === null ? $this->string('—') : $this->money($day['variance_micros']),
                $this->money($day['profit_micros']),
                $day['provider_usage_profit_micros'] === null ? $this->string('—') : $this->money($day['provider_usage_profit_micros']),
                $this->integer($day['usage_records'], 4),
            ];
        }

        $rows[] = [
            $this->string('Total', 2),
            $this->integer($report['totals']['generations'], 8),
            $this->money($report['totals']['charge_micros'], 8),
            $this->money($report['totals']['provider_micros'], 8),
            (int) $report['reconciliation']['records'] > 0 ? $this->money($report['totals']['provider_usage_micros'], 8) : $this->string('Not synced', 2),
            $report['totals']['variance_micros'] === null ? $this->string('—', 2) : $this->money($report['totals']['variance_micros'], 8),
            $this->money($report['totals']['profit_micros'], 8),
            (int) $report['reconciliation']['records'] > 0 ? $this->money($report['totals']['provider_usage_profit_micros'], 8) : $this->string('—', 2),
            $this->integer((int) $report['reconciliation']['records'], 8),
        ];

        return [
            'name' => 'Daily',
            'rows' => $rows,
            'widths' => [14, 10, 14, 18, 18, 14, 16, 16, 15],
            'freeze' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array{name: string, rows: array<int, array<int, array<string, mixed>>>, widths: array<int, float>, freeze: bool}
     */
    private function userSheet(array $report): array
    {
        $rows = [[
            $this->string('Creator', 1),
            $this->string('Email', 1),
            $this->string('Renders', 1),
            $this->string('Charges', 1),
            $this->string('Provider (ours)', 1),
            $this->string('Provider (API)', 1),
            $this->string('Variance', 1),
            $this->string('Profit (ours)', 1),
            $this->string('Balance', 1),
        ]];

        foreach ($report['users'] as $user) {
            $rows[] = [
                $this->string($user['name']),
                $this->string($user['email']),
                $this->integer($user['generations'], 4),
                $this->money($user['charge_micros']),
                $this->money($user['provider_micros']),
                $user['usage_provider_micros'] === null ? $this->string('Not synced') : $this->money($user['usage_provider_micros']),
                $user['variance_micros'] === null ? $this->string('—') : $this->money($user['variance_micros']),
                $this->money($user['profit_micros']),
                $this->money($user['balance_micros']),
            ];
        }

        return [
            'name' => 'Users',
            'rows' => $rows,
            'widths' => [26, 36, 10, 14, 18, 18, 14, 16, 16],
            'freeze' => true,
        ];
    }

    /**
     * @param  array{name: string, rows: array<int, array<int, array<string, mixed>>>, widths: array<int, float>, freeze: bool}  $sheet
     */
    private function sheetXml(array $sheet): string
    {
        $columns = '';

        foreach ($sheet['widths'] as $index => $width) {
            $columns .= '<col min="'.($index + 1).'" max="'.($index + 1).'" width="'.$width.'" customWidth="1"/>';
        }

        $rows = '';

        foreach ($sheet['rows'] as $rowIndex => $cells) {
            $number = $rowIndex + 1;

            if ($cells === []) {
                $rows .= '<row r="'.$number.'"/>';

                continue;
            }

            $xml = '<row r="'.$number.'">';

            foreach ($cells as $columnIndex => $cell) {
                $reference = $this->columnLetter($columnIndex + 1).$number;
                $style = (int) ($cell['style'] ?? 0);

                if (($cell['type'] ?? 'inlineStr') === 'n') {
                    $xml .= '<c r="'.$reference.'"'.($style ? ' s="'.$style.'"' : '').'><v>'.$cell['value'].'</v></c>';
                } else {
                    $xml .= '<c r="'.$reference.'" t="inlineStr"'.($style ? ' s="'.$style.'"' : '').'><is><t xml:space="preserve">'
                        .$this->xml($cell['value'])
                        .'</t></is></c>';
                }
            }

            $rows .= $xml.'</row>';
        }

        $pane = $sheet['freeze']
            ? '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            : '<sheetViews><sheetView workbookViewId="0"/></sheetViews>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<dimension ref="A1:'.$this->columnLetter(max(array_map('count', $sheet['rows']))).count($sheet['rows']).'"/>'
            .$pane
            .'<cols>'.$columns.'</cols>'
            .'<sheetData>'.$rows.'</sheetData>'
            .'</worksheet>';
    }

    /**
     * @param  array<int, array{name: string, rows: array<int, array<int, array<string, mixed>>>, widths: array<int, float>, freeze: bool}>  $sheets
     */
    private function workbookXml(array $sheets): string
    {
        $entries = '';

        foreach ($sheets as $index => $sheet) {
            $entries .= '<sheet name="'.$this->xml($sheet['name']).'" sheetId="'.($index + 1).'" r:id="rId'.($index + 1).'"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'.$entries.'</sheets>'
            .'</workbook>';
    }

    /**
     * @param  array<int, array{name: string}>  $sheets
     */
    private function workbookRelsXml(array $sheets): string
    {
        $relationships = '';

        foreach ($sheets as $index => $sheet) {
            $relationships .= '<Relationship Id="rId'.($index + 1).'" '
                .'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" '
                .'Target="worksheets/sheet'.($index + 1).'.xml"/>';
        }

        $relationships .= '<Relationship Id="rId'.(count($sheets) + 1).'" '
            .'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .$relationships
            .'</Relationships>';
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet3.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            .'<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            .'</Types>';
    }

    private function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            .'</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="2">'
            .'<numFmt numFmtId="164" formatCode="&quot;$&quot;#,##0.00"/>'
            .'<numFmt numFmtId="165" formatCode="0.0%"/>'
            .'</numFmts>'
            .'<fonts count="5">'
            .'<font><sz val="11"/><color rgb="FF18181B"/><name val="Calibri"/><family val="2"/></font>'
            .'<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>'
            .'<font><b/><sz val="11"/><color rgb="FF18181B"/><name val="Calibri"/><family val="2"/></font>'
            .'<font><b/><sz val="16"/><color rgb="FF18181B"/><name val="Calibri"/><family val="2"/></font>'
            .'<font><sz val="11"/><color rgb="FF71717A"/><name val="Calibri"/><family val="2"/></font>'
            .'</fonts>'
            .'<fills count="4">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF5B21B6"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFF5F3FF"/><bgColor indexed="64"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="2">'
            .'<border><left/><right/><top/><bottom/><diagonal/></border>'
            .'<border><left/><right/><top/><bottom style="thin"><color rgb="FFE4E4E7"/></bottom><diagonal/></border>'
            .'</borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="9">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center"/></xf>'
            .'<xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0"/>'
            .'<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            .'<xf numFmtId="3" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            .'<xf numFmtId="165" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            .'<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="4" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="164" fontId="2" fillId="3" borderId="0" xfId="0" applyNumberFormat="1"/>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function coreXml(array $report): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
            .'xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" '
            .'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            .'<dc:title>Lumora operator report</dc:title>'
            .'<dc:creator>Lumora</dc:creator>'
            .'<dcterms:created xsi:type="dcterms:W3CDTF">'.now()->toIso8601String().'</dcterms:created>'
            .'<cp:contentStatus>Report '.$this->xml($report['from'].' to '.$report['to']).'</cp:contentStatus>'
            .'</cp:coreProperties>';
    }

    private function appXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" '
            .'xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            .'<Application>Lumora</Application>'
            .'</Properties>';
    }

    /**
     * @return array{value: string, type: string, style: int}
     */
    private function string(string $value, int $style = 0): array
    {
        return ['value' => $value, 'type' => 'inlineStr', 'style' => $style];
    }

    /**
     * @return array{value: string, type: string, style: int}
     */
    private function integer(int $value, int $style = 0): array
    {
        return ['value' => (string) $value, 'type' => 'n', 'style' => $style];
    }

    /**
     * @return array{value: string, type: string, style: int}
     */
    private function number(float $value, int $style = 0): array
    {
        return ['value' => rtrim(rtrim(number_format($value, 6, '.', ''), '0'), '.'), 'type' => 'n', 'style' => $style];
    }

    /**
     * @return array{value: string, type: string, style: int}
     */
    private function money(?int $micros, int $style = 3): array
    {
        return $this->number(($micros ?? 0) / Money::MICROS_PER_UNIT, $style);
    }

    private function columnLetter(int $index): string
    {
        $letters = '';

        while ($index > 0) {
            $index--;
            $letters = chr(65 + ($index % 26)).$letters;
            $index = intdiv($index, 26);
        }

        return $letters;
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function timestamp(?string $value): string
    {
        return $value ? CarbonImmutable::parse($value)->format('M j, Y H:i') : 'Never';
    }

    private function credits(int $micros): string
    {
        return number_format($micros / Money::CREDIT_MICROS, 2).' credits';
    }
}
