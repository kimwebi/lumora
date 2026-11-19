<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Generation;
use App\Services\Images\GenerationImageStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class AdminImageController extends Controller
{
    public function index(GenerationImageStore $store): Response
    {
        $missing = $store->missing();

        $total = Generation::query()
            ->where('status', Generation::STATUS_SUCCEEDED)
            ->whereNotNull('image_url')
            ->count();

        return Inertia::render('features/admin/pages/AdminImages', [
            'storage' => [
                'mode' => $store->driver(),
                'disk' => $store->disk(),
                'max_bytes' => $store->maxBytes(),
                'total_count' => $total,
                'stored_count' => max(0, $total - $missing->count()),
                'missing_count' => $missing->count(),
                'missing' => $missing->take(100)->map(fn (Generation $generation): array => [
                    'uuid' => $generation->uuid,
                    'prompt' => $generation->prompt,
                    'image_url' => $generation->image_url,
                    'size' => $generation->size,
                    'quality' => $generation->quality,
                    'created_at' => $generation->created_at?->toIso8601String(),
                    'filename' => basename($store->storedPath($generation) ?? $generation->uuid.'.png'),
                    'user' => $generation->user?->only(['name', 'email']),
                    'has_provider_url' => filled($generation->meta['provider_image_url'] ?? null),
                ])->values()->all(),
            ],
        ]);
    }

    public function store(Request $request, GenerationImageStore $store): RedirectResponse
    {
        $maxKilobytes = max(1, intdiv($store->maxBytes(), 1024));

        $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:50'],
            'images.*' => ['required', 'file', 'image', 'mimes:png,jpg,jpeg,webp,gif', 'max:'.$maxKilobytes],
        ], [
            'images.*.max' => 'Each image must be smaller than '.round($maxKilobytes / 1024).' MB on this storage driver.',
        ]);

        $restored = 0;
        $unmatched = [];
        $failed = 0;

        foreach ($request->file('images', []) as $file) {
            $generation = $store->generationForFilename($file->getClientOriginalName());

            if (! $generation || $generation->status !== Generation::STATUS_SUCCEEDED) {
                $unmatched[] = $file->getClientOriginalName();

                continue;
            }

            try {
                $store->restoreFile($generation, $file->getPathname(), $file->getMimeType());
                $restored++;
            } catch (Throwable $exception) {
                report($exception);
                $failed++;
            }
        }

        $message = $restored.' image(s) restored.';

        if ($unmatched !== []) {
            $message .= ' '.count($unmatched).' file(s) did not match a completed render.';
        }

        if ($failed > 0) {
            $message .= ' '.$failed.' file(s) could not be written.';
        }

        return back()->with($restored > 0 ? 'success' : 'warning', $message);
    }

    public function refetch(Generation $generation, GenerationImageStore $store): RedirectResponse
    {
        try {
            $store->restoreFromProvider($generation);
        } catch (Throwable $exception) {
            return back()->with('error', 'Image could not be re-downloaded: '.$exception->getMessage());
        }

        return back()->with('success', 'Image restored from Pixelforge for '.$generation->uuid.'.');
    }
}
