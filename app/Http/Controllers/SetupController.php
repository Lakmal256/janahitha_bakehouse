<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SetupController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (User::query()->exists()) {
            return redirect()->route('login');
        }

        return view('auth.setup');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'alpha_dash:ascii', 'lowercase', 'min:3', 'max:40', 'unique:users,username'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $owner = Cache::lock('bakehouse:first-admin-setup', 10)->block(5, function () use ($validated): ?User {
            if (User::query()->exists()) {
                return null;
            }

            return User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
                'role' => 'admin',
                'perms' => User::permissionsForRole('admin'),
                'all_routes' => true,
                'active' => true,
                'is_owner' => true,
            ]);
        });

        if (! $owner) {
            return redirect()->route('login')->withErrors([
                'username' => 'Initial setup is already complete. Please sign in.',
            ]);
        }

        Auth::login($owner);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
