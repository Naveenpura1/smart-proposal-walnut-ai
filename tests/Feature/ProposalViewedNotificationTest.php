<?php

namespace Tests\Feature;

use App\Jobs\SendProposalViewedNotificationJob;
use App\Models\Proposal;
use App\Models\User;
use App\Notifications\ProposalViewedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * ProposalViewedNotificationTest — WB-035
 *
 * End-to-end tests for the throttled, queued email notification system.
 *
 *   AC-1   Notification triggered when a client views a proposal
 *   AC-2   Job is queued (not sent inline)
 *   AC-3/4 Throttle: only one notification per proposal per viewer per window
 *   AC-5   Second view within window is suppressed
 *   AC-6   New notification after throttle window expires
 *   AC-7   Email identifies the proposal by name
 *   AC-8   Email includes client name/email
 *   AC-9   Email includes the view timestamp
 *   AC-10  Email contains a deep link to the proposal
 *   AC-12  No Sales Rep → job runs without error, notification skipped
 *   AC-17  Throttle is per-proposal (different proposals notify independently)
 *   AC-18  Different IPs on same proposal each get their own throttle key
 *   AC-19  Subject line is descriptive
 *   AC-22  Bot views do NOT trigger notifications
 *   AC-24  Master toggle disables all notifications
 *   AC-25  Missing/invalid email → notification skipped, no error
 *   AC-27  Exactly one notification per view event within throttle rules
 */
class ProposalViewedNotificationTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function salesRep(): User
    {
        return User::factory()->sales()->create();
    }

    private function sentProposal(User $owner, array $attrs = []): Proposal
    {
        return Proposal::factory()->ownedBy($owner)->create(
            array_merge(['status' => 'Sent'], $attrs)
        );
    }

    private function publicUrl(Proposal $p): string
    {
        return "/proposals/view/{$p->public_token}";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-1/2: Job is queued on every human view
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function visiting_public_url_dispatches_notification_job(): void
    {
        Queue::fake();

        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
             ->get($this->publicUrl($proposal));

        Queue::assertPushed(SendProposalViewedNotificationJob::class, function ($job) use ($proposal) {
            return $this->getPrivate($job, 'proposalId') === $proposal->id;
        });
    }

    /** @test */
    public function notification_job_is_not_dispatched_synchronously(): void
    {
        // Queue::fake() prevents actual execution; we just verify the job is pushed
        Queue::fake();

        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);

        $this->get($this->publicUrl($proposal));

        // Fake queue means nothing ran synchronously — job is waiting in queue
        Queue::assertPushed(SendProposalViewedNotificationJob::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-22: Bot views do NOT trigger the job
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function bot_view_does_not_dispatch_notification_job(): void
    {
        Queue::fake();

        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);

        $this->get($this->publicUrl($proposal), ['User-Agent' => 'Googlebot/2.1']);

        Queue::assertNothingPushed();
    }

    /** @test */
    public function empty_user_agent_does_not_dispatch_notification_job(): void
    {
        Queue::fake();

        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);

        $this->get($this->publicUrl($proposal), ['User-Agent' => '']);

        Queue::assertNothingPushed();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-24: Master toggle
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function notification_is_not_sent_when_master_toggle_is_disabled(): void
    {
        Notification::fake();
        Queue::fake([SendProposalViewedNotificationJob::class]);

        Config::set('notifications.proposal_view_notify_enabled', false);

        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);

        // Manually run the job (as if queue processed it)
        $job = new SendProposalViewedNotificationJob(
            proposalId:     $proposal->id,
            viewerIp:       '1.2.3.4',
            viewedAt:       now()->utc()->format('M j, Y \a\t g:i A'),
            viewerTimezone: 'UTC',
        );
        $job->handle();

        Notification::assertNothingSent();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-3/4/5: Throttle — second view within window is suppressed
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function first_view_sends_notification_and_sets_throttle_key(): void
    {
        Notification::fake();
        Config::set('notifications.proposal_view_notify_throttle', 30);

        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);
        $ip       = '5.6.7.8';

        $job = new SendProposalViewedNotificationJob(
            proposalId:     $proposal->id,
            viewerIp:       $ip,
            viewedAt:       now()->utc()->format('M j, Y \a\t g:i A'),
            viewerTimezone: 'UTC',
        );
        $job->handle();

        Notification::assertSentTo($rep, ProposalViewedNotification::class);

        // Throttle key should now exist in cache
        $cacheKey = "proposal_view_notif:{$proposal->id}:{$ip}";
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test */
    public function second_view_within_throttle_window_is_suppressed(): void
    {
        Notification::fake();
        Config::set('notifications.proposal_view_notify_throttle', 30);

        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);
        $ip       = '5.6.7.8';

        $makeJob = fn () => new SendProposalViewedNotificationJob(
            proposalId:     $proposal->id,
            viewerIp:       $ip,
            viewedAt:       now()->utc()->format('M j, Y \a\t g:i A'),
            viewerTimezone: 'UTC',
        );

        // First view → notification sent
        $makeJob()->handle();
        Notification::assertSentTo($rep, ProposalViewedNotification::class, 1);

        // Second view within the window → suppressed
        $makeJob()->handle();

        // Still exactly 1 notification sent
        Notification::assertSentTo($rep, ProposalViewedNotification::class, 1);
    }

    /** @test */
    public function notification_sends_again_after_throttle_window_expires(): void
    {
        Notification::fake();
        Config::set('notifications.proposal_view_notify_throttle', 30);

        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);
        $ip       = '9.9.9.1';

        $makeJob = fn () => new SendProposalViewedNotificationJob(
            proposalId:     $proposal->id,
            viewerIp:       $ip,
            viewedAt:       now()->utc()->format('M j, Y \a\t g:i A'),
            viewerTimezone: 'UTC',
        );

        // First view
        $makeJob()->handle();
        Notification::assertSentTo($rep, ProposalViewedNotification::class, 1);

        // Simulate expiry: manually remove the throttle cache key
        Cache::forget("proposal_view_notif:{$proposal->id}:{$ip}");

        // View again after window expiry → new notification
        $makeJob()->handle();
        Notification::assertSentTo($rep, ProposalViewedNotification::class, 2);
    }

    /** @test */
    public function zero_throttle_window_sends_notification_on_every_view(): void
    {
        Notification::fake();
        Config::set('notifications.proposal_view_notify_throttle', 0);

        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);
        $ip       = '3.3.3.3';

        $makeJob = fn () => new SendProposalViewedNotificationJob(
            proposalId:     $proposal->id,
            viewerIp:       $ip,
            viewedAt:       now()->utc()->format('M j, Y \a\t g:i A'),
            viewerTimezone: 'UTC',
        );

        $makeJob()->handle();
        $makeJob()->handle();
        $makeJob()->handle();

        // 3 views with zero throttle → 3 notifications
        Notification::assertSentTo($rep, ProposalViewedNotification::class, 3);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-17/18: Throttle is per-proposal and per-IP
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function different_proposals_have_independent_throttle_keys(): void
    {
        Notification::fake();
        Config::set('notifications.proposal_view_notify_throttle', 30);

        $rep       = $this->salesRep();
        $proposalA = $this->sentProposal($rep, ['proposal_title' => 'Proposal A']);
        $proposalB = $this->sentProposal($rep, ['proposal_title' => 'Proposal B']);
        $ip        = '7.7.7.7';

        $makeJob = fn (int $proposalId) => new SendProposalViewedNotificationJob(
            proposalId:     $proposalId,
            viewerIp:       $ip,
            viewedAt:       now()->utc()->format('M j, Y \a\t g:i A'),
            viewerTimezone: 'UTC',
        );

        // View both proposals — each must fire its own notification
        $makeJob($proposalA->id)->handle();
        $makeJob($proposalB->id)->handle();

        // 2 distinct proposals → 2 notifications (AC-17)
        Notification::assertSentTo($rep, ProposalViewedNotification::class, 2);
    }

    /** @test */
    public function different_viewer_ips_on_same_proposal_each_trigger_notification(): void
    {
        Notification::fake();
        Config::set('notifications.proposal_view_notify_throttle', 30);

        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);

        $makeJob = fn (string $ip) => new SendProposalViewedNotificationJob(
            proposalId:     $proposal->id,
            viewerIp:       $ip,
            viewedAt:       now()->utc()->format('M j, Y \a\t g:i A'),
            viewerTimezone: 'UTC',
        );

        // Two distinct IPs viewing the same proposal — AC-18
        $makeJob('10.0.0.1')->handle();
        $makeJob('10.0.0.2')->handle();

        Notification::assertSentTo($rep, ProposalViewedNotification::class, 2);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-7/8/9/10/19: Notification content
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function notification_email_contains_proposal_title_and_client_info(): void
    {
        Notification::fake();
        Config::set('notifications.proposal_view_notify_throttle', 0);

        $rep = $this->salesRep();
        $proposal = Proposal::factory()->ownedBy($rep)->create([
            'status'         => 'Sent',
            'proposal_title' => 'Enterprise Platform Deal',
            'client_name'    => 'Acme Corp',
            'client_email'   => 'ceo@acme.com',
        ]);

        $job = new SendProposalViewedNotificationJob(
            proposalId:     $proposal->id,
            viewerIp:       '1.1.1.1',
            viewedAt:       'May 14, 2026 at 10:30 AM',
            viewerTimezone: 'UTC',
        );
        $job->handle();

        Notification::assertSentTo(
            $rep,
            ProposalViewedNotification::class,
            function (ProposalViewedNotification $notification) use ($rep) {
                $mail = $notification->toMail($rep);

                // AC-19: subject includes proposal name and client name
                $this->assertStringContainsString('Enterprise Platform Deal', $mail->subject);
                $this->assertStringContainsString('Acme Corp', $mail->subject);

                // AC-7: proposal name in body
                $introLines = implode(' ', array_map(
                    fn ($line) => is_string($line) ? $line : ($line->message ?? ''),
                    $mail->introLines
                ));
                $this->assertStringContainsString('Enterprise Platform Deal', $introLines);

                // AC-8: client info
                $this->assertStringContainsString('Acme Corp', $introLines);
                $this->assertStringContainsString('ceo@acme.com', $introLines);

                // AC-9: timestamp
                $this->assertStringContainsString('May 14, 2026', $introLines);

                return true;
            }
        );
    }

    /** @test */
    public function notification_email_contains_deep_link_to_proposal(): void
    {
        Notification::fake();
        Config::set('notifications.proposal_view_notify_throttle', 0);

        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);

        $job = new SendProposalViewedNotificationJob(
            proposalId:     $proposal->id,
            viewerIp:       '2.2.2.2',
            viewedAt:       now()->utc()->format('M j, Y \a\t g:i A'),
            viewerTimezone: 'UTC',
        );
        $job->handle();

        Notification::assertSentTo(
            $rep,
            ProposalViewedNotification::class,
            function (ProposalViewedNotification $notification) use ($rep, $proposal) {
                $mail = $notification->toMail($rep);
                // AC-10: action URL is the proposals.show route
                $this->assertStringContainsString(
                    route('proposals.show', $proposal),
                    $mail->actionUrl
                );
                return true;
            }
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-12: No Sales Rep assigned
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function job_skips_gracefully_when_proposal_does_not_exist(): void
    {
        Notification::fake();

        $job = new SendProposalViewedNotificationJob(
            proposalId:     999999,
            viewerIp:       '1.1.1.1',
            viewedAt:       now()->utc()->format('M j, Y \a\t g:i A'),
            viewerTimezone: 'UTC',
        );

        // Must not throw
        $job->handle();

        Notification::assertNothingSent();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-25: Invalid rep email
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function job_skips_when_rep_email_is_invalid(): void
    {
        Notification::fake();
        Config::set('notifications.proposal_view_notify_throttle', 0);

        $rep = User::factory()->sales()->create(['email' => 'not-a-valid-email']);
        $proposal = $this->sentProposal($rep);

        $job = new SendProposalViewedNotificationJob(
            proposalId:     $proposal->id,
            viewerIp:       '1.2.3.4',
            viewedAt:       now()->utc()->format('M j, Y \a\t g:i A'),
            viewerTimezone: 'UTC',
        );
        $job->handle();

        Notification::assertNothingSent();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-27: Exactly one notification dispatched per view event
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function exactly_one_job_dispatched_per_human_view_event(): void
    {
        Queue::fake();

        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);

        $this->withServerVariables(['REMOTE_ADDR' => '1.2.3.4'])
             ->get($this->publicUrl($proposal));

        // Exactly one job — not zero, not two (AC-27)
        Queue::assertPushed(SendProposalViewedNotificationJob::class, 1);
    }

    /** @test */
    public function draft_proposal_view_does_not_dispatch_notification_job(): void
    {
        Queue::fake();

        $rep      = $this->salesRep();
        $proposal = Proposal::factory()->ownedBy($rep)->create(['status' => 'Draft']);

        $this->get($this->publicUrl($proposal));

        // Draft proposals show the "not yet shared" page without recording a view
        Queue::assertNothingPushed();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Read a private property from a job for assertion purposes.
     */
    private function getPrivate(object $object, string $property): mixed
    {
        $ref = new \ReflectionClass($object);
        $prop = $ref->getProperty($property);
        $prop->setAccessible(true);
        return $prop->getValue($object);
    }
}
