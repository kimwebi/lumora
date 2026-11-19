<?php

namespace App\Http\Controllers;

use App\Http\Resources\GenerationResource;
use App\Models\Generation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $stats = [
            'balance_micros' => (int) $user->credit_balance_micros,
            'total_generations' => $user->generations()->count(),
            'succeeded' => $user->generations()->where('status', Generation::STATUS_SUCCEEDED)->count(),
            'processing' => $user->generations()->processing()->count(),
            'spent_micros' => (int) $user->generations()
                ->where('status', Generation::STATUS_SUCCEEDED)
                ->sum('actual_user_charge_micros'),
            'failed' => $user->generations()->where('status', Generation::STATUS_FAILED)->count(),
        ];

        $recent = GenerationResource::collection(
            $user->generations()->latest()->limit(6)->get()
        )->resolve();

        $active = $user->generations()
            ->processing()
            ->latest()
            ->first();

        return Inertia::render('features/dashboard/pages/Dashboard', [
            'stats' => $stats,
            'recent' => $recent,
            'active' => $active ? (new GenerationResource($active))->resolve() : null,
            'providerReady' => filled(config('lumora.provider.api_key')),
        ]);
    }
}
