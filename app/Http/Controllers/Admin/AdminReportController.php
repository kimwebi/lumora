<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminReportService;
use App\Services\Admin\Reports\ReportPdf;
use App\Services\Admin\Reports\ReportPng;
use App\Services\Admin\Reports\ReportXlsx;
use App\Services\Pixelforge\PixelforgeException;
use App\Services\Pixelforge\UsageReconciler;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AdminReportController extends Controller
{
    public function index(Request $request, AdminReportService $reports): InertiaResponse
    {
        return Inertia::render('features/admin/pages/AdminReports', [
            'report' => $reports->build((int) $request->query('days', 14)),
            'providerReady' => filled(config('lumora.provider.api_key')),
        ]);
    }

    public function sync(
        Request $request,
        AdminReportService $reports,
        UsageReconciler $reconciler,
    ): RedirectResponse {
        $days = $reports->normaliseDays((int) $request->input('days', 14));

        try {
            $result = $reconciler->sync(
                CarbonImmutable::now()->subDays($days - 1)->startOfDay(),
                CarbonImmutable::now()->endOfDay(),
            );
        } catch (PixelforgeException $exception) {
            return back()->with('error', 'Provider usage sync failed: '.$exception->getMessage());
        }

        return back()->with(
            'success',
            "Provider usage synced — {$result['stored']} record(s) processed for the last {$days} days.",
        );
    }

    public function export(
        Request $request,
        string $format,
        AdminReportService $reports,
        ReportPdf $pdf,
        ReportXlsx $xlsx,
        ReportPng $png,
    ): Response {
        $report = $reports->build((int) $request->query('days', 14));
        $filename = 'lumora-operator-report-'.$report['from'].'-to-'.$report['to'];

        [$content, $mime, $extension] = match ($format) {
            'pdf' => [$pdf->render($report), 'application/pdf', 'pdf'],
            'xlsx' => [$xlsx->render($report), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'xlsx'],
            'png' => [$png->render($report), 'image/png', 'png'],
        };

        return response($content, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="'.$filename.'.'.$extension.'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
