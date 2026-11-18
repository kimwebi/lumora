<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('features/settings/pages/Settings', [
            'profile' => $request->user()->toInertiaArray(),
            'stats' => [
                'generations' => $request->user()->generations()->count(),
                'member_since' => $request->user()->created_at?->toIso8601String(),
            ],
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $emailChanged = $user->email !== $data['email'];

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'email_verified_at' => $emailChanged ? now() : $user->email_verified_at,
        ])->save();

        return back()->with('success', 'Profile updated.');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->forceFill([
            'password' => $request->validated('password'),
            'remember_token' => Str::random(60),
        ])->save();

        return back()->with('success', 'Password updated. Keep it somewhere safe.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'current_password'],
        ], [
            'password.current_password' => 'That password does not match our records.',
        ]);

        $user = $request->user();

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $user->delete();

        return redirect()->route('home')
            ->with('success', 'Your Lumora account has been deleted.');
    }
}
