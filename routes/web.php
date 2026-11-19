<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CodeExplanationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\GenerationController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Webhooks\PixelforgeWebhookController;
use App\Http\Middleware\PreventSearchIndexing;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', LandingController::class)->name('home');
Route::get('/terms', fn () => Inertia::render('features/legal/pages/Terms'))->name('terms');
Route::get('/privacy', fn () => Inertia::render('features/legal/pages/Privacy'))->name('privacy');
Route::get('/guide', [DocumentationController::class, 'userGuide'])->name('guide');
Route::get('/developer-guide', [DocumentationController::class, 'developerGuide'])->name('developer-guide');
Route::get('/code-explanation', CodeExplanationController::class)->middleware(PreventSearchIndexing::class)->name('code-explanation');
Route::redirect('/developer guide', '/developer-guide', 301);
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', fn () => response(
    "User-agent: *\nAllow: /\nDisallow: /dashboard\nDisallow: /generate\nDisallow: /creations\nDisallow: /billing\nDisallow: /settings\nDisallow: /admin\n\nSitemap: ".url('/sitemap.xml')."\n",
    200,
    ['Content-Type' => 'text/plain'],
))->name('robots');

Route::post('/webhooks/pixelforge', PixelforgeWebhookController::class)
    ->name('webhooks.pixelforge');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/generate', [GenerationController::class, 'create'])->name('generate');
    Route::post('/generations', [GenerationController::class, 'store'])->name('generations.store');
    Route::get('/creations', [GenerationController::class, 'index'])->name('creations');
    Route::get('/generations/{generation:uuid}/status', [GenerationController::class, 'status'])->name('generations.status');
    Route::delete('/generations/{generation:uuid}', [GenerationController::class, 'destroy'])->name('generations.destroy');

    Route::get('/billing', [BillingController::class, 'index'])->name('billing');
    Route::post('/billing/purchase', [BillingController::class, 'purchase'])->name('billing.purchase');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    Route::delete('/settings/account', [SettingsController::class, 'destroy'])->name('settings.account.destroy');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::post('/reconcile', [AdminDashboardController::class, 'reconcile'])->name('reconcile');

    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports');
    Route::post('/reports/sync', [AdminReportController::class, 'sync'])->name('reports.sync');
    Route::get('/reports/export/{format}', [AdminReportController::class, 'export'])
        ->whereIn('format', ['pdf', 'xlsx', 'png'])
        ->name('reports.export');
});


use Illuminate\Support\Facades\Storage;
use App\Models\Generation;

Route::get('/lumora-diagnostic', function () {
    return response()->json([

        // Keep your existing diagnostic information here.

        'generations' => \App\Models\Generation::withTrashed()
            ->where('status', 'succeeded')
            ->get()
            ->map(fn ($generation) => [
                'uuid' => $generation->uuid,
                'provider_generation_id' => $generation->provider_generation_id,
                'image_url' => $generation->image_url,
                'provider_image_url' => $generation->meta['provider_image_url'] ?? null,
                'image_mirrored_at' => $generation->meta['image_mirrored_at'] ?? null,
            ]),

    ]);
})->middleware('auth');
