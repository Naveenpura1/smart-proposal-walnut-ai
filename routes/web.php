<?php

<<<<<<< HEAD
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProposalController;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('welcome');
});

// Authenticated Routes
Route::middleware(['auth', 'verified'])->group(function () {
    
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // --- Admin Only Routes ---
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/admin/settings', function () {
            return "Admin Settings Page";
        })->name('admin.settings');
        // Add User Management, Global Reports here
    });

    // --- Sales Rep Only Routes ---
    Route::middleware(['role:sales'])->group(function () {
        Route::get('/proposals/create', function () {
            return "Create Proposal Page";
        })->name('proposals.create');
        // Add Proposal creation, Client management here
    });
});

    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });


Route::middleware(['auth', 'role:sales'])->group(function () {
    Route::get('/proposals/create', [ProposalController::class, 'create'])->name('proposals.create');
    Route::post('/proposals', [ProposalController::class, 'store'])->name('proposals.store');
    Route::get('/proposals/{proposal}/edit', [ProposalController::class, 'edit'])->name('proposals.edit');
    Route::patch('/proposals/{proposal}', [ProposalController::class, 'update'])->name('proposals.update');
    Route::delete('/proposals/{proposal}', [ProposalController::class, 'destroy'])->name('proposals.destroy');
});
require __DIR__.'/auth.php';
=======
use App\Http\Controllers\Admin\AnalyticsController as AdminAnalyticsController;
use App\Http\Controllers\Admin\SessionController as AdminSessionController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\PublicProposalController;
use App\Models\Proposal;
use App\Models\ProposalView;
use Illuminate\Support\Facades\Route;

// ── Public ──────────────────────────────────────────────────────────────────
Route::get('/', function () {
    return redirect()->route('login');
});

// ── Public proposal view (WB-032 — no auth required) ────────────────────────
// AC-2:  Accessible without login.
// AC-10/11/32: Controller handles Draft / invalid-token states gracefully.
Route::get('/proposals/view/{token}',
    [PublicProposalController::class, 'show']
)->name('proposals.public.show');

Route::post('/proposals/view/{token}/accept',
    [PublicProposalController::class, 'accept']
)->name('proposals.public.accept');

Route::post('/proposals/view/{token}/decline',
    [PublicProposalController::class, 'decline']
)->name('proposals.public.decline');

// ── Session keep-alive (AC-17: extend session timer from the idle-timeout modal)
Route::middleware(['auth'])->post('/extend-session', function () {
    // Touching the session is enough — Laravel will reset its lifetime.
    session()->put('_extended_at', now()->toIso8601String());
    return response()->json(['ok' => true]);
})->name('session.extend');

// ── Dashboard (auth only — unverified users land here and see the email
//    verification banner defined in layouts/app.blade.php) ──────────────────
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {

        $user   = auth()->user();
        $stats  = ['total' => 0, 'draft' => 0, 'sent' => 0, 'accepted' => 0];
        $recent = collect();

        // WB-030: admin summary KPIs shown on the dashboard
        $adminStats = ['total_proposals' => 0, 'open_rate' => 0, 'accepted_rate' => 0, 'total_views' => 0];

        if ($user->isSales()) {
            $base  = $user->proposals();
            $stats = [
                'total'    => (clone $base)->count(),
                'draft'    => (clone $base)->where('status', 'Draft')->count(),
                'sent'     => (clone $base)->where('status', 'Sent')->count(),
                'accepted' => (clone $base)->where('status', 'Accepted')->count(),
            ];
            $recent = (clone $base)->latest('updated_at')->limit(5)->get();
        }

        if ($user->isAdmin()) {
            $total       = Proposal::count();
            $sentOrMore  = Proposal::whereIn('status', ['Sent', 'Viewed', 'Accepted'])->count();
            $opened      = Proposal::whereIn('status', ['Viewed', 'Accepted'])->count();
            $accepted    = Proposal::where('status', 'Accepted')->count();
            $adminStats  = [
                'total_proposals' => $total,
                'open_rate'       => $sentOrMore > 0 ? round(($opened   / $sentOrMore) * 100, 1) : 0,
                'accepted_rate'   => $sentOrMore > 0 ? round(($accepted / $sentOrMore) * 100, 1) : 0,
                'total_views'     => ProposalView::where('is_bot', false)->count(),
            ];
        }

        return view('dashboard', compact('stats', 'recent', 'adminStats'));
    })->name('dashboard');
});

// ── Authenticated + verified ─────────────────────────────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {

    // Profile (both roles)
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ── Admin-only ───────────────────────────────────────────────────────────
    Route::middleware(['role:admin'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/settings', function () {
                return view('admin.settings');
            })->name('settings');

            // WB-029/030: Platform-wide analytics + CSV exports (AC-18/19)
            Route::get('/analytics',                [AdminAnalyticsController::class, 'index'])->name('analytics');
            Route::get('/analytics/export/proposals',[AdminAnalyticsController::class, 'exportProposals'])->name('analytics.export.proposals');
            Route::get('/analytics/export/reps',    [AdminAnalyticsController::class, 'exportReps'])->name('analytics.export.reps');

            Route::get('/users',               [AdminUserController::class, 'index'])->name('users.index');
            Route::get('/users/{user}/edit',   [AdminUserController::class, 'edit'])->name('users.edit');
            Route::patch('/users/{user}',      [AdminUserController::class, 'update'])->name('users.update');
            Route::delete('/users/{user}',     [AdminUserController::class, 'destroy'])->name('users.destroy');

            // AC-24: Session management — view and revoke active sessions
            Route::get('/sessions',                          [AdminSessionController::class, 'index'])->name('sessions.index');
            Route::delete('/sessions/{session}',             [AdminSessionController::class, 'destroy'])->name('sessions.destroy');
            Route::delete('/users/{user}/sessions',          [AdminSessionController::class, 'destroyForUser'])->name('sessions.destroyForUser');
        });

    // ── Sales Rep-only ───────────────────────────────────────────────────────
    Route::middleware(['role:sales'])->group(function () {
        Route::get('/proposals',                    [ProposalController::class, 'index'])->name('proposals.index');
        Route::get('/proposals/create',             [ProposalController::class, 'create'])->name('proposals.create');
        Route::post('/proposals',                   [ProposalController::class, 'store'])->name('proposals.store');
        Route::get('/proposals/{proposal}',         [ProposalController::class, 'show'])->name('proposals.show');
        Route::get('/proposals/{proposal}/edit',    [ProposalController::class, 'edit'])->name('proposals.edit');
        Route::patch('/proposals/{proposal}',       [ProposalController::class, 'update'])->name('proposals.update');
        Route::delete('/proposals/{proposal}',      [ProposalController::class, 'destroy'])->name('proposals.destroy');
        // WB-022: Clone — POST prevents accidental activation via link/prefetch
        Route::post('/proposals/{proposal}/clone',  [ProposalController::class, 'clone'])->name('proposals.clone');

        // WB-032: Regenerate public token — invalidates old share link (AC-18)
        Route::post('/proposals/{proposal}/regenerate-token',
            [PublicProposalController::class, 'regenerateToken']
        )->name('proposals.regenerate-token');
    });
});

require __DIR__.'/auth.php';
>>>>>>> 9ad783d (Initial commit)
