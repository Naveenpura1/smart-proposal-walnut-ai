<?php

namespace App\Notifications;

use App\Models\Proposal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * ProposalViewedNotification — WB-035
 *
 * Sent to the Sales Representative when a client views their proposal via the
 * public token URL for the first time within the throttle window.
 *
 * AC-2:  Queued via ShouldQueue so it never blocks the HTTP request.
 * AC-7:  Identifies the proposal by title/reference.
 * AC-8:  Includes client name and contact.
 * AC-9:  Includes the view timestamp with timezone.
 * AC-10: Includes a direct deep-link to the proposal in the dashboard.
 * AC-19: Clear, descriptive subject line.
 * AC-20: Professionally formatted, consistent with app branding.
 * AC-14: Laravel queue retries handle transient SMTP failures.
 */
class ProposalViewedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Number of queue-layer retries for transient SMTP / network failures (AC-14).
     * Each retry is attempted after an exponential back-off managed by the queue driver.
     */
    public int $tries = 3;

    /**
     * Seconds to wait before the first retry (AC-14).
     */
    public int $backoff = 60;

    // ─────────────────────────────────────────────────────────────────────────

    public function __construct(
        private readonly Proposal $proposal,
        private readonly string   $viewerIp,
        private readonly string   $viewedAt,      // pre-formatted with timezone (AC-9)
        private readonly string   $viewerTimezone, // e.g. 'UTC'
    ) {
        $this->onQueue(config('notifications.proposal_view_notify_queue', 'default'));
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Deliver only via mail (AC-1).
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the mail message (AC-7/8/9/10/19/20).
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $appName      = config('app.name', 'Smart Proposal');
        $proposalRef  = $this->proposal->proposal_title
            ?: ('Proposal #' . $this->proposal->id);
        $clientName   = $this->proposal->client_name;
        $clientEmail  = $this->proposal->client_email;
        $proposalUrl  = route('proposals.show', $this->proposal);

        // AC-19: clear subject line
        $subject = "Your proposal "{$proposalRef}" has been viewed by {$clientName}";

        return (new MailMessage)
            // AC-19: descriptive subject
            ->subject($subject)

            // AC-20: professional greeting
            ->greeting("Hello, {$notifiable->name}!")

            // AC-7: identify the proposal
            ->line("Good news — your proposal **{$proposalRef}** has been viewed by your client.")

            // AC-8: client details
            ->line("**Client:** {$clientName}" . ($clientEmail ? " ({$clientEmail})" : ''))

            // AC-9: timestamp with timezone
            ->line("**Viewed at:** {$this->viewedAt} ({$this->viewerTimezone})")

            // AC-10: deep link to proposal
            ->action('Open Proposal', $proposalUrl)

            ->line('This is a great time to follow up while the proposal is fresh in your client's mind.')

            ->salutation("— The {$appName} Team");
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Log failures that exhaust all retries (AC-15).
     */
    public function failed(\Throwable $e): void
    {
        \Illuminate\Support\Facades\Log::channel('walnut_ai')->error(
            'ProposalViewedNotification failed after all retries',
            [
                'proposal_id' => $this->proposal->id,
                'client_name' => $this->proposal->client_name,
                'viewer_ip'   => $this->viewerIp,
                'error'       => $e->getMessage(),
                'timestamp'   => now()->utc()->toIso8601String(),
            ]
        );
    }
}
