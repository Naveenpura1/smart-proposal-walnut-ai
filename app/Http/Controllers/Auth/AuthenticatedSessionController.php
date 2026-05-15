<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $default = $request->user()->isAdmin()
            ? route('admin.users.index', absolute: false)
            : route('dashboard', absolute: false);

        return redirect()->intended($default);
    }

    /**
     * Destroy an authenticated session.
     *
     * Rotates remember_token so any persistent "remember me" cookies
     * are immediately invalidated, then wipes the session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            // Rotate remember_token — invalidates all "remember me" cookies
            $user->forceFill(['remember_token' => Str::random(60)])->save();

            Log::channel('security')->info('User signed out', [
                'user_id'   => $user->id,
                'email'     => $user->email,
                'ip'        => $request->ip(),
                'timestamp' => now()->utc()->toIso8601String(),
            ]);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
