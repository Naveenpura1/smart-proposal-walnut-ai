<?php

namespace App\Jobs;

use App\Models\Proposal;
use App\Notifications\ProposalViewedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * SendProposalViewedNotificationJob — WB-035
 *
 * Dispatched by PublicProposalController::recordView() for every human, non-bot
 * proposal view. The job implements throttle logic via the cache before sending
 * the ProposalViewedNotification to the Sales Rep.
 *
 * AC-2:  Queued — never blocks the HTTP response cycle.
 * AC-3/4: Cache-based throttle: one notification per proposal per viewer IP
 *          per throttle window (default: 30 minutes, configurable — AC-23).
 * AC-5:  Second view within the window → cache hit → suppress.
 * AC-6:  After window expires → cache miss → new notification queued.
 * AC-12: No Sales Rep assigned → log + skip (AC-12).
 * AC-16: Every send or suppression is logged to the audit channel.
 * AC-17: Throttle is per-proposal per-IP so multiple proposals notify independently.
 * AC-18: Multiple distinct viewer IPs on the same proposal each get their own
 *         throttle key and produce their own notifications.
 * AC-22: AC-22 — internal admin views do NOT reach this job (filtered upstream).
 * AC-25: Missing / invalid rep email → log warning + skip, no queue error.
 * AC-26: Cache operations are atomic (Cache::add is atomic on most drivers).
 */
class SendProposalViewedNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Queue-layer retries for transient queue failures (not SMTP failures,
     * which are handled inside the Notification itself — AC-14).
     */
    public int $tries = 3;

    // ─────────────────────────────────────────────────────────────────────────

    public function __construct(
        private readonly int    $proposalId,
        private readonly string $viewerIp,
        private readonly string $viewedAt,       // ISO-8601 UTC string
        private readonly string $viewerTimezone, // always 'UTC' unless geo-detected
    ) {}

    // ─────────────────────────────────────────────────────────────────────────

    public function handle(): void
    {
        // ── AC-24: master toggle ──────────────────────────────────────────────
        if (! config('notifications.proposal_view_notify_enabled', true)) {
            return;
        }

        // ── Load the proposal ─────────────────────────────────────────────────
        $proposal = Proposal::with('user')->find($this->proposalId);

        if (! $proposal) {
            Log::channel('walnut_ai')->warning('ProposalViewedNotificationJob: proposal not found', [
                'proposal_id' => $this->proposalId,
                'timestamp'   => now()->utc()->toIso8601String(),
            ]);
            return;
        }

        // ── AC-12: guard — no rep assigned ────────────────────────────────────
        $rep = $proposal->user;

        if (! $rep) {
            Log::channel('walnut_ai')->warning(
                'ProposalViewedNotificationJob: no Sales Rep assigned — skipping',
                [
                    'proposal_id' => $proposal->id,
                    'timestamp'   => now()->utc()->toIso8601String(),
                ]
            );
            return;
        }

        // ── AC-25: guard — missing or invalid email ───────────────────────────
        if (empty($rep->email) || ! filter_var($rep->email, FILTER_VALIDATE_EMAIL)) {
            Log::channel('walnut_ai')->warning(
                'ProposalViewedNotificationJob: Sales Rep email invalid or missing — skipping',
                [
                    'proposal_id' => $proposal->id,
                    'rep_id'      => $rep->id,
                    'timestamp'   => now()->utc()->toIso8601String(),
                ]
            );
            return;
        }

        // ── AC-3/4/5/6/26: cache-based throttle ──────────────────────────────
        //
        // Throttle key is per-proposal per-viewer-IP (AC-17/18).
        // Cache::add() is atomic on Redis and database drivers (AC-26):
        // it only succeeds (returns true) if the key does NOT already exist.
        // If it returns false the key is already set → throttle active → suppress.
        //
        $throttleMinutes = max(0, (int) config('notifications.proposal_view_notify_throttle', 30));
        $throttleKey     = "proposal_view_notif:{$proposal->id}:{$this->viewerIp}";

        $isThrottled = ($throttleMinutes > 0)
            && ! Cache::add($throttleKey, true, now()->addMinutes($throttleMinutes));

        if ($isThrottled) {
            // AC-16: log suppression
            Log::channel('walnut_ai')->info(
                'ProposalViewedNotificationJob: suppressed (throttle active)',
                [
                    'proposal_id'  => $proposal->id,
                    'rep_id'       => $rep->id,
                    'viewer_ip'    => $this->viewerIp,
                    'throttle_min' => $throttleMinutes,
                    'status'       => 'suppressed',
                    'timestamp'    => now()->utc()->toIso8601String(),
                ]
            );
            return;
        }

        // ── Dispatch the notification ─────────────────────────────────────────
        $rep->notify(new ProposalViewedNotification(
            proposal:       $proposal,
            viewerIp:       $this->viewerIp,
            viewedAt:       $this->viewedAt,
            viewerTimezone: $this->viewerTimezone,
        ));

        // ── AC-16: audit log — notification queued ────────────────────────────
        Log::channel('walnut_ai')->info(
            'ProposalViewedNotificationJob: notification queued',
            [
                'proposal_id' => $proposal->id,
                'rep_id'      => $rep->id,
                'rep_email'   => $rep->email,
                'viewer_ip'   => $this->viewerIp,
                'status'      => 'queued',
                'timestamp'   => now()->utc()->toIso8601String(),
            ]
        );
    }

    /**
     * AC-15: Log job failures that exhaust all retries.
     */
    public function failed(\Throwable $e): void
    {
        Log::channel('walnut_ai')->error(
            'SendProposalViewedNotificationJob: failed after all retries',
            [
                'proposal_id' => $this->proposalId,
                'viewer_ip'   => $this->viewerIp,
                'error'       => $e->getMessage(),
                'timestamp'   => now()->utc()->toIso8601String(),
            ]
        );
    }
}
