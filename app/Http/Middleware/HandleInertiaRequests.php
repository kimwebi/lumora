<?php

namespace App\Http\Middleware;

use App\Models\Generation;
use App\Support\Seo;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => fn () => $user?->toInertiaArray(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'notifications' => fn () => $this->notifications($request),
            'seo' => fn () => Seo::forPath($request->path()),
            'brand' => [
                'name' => config('lumora.brand.name'),
                'tagline' => config('lumora.brand.tagline'),
            ],
            'support' => [
                'email' => config('lumora.brand.support_email'),
            ],
            'quote' => fn () => Inspiring::quote(),
        ]);
    }

    private function notifications(Request $request): array
    {
        if (! $request->user()) {
            return [];
        }

        return Generation::query()
            ->ownedBy($request->user())
            ->whereNotNull('settled_at')
            ->where('settled_at', '>=', now()->subDay())
            ->latest('settled_at')
            ->limit(6)
            ->get()
            ->map(fn (Generation $generation) => [
                'id' => $generation->uuid,
                'prompt' => str($generation->prompt)->limit(80)->toString(),
                'status' => $generation->status,
                'image_url' => $generation->image_url,
                'settled_at' => $generation->settled_at?->toIso8601String(),
                'charge_micros' => $generation->actual_user_charge_micros,
            ])
            ->all();
    }
}
