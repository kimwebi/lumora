<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Services\Billing\CreditService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('features/auth/pages/Register');
    }

    public function store(Request $request, CreditService $credits): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()->min(8)],
            'terms' => ['accepted'],
        ], [
            'terms.accepted' => 'Please accept the terms to create your studio.',
        ]);

        $user = DB::transaction(function () use ($data, $credits) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $bonus = (int) config('lumora.credits.welcome_bonus_micros', 0);

            if ($bonus > 0) {
                $credits->grant(
                    $user,
                    $bonus,
                    CreditTransaction::TYPE_BONUS,
                    'Welcome credits from Lumora',
                    'welcome:'.$user->id,
                );
            }

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('success', 'Welcome to Lumora. Your studio is ready — enjoy your complimentary credits.');
    }
}
