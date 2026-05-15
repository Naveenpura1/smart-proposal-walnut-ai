<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $request->user()->forceFill([
            'password'       => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ])->save();

        Log::channel('security')->info('Password changed via profile', [
            'user_id'   => $request->user()->id,
            'email'     => $request->user()->email,
            'ip'        => $request->ip(),
            'timestamp' => now()->utc()->toIso8601String(),
        ]);

        return back()->with('status', 'password-updated');
    }
}
