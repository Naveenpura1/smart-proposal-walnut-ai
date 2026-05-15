<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SessionController extends Controller
{
    /**
     * Display all active sessions grouped by user (AC-24).
     *
     * "Active" = last_activity within the configured session lifetime.
     */
    public function index(Request $request): View
    {
        $lifetimeSeconds = config('session.lifetime', 120) * 60;
        $cutoff          = now()->subSeconds($lifetimeSeconds)->timestamp;

        // Fetch active sessions, left-join with users so guest sessions also appear.
        $sessions = DB::table('sessions')
            ->leftJoin('users', 'sessions.user_id', '=', 'users.id')
            ->where('sessions.last_activity', '>=', $cutoff)
            ->select(
                'sessions.id as session_id',
                'sessions.user_id',
                'sessions.ip_address',
                'sessions.user_agent',
                'sessions.last_activity',
                'users.name as user_name',
                'users.email as user_email',
                'users.role as user_role',
            )
            ->orderByDesc('sessions.last_activity')
            ->paginate(25)
            ->withQueryString();

        $totalActive = DB::table('sessions')
            ->where('last_activity', '>=', $cutoff)
            ->count();

        return view('admin.sessions.index', compact('sessions', 'totalActive'));
    }

    /**
     * Invalidate a specific session (AC-24: manual session revocation).
     */
    public function destroy(Request $request, string $sessionId): RedirectResponse
    {
        $session = DB::table('sessions')->where('id', $sessionId)->first();

        if (! $session) {
            return back()->with('error', 'Session not found or already expired.');
        }

        // Prevent admin from revoking their own current session
        if ($sessionId === $request->session()->getId()) {
            return back()->with('error', 'You cannot revoke your own current session. Use Sign Out instead.');
        }

        DB::table('sessions')->where('id', $sessionId)->delete();

        Log::channel('security')->warning('Admin revoked session', [
            'admin_id'           => $request->user()->id,
            'admin_email'        => $request->user()->email,
            'revoked_session_id' => $sessionId,
            'revoked_user_id'    => $session->user_id,
            'ip'                 => $request->ip(),
            'timestamp'          => now()->utc()->toIso8601String(),
        ]);

        return back()->with('success', 'Session has been revoked successfully.');
    }

    /**
     * Invalidate ALL sessions for a given user (AC-24: full account lockout).
     */
    public function destroyForUser(Request $request, User $user): RedirectResponse
    {
        // Prevent admin from locking themselves out
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot revoke all sessions for your own account.');
        }

        $count = DB::table('sessions')->where('user_id', $user->id)->count();
        DB::table('sessions')->where('user_id', $user->id)->delete();

        Log::channel('security')->warning('Admin revoked all sessions for user', [
            'admin_id'        => $request->user()->id,
            'admin_email'     => $request->user()->email,
            'target_user_id'  => $user->id,
            'target_email'    => $user->email,
            'sessions_killed' => $count,
            'ip'              => $request->ip(),
            'timestamp'       => now()->utc()->toIso8601String(),
        ]);

        return back()->with('success', "All {$count} session(s) for \"{$user->name}\" have been revoked.");
    }
}
