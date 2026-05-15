<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * RoleMiddleware — Reusable, hierarchy-aware role gate.
 *
 * Usage:
 *   Route::middleware('role:admin')          — single role
 *   Route::middleware('role:admin,super-admin') — any of these roles
 *
 * Satisfies:
 *   AC-2  — Authenticated users with insufficient role receive 403
 *   AC-5  — Evaluated on every request, not only initial load
 *   AC-8  — Missing / null role defaults to deny
 *   AC-10 — super-admin hierarchy: satisfies all lower roles automatically
 *   AC-11 — Single reusable middleware; adding a protected route = one annotation
 *   AC-12 — Denied attempts are logged to the security audit channel
 *   AC-15 — Admin URLs are NOT stored as the post-login redirect for non-admins
 *   AC-16 — Role hierarchy read from config/roles.php (centralised)
 *   AC-18 — Accepts multiple allowed roles in a single middleware call
 *   AC-19 — Route groups inherit parent middleware; no per-child annotation needed
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$allowedRoles): Response
    {
        $user     = $request->user();
        $userRole = $user?->role;   // null if unauthenticated or role column missing

        // AC-8: missing / null role → deny, redirect to login
        if (! $userRole) {
            return redirect()->route('login');
        }

        // AC-10 + AC-18: resolve which concrete roles the user's role satisfies
        $hierarchy = config('roles.hierarchy', []);
        $satisfied = $hierarchy[$userRole] ?? [$userRole];

        // Check if any of the required roles are satisfied
        foreach ($allowedRoles as $required) {
            if (in_array($required, $satisfied, true)) {
                return $next($request);
            }
        }

        // ── Access denied ──────────────────────────────────────────────────
        // AC-12: log the unauthorised attempt to the security audit channel
        Log::channel('security')->warning('Unauthorised route access attempt', [
            'user_id'   => $user->id,
            'user_role' => $userRole,
            'route'     => $request->path(),
            'method'    => $request->method(),
            'required'  => $allowedRoles,
            'ip'        => $request->ip(),
            'timestamp' => now()->utc()->toIso8601String(),
        ]);

        // AC-15: do NOT store admin-only URLs as intended redirect for non-admins;
        // instead clear any previously stored intended URL so the user lands on
        // their role-appropriate home after any re-authentication.
        $request->session()->forget('url.intended');

        // AC-2 + AC-13: return 403 — the custom 403 view handles the friendly message
        abort(403);
    }
}
