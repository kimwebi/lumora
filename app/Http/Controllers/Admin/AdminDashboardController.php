<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminMetrics;
use App\Services\Pixelforge\PixelforgeException;
use App\Services\Pixelforge\UsageReconciler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function index(Request $request, AdminMetrics $metrics): Response
    {
        $days = (int) $request->query('days', 14);

        return Inertia::render('features/admin/pages/AdminDashboard', [
            'metrics' => $metrics->overview($days),
            'providerReady' => filled(config('lumora.provider.api_key')),
        ]);
    }

    public function reconcile(UsageReconciler $reconciler): RedirectResponse
    {
        try {
            $result = $reconciler->sync(now()->subDays(2), now());
        } catch (PixelforgeException $exception) {
            return back()->with('error', 'Usage sync failed: '.$exception->getMessage());
        }

        return back()->with(
            'success',
            "Provider usage synced — {$result['stored']} record(s) processed.",
        );
    }
}
