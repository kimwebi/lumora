<?php

namespace App\Http\Controllers;

use App\Models\Generation;
use App\Models\User;
use App\Services\Images\GenerationImageStore;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    public function __invoke(Request $request, GenerationImageStore $images): Response
    {
        $showcase = Generation::query()
            ->where('status', Generation::STATUS_SUCCEEDED)
            ->whereNotNull('image_url')
            ->latest()
            ->limit(24)
            ->get(['id', 'uuid', 'prompt', 'image_url', 'size', 'quality', 'status', 'created_at', 'meta'])
            ->filter(fn (Generation $generation) => $images->exists($generation))
            ->take(6)
            ->values()
            ->map(fn (Generation $generation) => [
                'uuid' => $generation->uuid,
                'prompt' => $generation->prompt,
                'image_url' => $generation->image_url,
                'size' => $generation->size,
                'quality' => $generation->quality,
            ]);

        $packages = collect(config('lumora.credits.packages', []))
            ->map(fn (array $package, string $key) => [
                'key' => $key,
                'name' => $package['name'],
                'credits' => (int) $package['credits'],
                'price_micros' => (int) $package['price_micros'],
                'description' => $package['description'],
                'popular' => (bool) $package['popular'],
            ])
            ->values();

        return Inertia::render('features/landing/pages/Welcome', [
            'showcase' => $showcase,
            'packages' => $packages,
            'stats' => [
                'creators' => User::query()->count(),
                'images' => Generation::query()->where('status', Generation::STATUS_SUCCEEDED)->count(),
                'markup_basis_points' => (int) config('lumora.pricing.markup_basis_points'),
            ],
            'isAuthenticated' => $request->user() !== null,
            'canRegister' => true,
        ]);
    }
}
