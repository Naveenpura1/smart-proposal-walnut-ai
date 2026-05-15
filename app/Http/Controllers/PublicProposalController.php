<?php

namespace App\Http\Controllers;

use App\Jobs\SendProposalViewedNotificationJob;
use App\Models\Proposal;
use App\Models\ProposalView;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

/**
 * PublicProposalController — WB-032
 *
 * Handles all public-facing, unauthenticated proposal interactions:
 *   show()     — render the read-only client view (AC-2/3/20)
 *   accept()   — client accepts the proposal (AC-21/22)
 *   decline()  — client declines the proposal (AC-21/22)
 *
 * And the authenticated owner action:
 *   regenerateToken() — invalidate old token, issue a new one (AC-18/19)
 */
class PublicProposalController extends Controller
{
    // ── Bot detection patterns (AC-17) ────────────────────────────────────────

    /**
     * User-agent fragments that identify known bots, crawlers, and automated
     * tools. Views matching these patterns are logged as is_bot = true and
     * excluded from public engagement counts.
     *
     * This list covers the major crawlers; extend via config if needed.
     */
    private const BOT_PATTERNS = [
        'bot', 'crawler', 'spider', 'slurp', 'mediapartners',
        'googlebot', 'bingbot', 'yandex', 'duckduckbot', 'baiduspider',
        'facebookexternalhit', 'twitterbot', 'linkedinbot', 'whatsapp',
        'curl/', 'wget/', 'python-requests', 'go-http-client', 'java/',
        'httpclient', 'okhttp', 'axios/', 'postman', 'insomnia',
        'headlesschrome', 'phantomjs', 'selenium', 'playwright', 'puppeteer',
    ];

    // ── Public: render proposal (AC-2/3) ──────────────────────────────────────

    /**
     * Show the read-only client-facing proposal page.
     *
     * AC-2:  No authentication required.
     * AC-10: Expired, declined, or deleted → "no longer available" message.
     * AC-11: Draft → "not yet shared" message.
     * AC-32: Invalid/malformed token → 404-style page.
     */
    public function show(Request $request, string $token): View
    {
        // AC-32: Look up proposal by token — abort with friendly 404 if not found
        $proposal = Proposal::where('public_token', $token)->first();

        if (! $proposal) {
            return view('proposals.public.unavailable', [
                'reason' => 'not_found',
            ]);
        }

        // AC-11: Draft is not yet shared
        if ($proposal->isDraft()) {
            return view('proposals.public.unavailable', [
                'reason'   => 'draft',
                'proposal' => $proposal,
            ]);
        }

        // AC-10: Proposal has been accepted (still show read-only content)
        // No other "expired/declined" state in the current schema — if that
        // feature is added later, the check goes here.

        // AC-4: Record the view event
        $this->recordView($request, $proposal, $token);

        return view('proposals.public.show', compact('proposal'));
    }

    // ── Public: client CTAs (AC-21/22) ────────────────────────────────────────

    /**
     * Client clicks "Accept Proposal" on the public URL (AC-21/22).
     *
     * Allowed from: Sent, Viewed (not Draft or already Accepted).
     */
    public function accept(Request $request, string $token): View
    {
        $proposal = $this->findActiveOrAbort($token);

        if (! in_array($proposal->status, ['Sent', 'Viewed'], true)) {
            return view('proposals.public.unavailable', [
                'reason'   => 'already_resolved',
                'proposal' => $proposal,
            ]);
        }

        $proposal->update(['status' => 'Accepted']);

        Log::channel('security')->info('Proposal accepted by client', [
            'proposal_id' => $proposal->id,
            'ip'          => $request->ip(),
            'ua'          => substr($request->userAgent() ?? '', 0, 200),
            'timestamp'   => now()->utc()->toIso8601String(),
        ]);

        return view('proposals.public.show', [
            'proposal'  => $proposal->fresh(),
            'flash'     => 'accepted',
        ]);
    }

    /**
     * Client clicks "Decline Proposal" on the public URL (AC-21/22).
     */
    public function decline(Request $request, string $token): View
    {
        $proposal = $this->findActiveOrAbort($token);

        if (! in_array($proposal->status, ['Sent', 'Viewed'], true)) {
            return view('proposals.public.unavailable', [
                'reason'   => 'already_resolved',
                'proposal' => $proposal,
            ]);
        }

        // Mark as Draft (declined — not a permanent status in the current schema;
        // treat as reverting to Draft so the owner can revise and re-send)
        // TODO: when a 'Declined' status is added to the ENUM this should use it.
        $proposal->update(['status' => 'Draft']);

        Log::channel('security')->info('Proposal declined by client', [
            'proposal_id' => $proposal->id,
            'ip'          => $request->ip(),
            'ua'          => substr($request->userAgent() ?? '', 0, 200),
            'timestamp'   => now()->utc()->toIso8601String(),
        ]);

        return view('proposals.public.show', [
            'proposal' => $proposal->fresh(),
            'flash'    => 'declined',
        ]);
    }

    // ── Authenticated owner: token regeneration (AC-18/19) ────────────────────

    /**
     * Invalidate the current token and generate a new one (AC-18).
     * Historical view events keep their `token_used` value (AC-19).
     * Requires authentication and proposal ownership (via policy).
     */
    public function regenerateToken(Request $request, Proposal $proposal): RedirectResponse
    {
        $this->authorize('update', $proposal);

        $oldToken = $proposal->public_token;
        $proposal->regenerateToken();

        Log::channel('security')->info('Proposal public token regenerated', [
            'proposal_id' => $proposal->id,
            'old_token'   => $oldToken,
            'new_token'   => $proposal->public_token,
            'user_id'     => auth()->id(),
            'timestamp'   => now()->utc()->toIso8601String(),
        ]);

        return redirect()
            ->route('proposals.show', $proposal)
            ->with('success', 'Share link regenerated — the old link is now invalid.');
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Find a proposal by token that is publicly accessible (not Draft).
     * Returns the unavailable view for Draft or missing proposals.
     */
    private function findActiveOrAbort(string $token): Proposal
    {
        $proposal = Proposal::where('public_token', $token)->first();

        if (! $proposal) {
            abort(404);
        }

        if ($proposal->isDraft()) {
            abort(404);
        }

        return $proposal;
    }

    /**
     * Record a view event for this proposal access (AC-4/5/7/17).
     *
     * AC-4:  Capture timestamp, IP, user-agent, referrer.
     * AC-5:  Determine uniqueness (first view from this IP on this proposal).
     * AC-7:  Auto-transition Sent → Viewed on first human view.
     * AC-17: Detect and flag bots; they are recorded but excluded from counts.
     */
    private function recordView(Request $request, Proposal $proposal, string $token): void
    {
        $ip        = $request->ip();
        $userAgent = $request->userAgent() ?? '';
        $referrer  = $request->header('referer') ?? null;

        // AC-17: bot detection
        $isBot = $this->isBot($userAgent);

        // AC-5: is this IP's first view on this proposal?
        $isUnique = ! $isBot && ! ProposalView::where('proposal_id', $proposal->id)
                                              ->where('ip_address', $ip)
                                              ->where('is_bot', false)
                                              ->exists();

        ProposalView::create([
            'proposal_id' => $proposal->id,
            'token_used'  => $token,
            'ip_address'  => $ip,
            'user_agent'  => $userAgent ? substr($userAgent, 0, 512) : null,
            'referrer'    => $referrer  ? substr($referrer,  0, 2048) : null,
            'is_bot'      => $isBot,
            'is_unique'   => $isUnique,
            'viewed_at'   => now(),
        ]);

        // AC-7: transition Sent → Viewed on first human view
        if (! $isBot && $proposal->status === 'Sent') {
            $proposal->update([
                'status'         => 'Viewed',
                'first_viewed_at' => now(),
            ]);
        }

        // Also record first_viewed_at if it's somehow still null
        if (! $isBot && $isUnique && $proposal->first_viewed_at === null) {
            $proposal->update(['first_viewed_at' => now()]);
        }

        // WB-035: Queue a throttled notification to the Sales Rep (AC-1/2/22).
        // Only human views reach this point (bots are filtered above).
        // Internal admin previews never hit the public route, satisfying AC-22.
        if (! $isBot) {
            SendProposalViewedNotificationJob::dispatch(
                proposalId:     $proposal->id,
                viewerIp:       $ip,
                viewedAt:       now()->utc()->format('M j, Y \a\t g:i A'),
                viewerTimezone: 'UTC',
            )->onQueue(config('notifications.proposal_view_notify_queue', 'default'));
        }
    }

    /**
     * AC-17: Detect bots by matching the UA string against known patterns.
     * Case-insensitive; empty UA strings are treated as bots.
     */
    private function isBot(string $userAgent): bool
    {
        if (empty($userAgent)) {
            return true;
        }

        $lower = strtolower($userAgent);

        foreach (self::BOT_PATTERNS as $pattern) {
            if (str_contains($lower, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
