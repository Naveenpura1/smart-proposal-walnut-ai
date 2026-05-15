<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * AC-5: Always return the same generic confirmation message regardless of
     * whether the email exists, preventing user-enumeration attacks.
     * AC-23: Laravel's built-in throttle (one request per 60s per email) is
     * enforced by the password broker; we surface a distinct throttle error.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        // Log the attempt for the security audit trail (AC-22).
        Log::channel('security')->info('Password reset link requested', [
            'email'     => $request->email,
            'status'    => $status,
            'ip'        => $request->ip(),
            'timestamp' => now()->utc()->toIso8601String(),
        ]);

        // Throttle (too many requests for this email) is the only case where we
        // return a visible error — it reveals nothing about account existence.
        if ($status === Password::RESET_THROTTLED) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('passwords.throttled')]);
        }

        // For both RESET_LINK_SENT and INVALID_USER we return the same generic
        // message so an attacker cannot tell whether the email is registered.
        return back()->with(
            'status',
            __('If that email address is registered, we\'ve sent a password reset link. Please check your inbox.')
        );
    }
}
