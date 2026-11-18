<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGenerationRequest;
use App\Http\Resources\GenerationResource;
use App\Models\Generation;
use App\Services\Generations\GenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class GenerationController extends Controller
{
    public function create(Request $request): Response
    {
        $user = $request->user();

        $active = null;

        if ($request->session()->has('active_uuid')) {
            $active = Generation::query()
                ->ownedBy($user)
                ->where('uuid', $request->session()->get('active_uuid'))
                ->first();
        }

        $active ??= $user->generations()->processing()->latest()->first();

        return Inertia::render('features/generate/pages/Generate', [
            'options' => [
                'sizes' => config('lumora.generation.size_options'),
                'qualities' => config('lumora.generation.quality_options'),
                'defaults' => [
                    'size' => config('lumora.generation.default_size'),
                    'quality' => config('lumora.generation.default_quality'),
                ],
            ],
            'pricing' => [
                'rates' => config('lumora.pricing.image_rates_micros'),
                'fallback_micros' => (int) config('lumora.pricing.fallback_image_micros'),
                'markup_basis_points' => (int) config('lumora.pricing.markup_basis_points'),
                'credit_micros' => (int) config('lumora.credits.micros_per_credit'),
            ],
            'balanceMicros' => (int) $user->credit_balance_micros,
            'providerReady' => filled(config('lumora.provider.api_key')),
            'activeCount' => $user->generations()->processing()->count(),
            'maxActive' => (int) config('lumora.generation.max_active_per_user'),
            'active' => $active ? (new GenerationResource($active))->resolve() : null,
        ]);
    }

    public function store(StoreGenerationRequest $request, GenerationService $generations): RedirectResponse
    {
        $generation = $generations->create($request->user(), $request->validated());

        $generations->dispatch($generation);

        return redirect()->route('generate')
            ->with('active_uuid', $generation->uuid)
            ->with('success', 'Your image is rendering. We will update this page the moment it is ready.');
    }

    public function index(Request $request): Response
    {
        $user = $request->user();

        $status = (string) $request->query('status', 'all');
        $status = in_array($status, ['all', 'processing', 'succeeded', 'failed'], true) ? $status : 'all';
        $search = trim((string) $request->query('q', ''));

        $generations = $user->generations()
            ->when($status === 'processing', fn ($query) => $query->processing())
            ->when($status === 'succeeded', fn ($query) => $query->where('status', Generation::STATUS_SUCCEEDED))
            ->when($status === 'failed', fn ($query) => $query->where('status', Generation::STATUS_FAILED))
            ->when($search !== '', fn ($query) => $query->where('prompt', 'like', '%'.$search.'%'))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $counts = $user->generations()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return Inertia::render('features/creations/pages/Creations', [
            'generations' => GenerationResource::collection($generations),
            'filters' => [
                'status' => $status,
                'q' => $search,
            ],
            'counts' => [
                'all' => (int) $counts->sum(),
                'processing' => (int) ($counts[Generation::STATUS_PENDING] ?? 0) + (int) ($counts[Generation::STATUS_PROCESSING] ?? 0),
                'succeeded' => (int) ($counts[Generation::STATUS_SUCCEEDED] ?? 0),
                'failed' => (int) ($counts[Generation::STATUS_FAILED] ?? 0),
            ],
            'activeCount' => $user->generations()->processing()->count(),
        ]);
    }

    public function status(Request $request, Generation $generation): JsonResponse
    {
        Gate::authorize('view', $generation);

        return response()->json([
            'generation' => (new GenerationResource($generation))->resolve(),
            'balance_micros' => (int) $request->user()->fresh()->credit_balance_micros,
            'active_count' => $request->user()->generations()->processing()->count(),
        ]);
    }

    public function destroy(Request $request, Generation $generation): RedirectResponse
    {
        Gate::authorize('delete', $generation);

        if (! $generation->isTerminal()) {
            return back()->with('error', 'You can only remove a creation after it has finished processing.');
        }

        $generation->delete();

        return back()->with('success', 'Creation removed from your gallery.');
    }
}
