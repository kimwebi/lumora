<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseCreditsRequest;
use App\Http\Resources\CreditTransactionResource;
use App\Services\Billing\CreditService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function index(Request $request, CreditService $credits): Response
    {
        $user = $request->user();

        $packages = collect(config('lumora.credits.packages', []))
            ->map(fn (array $package, string $key) => [
                'key' => $key,
                'name' => $package['name'],
                'credits' => (int) $package['credits'],
                'price_micros' => (int) $package['price_micros'],
                'price' => Money::format((int) $package['price_micros'], 2),
                'price_per_credit' => Money::format(
                    (int) round($package['price_micros'] / max(1, (int) $package['credits'])),
                    2,
                ),
                'description' => $package['description'],
                'popular' => (bool) $package['popular'],
            ])
            ->values();

        $transactions = $user->creditTransactions()
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('features/billing/pages/Billing', [
            'summary' => $credits->summary($user),
            'packages' => $packages,
            'transactions' => CreditTransactionResource::collection($transactions),
            'creditMicros' => (int) config('lumora.credits.micros_per_credit'),
        ]);
    }

    public function purchase(PurchaseCreditsRequest $request, CreditService $credits): RedirectResponse
    {
        $data = $request->validated();
        $package = config('lumora.credits.packages.'.$data['package']);

        DB::transaction(function () use ($request, $credits, $data) {
            $user = $credits->lockUser($request->user()->id);

            $credits->purchase(
                $user,
                $data['package'],
                'purchase_'.$data['purchase_token'],
            );
        });

        return back()->with(
            'success',
            number_format((int) $package['credits']).' credits added to your studio. Happy creating.',
        );
    }
}
