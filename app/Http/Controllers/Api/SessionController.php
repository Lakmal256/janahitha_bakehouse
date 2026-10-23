<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SessionController extends Controller
{
    /**
     * Show who is signed in, and whether the first admin account still has to be created.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user && ! $user->active) {
            $this->endSession($request);
            $user = null;
        }

        return response()->json([
            'data' => [
                'user' => $user ? new UserResource($user->load('routes:id,source_key')) : null,
                'needsSetup' => ! User::query()->exists(),
            ],
        ]);
    }

    /**
     * Sign in with a username and password.
     */
    public function store(LoginRequest $request): UserResource
    {
        $username = Str::lower(trim($request->string('username')));
        $password = (string) $request->string('password');
        $throttleKey = Str::transliterate($username.'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'username' => trans('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)]),
            ])->status(429);
        }

        $user = User::query()->where('username', $username)->first();
        $user?->upgradeLegacyPassword($password);

        if (! $user || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages(['username' => trans('auth.failed')]);
        }

        if (! $user->active) {
            throw ValidationException::withMessages(['username' => 'This account is turned off. Ask the admin.']);
        }

        RateLimiter::clear($throttleKey);
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return new UserResource($user->load('routes:id,source_key'));
    }

    /**
     * Sign out.
     */
    public function destroy(Request $request): Response
    {
        $this->endSession($request);

        return response()->noContent();
    }

    private function endSession(Request $request): void
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
