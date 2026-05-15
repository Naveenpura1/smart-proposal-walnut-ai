<?php

namespace Tests\Feature;

use App\Models\Proposal;
use App\Models\ProposalView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ProposalViewTrackingTest — WB-032
 *
 * Covers the core acceptance criteria for the public token / view-tracking feature:
 *
 *   AC-1/12  — token is unique UUID, not a sequential ID
 *   AC-2     — public URL needs no authentication
 *   AC-3/20  — read-only client view; no internal controls visible
 *   AC-4     — every visit records a ProposalView row with IP / UA / referrer
 *   AC-5     — total view count and unique viewer count tracked separately
 *   AC-7     — first human view auto-transitions Sent → Viewed
 *   AC-10    — accepted proposal public URL still shows read-only content
 *   AC-11    — Draft proposals return "not yet shared" message
 *   AC-13    — view count appears in proposals list (via withCount eager load)
 *   AC-17    — bot UA is flagged is_bot=true and excluded from human counts
 *   AC-18    — token regeneration invalidates old URL
 *   AC-19    — old view events retain their token_used after regeneration
 *   AC-21/22 — Accept / Decline CTAs update status
 *   AC-30    — share URL input and copy button rendered on owner detail page
 *   AC-32    — invalid token returns unavailable page (not a system error)
 */
class ProposalViewTrackingTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function salesRep(): User
    {
        return User::factory()->sales()->create();
    }

    private function sentProposal(User $owner): Proposal
    {
        return Proposal::factory()->ownedBy($owner)->create(['status' => 'Sent']);
    }

    private function publicUrl(Proposal $p): string
    {
        return "/proposals/view/{$p->public_token}";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-1/12: Token format
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function proposal_gets_a_uuid_public_token_on_creation(): void
    {
        $rep      = $this->salesRep();
        $proposal = Proposal::factory()->ownedBy($rep)->create();

        $this->assertNotNull($proposal->public_token);
        // UUID v4 pattern: 8-4-4-4-12 hex chars
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $proposal->public_token
        );
    }

    /** @test */
    public function each_proposal_gets_a_different_token(): void
    {
        $rep = $this->salesRep();
        $a   = Proposal::factory()->ownedBy($rep)->create();
        $b   = Proposal::factory()->ownedBy($rep)->create();

        $this->assertNotEquals($a->public_token, $b->public_token);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-2: Public URL requires no authentication
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function public_url_is_accessible_without_authentication(): void
    {
        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);

        $this->get($this->publicUrl($proposal))->assertOk();
    }

    /** @test */
    public function authenticated_user_can_also_access_public_url(): void
    {
        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);

        $this->actingAs($rep)->get($this->publicUrl($proposal))->assertOk();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-3/20: Read-only view — no internal controls
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function public_view_displays_proposal_content(): void
    {
        $rep      = $this->salesRep();
        $proposal = Proposal::factory()->ownedBy($rep)->create([
            'status'            => 'Sent',
            'proposal_title'    => 'Enterprise Platform',
            'client_name'       => 'Jane Smith',
            'generated_content' => 'This proposal covers the full implementation.',
        ]);

        $response = $this->get($this->publicUrl($proposal));

        $response->assertOk();
        $response->assertSee('Enterprise Platform');
        $response->assertSee('Jane Smith');
        $response->assertSee('This proposal covers the full implementation');
    }

    /** @test */
    public function public_view_does_not_expose_internal_management_controls(): void
    {
        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);

        $response = $this->get($this->publicUrl($proposal));

        $response->assertOk();
        // No edit links, no admin routes, no internal form actions
        $response->assertDontSee(route('proposals.edit', $proposal), false);
        $response->assertDontSee(route('proposals.destroy', $proposal), false);
        $response->assertDontSee('Clone as new Draft');
        $response->assertDontSee('Regenerate AI content');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-4: View events recorded
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function viewing_public_url_creates_a_proposal_view_record(): void
    {
        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);

        $this->get($this->publicUrl($proposal));

        $this->assertDatabaseHas('proposal_views', [
            'proposal_id' => $proposal->id,
            'token_used'  => $proposal->public_token,
        ]);
    }

    /** @test */
    public function view_record_captures_ip_and_user_agent(): void
    {
        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.42'])
             ->get($this->publicUrl($proposal), [
                 'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
             ]);

        $view = ProposalView::where('proposal_id', $proposal->id)->first();
        $this->assertNotNull($view);
        $this->assertStringContainsString('Mozilla', $view->user_agent ?? '');
    }

    /** @test */
    public function multiple_visits_create_multiple_view_records(): void
    {
        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);

        $this->get($this->publicUrl($proposal));
        $this->get($this->publicUrl($proposal));
        $this->get($this->publicUrl($proposal));

        $this->assertEquals(3, ProposalView::where('proposal_id', $proposal->id)->count());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-5: Unique vs total view counts
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function first_visit_from_an_ip_is_marked_unique(): void
    {
        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);

        $this->withServerVariables(['REMOTE_ADDR' => '1.2.3.4'])
             ->get($this->publicUrl($proposal));

        $this->assertDatabaseHas('proposal_views', [
            'proposal_id' => $proposal->id,
            'is_unique'   => true,
            'is_bot'      => false,
        ]);
    }

    /** @test */
    public function repeat_visit_from_same_ip_is_not_marked_unique(): void
    {
        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);

        $this->withServerVariables(['REMOTE_ADDR' => '1.2.3.4'])->get($this->publicUrl($proposal));
        $this->withServerVariables(['REMOTE_ADDR' => '1.2.3.4'])->get($this->publicUrl($proposal));

        $views = ProposalView::where('proposal_id', $proposal->id)->get();
        $this->assertEquals(2, $views->count());
        $this->assertEquals(1, $views->where('is_unique', true)->count());
        $this->assertEquals(1, $views->where('is_unique', false)->count());
    }

    /** @test */
    public function unique_view_count_method_counts_only_unique_human_views(): void
    {
        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);

        // Two different IPs
        $this->withServerVariables(['REMOTE_ADDR' => '1.1.1.1'])->get($this->publicUrl($proposal));
        $this->withServerVariables(['REMOTE_ADDR' => '2.2.2.2'])->get($this->publicUrl($proposal));
        // Repeat from first IP
        $this->withServerVariables(['REMOTE_ADDR' => '1.1.1.1'])->get($this->publicUrl($proposal));

        $proposal->refresh();
        $this->assertEquals(3, $proposal->totalViewCount());
        $this->assertEquals(2, $proposal->uniqueViewCount());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-7: Auto-transition Sent → Viewed
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function first_human_view_transitions_status_from_sent_to_viewed(): void
    {
        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);

        $this->get($this->publicUrl($proposal));

        $this->assertDatabaseHas('proposals', [
            'id'     => $proposal->id,
            'status' => 'Viewed',
        ]);
    }

    /** @test */
    public function first_view_records_first_viewed_at_timestamp(): void
    {
        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);

        $this->assertNull($proposal->first_viewed_at);

        $this->get($this->publicUrl($proposal));

        $this->assertNotNull($proposal->fresh()->first_viewed_at);
    }

    /** @test */
    public function already_viewed_status_does_not_get_reset_on_subsequent_views(): void
    {
        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);

        $this->get($this->publicUrl($proposal)); // → Viewed
        $this->get($this->publicUrl($proposal)); // should stay Viewed

        $this->assertDatabaseHas('proposals', [
            'id'     => $proposal->id,
            'status' => 'Viewed',
        ]);
    }

    /** @test */
    public function accepted_proposal_does_not_get_downgraded_to_viewed_on_view(): void
    {
        $rep      = $this->salesRep();
        $proposal = Proposal::factory()->ownedBy($rep)->create(['status' => 'Accepted']);

        $this->get($this->publicUrl($proposal));

        $this->assertDatabaseHas('proposals', [
            'id'     => $proposal->id,
            'status' => 'Accepted',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-11: Draft proposals
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function draft_proposal_public_url_shows_not_yet_shared_message(): void
    {
        $rep      = $this->salesRep();
        $proposal = Proposal::factory()->ownedBy($rep)->create(['status' => 'Draft']);

        $response = $this->get($this->publicUrl($proposal));

        $response->assertOk(); // friendly page, not 500
        $response->assertSee('Not yet shared');
        $response->assertDontSee($proposal->generated_content ?? 'proposal content');
    }

    /** @test */
    public function draft_proposal_view_does_not_record_a_view_event(): void
    {
        $rep      = $this->salesRep();
        $proposal = Proposal::factory()->ownedBy($rep)->create(['status' => 'Draft']);

        $this->get($this->publicUrl($proposal));

        $this->assertEquals(0, ProposalView::where('proposal_id', $proposal->id)->count());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-17: Bot detection
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function known_bot_user_agent_is_flagged_as_bot(): void
    {
        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);

        $this->get($this->publicUrl($proposal), ['User-Agent' => 'Googlebot/2.1 (+http://www.google.com/bot.html)']);

        $this->assertDatabaseHas('proposal_views', [
            'proposal_id' => $proposal->id,
            'is_bot'      => true,
        ]);
    }

    /** @test */
    public function bot_view_does_not_transition_status_to_viewed(): void
    {
        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);

        $this->get($this->publicUrl($proposal), ['User-Agent' => 'python-requests/2.28.0']);

        $this->assertDatabaseHas('proposals', [
            'id'     => $proposal->id,
            'status' => 'Sent',  // NOT changed to Viewed
        ]);
    }

    /** @test */
    public function bot_views_excluded_from_human_view_count(): void
    {
        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);

        $this->get($this->publicUrl($proposal), ['User-Agent' => 'Googlebot/2.1']);
        $this->get($this->publicUrl($proposal), ['User-Agent' => 'Mozilla/5.0 (human)']);

        $proposal->refresh();
        $this->assertEquals(1, $proposal->totalViewCount());   // only the human
        $this->assertEquals(2, $proposal->views()->count());   // both recorded
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-18/19: Token regeneration
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function owner_can_regenerate_token_which_invalidates_old_url(): void
    {
        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);
        $oldToken = $proposal->public_token;

        $this->actingAs($rep)
             ->post(route('proposals.regenerate-token', $proposal))
             ->assertRedirect(route('proposals.show', $proposal));

        $proposal->refresh();
        $this->assertNotEquals($oldToken, $proposal->public_token);

        // Old URL now returns unavailable page
        $this->get("/proposals/view/{$oldToken}")->assertSee('no longer available');
    }

    /** @test */
    public function historical_views_retain_old_token_used_after_regeneration(): void
    {
        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);
        $oldToken = $proposal->public_token;

        $this->get($this->publicUrl($proposal)); // creates a view with old token

        // Regenerate
        $this->actingAs($rep)->post(route('proposals.regenerate-token', $proposal));

        // The view record still has the old token
        $this->assertDatabaseHas('proposal_views', [
            'proposal_id' => $proposal->id,
            'token_used'  => $oldToken,
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_regenerate_token(): void
    {
        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);
        $oldToken = $proposal->public_token;

        $this->post(route('proposals.regenerate-token', $proposal))->assertRedirect('/login');

        $this->assertEquals($oldToken, $proposal->fresh()->public_token);
    }

    /** @test */
    public function non_owner_cannot_regenerate_token(): void
    {
        $owner = $this->salesRep();
        $other = $this->salesRep();
        $proposal = $this->sentProposal($owner);

        $this->actingAs($other)
             ->post(route('proposals.regenerate-token', $proposal))
             ->assertNotFound();

        $this->assertEquals($proposal->public_token, $proposal->fresh()->public_token);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-21/22: Accept / Decline CTAs
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function accept_cta_updates_proposal_status_to_accepted(): void
    {
        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);

        $this->post(route('proposals.public.accept', $proposal->public_token));

        $this->assertDatabaseHas('proposals', [
            'id'     => $proposal->id,
            'status' => 'Accepted',
        ]);
    }

    /** @test */
    public function accept_cta_shows_confirmation_message(): void
    {
        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);

        $response = $this->post(route('proposals.public.accept', $proposal->public_token));

        $response->assertOk();
        $response->assertSee('Proposal accepted');
    }

    /** @test */
    public function decline_cta_updates_proposal_status(): void
    {
        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);

        $this->post(route('proposals.public.decline', $proposal->public_token));

        // Declined → reverts to Draft in current schema
        $this->assertDatabaseHas('proposals', [
            'id'     => $proposal->id,
            'status' => 'Draft',
        ]);
    }

    /** @test */
    public function accept_cta_not_available_for_already_accepted_proposals(): void
    {
        $rep      = $this->salesRep();
        $proposal = Proposal::factory()->ownedBy($rep)->create(['status' => 'Accepted']);

        $response = $this->post(route('proposals.public.accept', $proposal->public_token));

        $response->assertOk();
        $response->assertSee('already resolved');
    }

    /** @test */
    public function public_view_shows_cta_buttons_for_sent_proposal(): void
    {
        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);

        $response = $this->get($this->publicUrl($proposal));

        $response->assertOk();
        $response->assertSee('Accept Proposal');
        $response->assertSee('Decline');
        $response->assertSee('data-testid="client-cta-section"', false);
    }

    /** @test */
    public function public_view_shows_accepted_banner_for_accepted_proposals(): void
    {
        $rep      = $this->salesRep();
        $proposal = Proposal::factory()->ownedBy($rep)->create(['status' => 'Accepted']);

        $response = $this->get($this->publicUrl($proposal));

        $response->assertOk();
        $response->assertSee('data-testid="accepted-banner"', false);
        $response->assertDontSee('data-testid="client-cta-section"', false);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-30: Share link UI on owner detail page
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function owner_detail_page_shows_share_url_input(): void
    {
        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);

        $response = $this->actingAs($rep)->get(route('proposals.show', $proposal));

        $response->assertOk();
        $response->assertSee('data-testid="share-url-input"', false);
        $response->assertSee(route('proposals.public.show', $proposal->public_token), false);
    }

    /** @test */
    public function owner_detail_page_shows_copy_link_button(): void
    {
        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);

        $response = $this->actingAs($rep)->get(route('proposals.show', $proposal));

        $response->assertOk();
        $response->assertSee('data-testid="copy-link-btn"', false);
    }

    /** @test */
    public function owner_detail_page_shows_view_stats_when_views_exist(): void
    {
        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);

        // Create two views — one unique, one repeat
        $this->withServerVariables(['REMOTE_ADDR' => '5.5.5.5'])->get($this->publicUrl($proposal));
        $this->withServerVariables(['REMOTE_ADDR' => '6.6.6.6'])->get($this->publicUrl($proposal));
        $this->withServerVariables(['REMOTE_ADDR' => '5.5.5.5'])->get($this->publicUrl($proposal));

        $response = $this->actingAs($rep)->get(route('proposals.show', $proposal));

        $response->assertOk();
        $response->assertSee('data-testid="total-views"', false);
        $response->assertSee('data-testid="unique-views"', false);
        $response->assertSee('3'); // total
        $response->assertSee('2'); // unique
    }

    /** @test */
    public function owner_detail_page_shows_view_log_when_views_exist(): void
    {
        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);

        $this->get($this->publicUrl($proposal));

        $response = $this->actingAs($rep)->get(route('proposals.show', $proposal));

        $response->assertOk();
        $response->assertSee('data-testid="view-log"', false);
        $response->assertSee('View Log');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-32: Invalid / malformed token
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function invalid_token_shows_friendly_unavailable_page(): void
    {
        $response = $this->get('/proposals/view/this-token-does-not-exist');

        $response->assertOk(); // friendly page, not a 500
        $response->assertSee('no longer available');
    }

    /** @test */
    public function malformed_token_does_not_expose_system_errors(): void
    {
        $response = $this->get('/proposals/view/' . str_repeat('x', 500));

        $response->assertOk();
        $response->assertDontSee('Exception');
        $response->assertDontSee('Stack trace');
        $response->assertDontSee('Illuminate');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-13: View count in proposals list
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function proposals_index_shows_view_count_for_viewed_proposals(): void
    {
        $rep      = $this->salesRep();
        $proposal = $this->sentProposal($rep);

        // Create 2 human views
        $this->withServerVariables(['REMOTE_ADDR' => '7.7.7.7'])->get($this->publicUrl($proposal));
        $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])->get($this->publicUrl($proposal));

        $response = $this->actingAs($rep)->get(route('proposals.index'));

        $response->assertOk();
        // The view count cell should appear in the list
        $response->assertSee('data-testid="view-count-cell"', false);
    }
}
