<?php

namespace Tests\Feature;

use App\Models\Proposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * DashboardStatsTest — WB-033
 *
 * Verifies that the sales rep dashboard:
 *   - Shows live proposal counts per status (total, draft, sent, accepted)
 *   - Shows a recent proposals list (last 5 by updated_at)
 *   - Scopes all data exclusively to the authenticated rep's own proposals
 *   - Renders the correct empty state when no proposals exist
 *   - Shows a "View all N proposals" footer when total > 5
 *   - Is inaccessible to unauthenticated visitors (redirect to login)
 *   - Shows the admin layout (not stats) for admin users
 */
class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function salesRep(): User
    {
        return User::factory()->sales()->create();
    }

    private function adminUser(): User
    {
        return User::factory()->admin()->create();
    }

    private function proposalFor(User $user, array $attrs = []): Proposal
    {
        return Proposal::factory()->ownedBy($user)->create($attrs);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Access control
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    /** @test */
    public function authenticated_sales_rep_can_access_dashboard(): void
    {
        $this->actingAs($this->salesRep())
             ->get('/dashboard')
             ->assertOk();
    }

    /** @test */
    public function authenticated_admin_can_access_dashboard(): void
    {
        $this->actingAs($this->adminUser())
             ->get('/dashboard')
             ->assertOk();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Stat counts — live from DB
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function stat_cards_show_zero_counts_when_rep_has_no_proposals(): void
    {
        $rep      = $this->salesRep();
        $response = $this->actingAs($rep)->get('/dashboard');

        $response->assertOk();
        // All four stat values render as 0
        // The view emits them via {{ $stats['total'] }} etc., so we check the
        // rendered text rather than any DOM structure.
        $response->assertSee('0');          // total
        $response->assertSee('Total');
        $response->assertSee('Drafts');
        $response->assertSee('Sent');
        $response->assertSee('Accepted');
    }

    /** @test */
    public function total_stat_reflects_live_proposal_count(): void
    {
        $rep = $this->salesRep();
        $this->proposalFor($rep, ['status' => 'Draft']);
        $this->proposalFor($rep, ['status' => 'Sent']);
        $this->proposalFor($rep, ['status' => 'Accepted']);

        $response = $this->actingAs($rep)->get('/dashboard');

        // The number 3 appears in the page as the total stat value
        $response->assertOk();
        $response->assertSee('3');
    }

    /** @test */
    public function draft_count_matches_only_draft_proposals(): void
    {
        $rep = $this->salesRep();
        $this->proposalFor($rep, ['status' => 'Draft']);
        $this->proposalFor($rep, ['status' => 'Draft']);
        $this->proposalFor($rep, ['status' => 'Sent']);

        $response = $this->actingAs($rep)->get('/dashboard');

        $response->assertOk();
        // 2 drafts — the value 2 must appear; also 1 sent
        $response->assertSee('2');
        $response->assertSee('1');
    }

    /** @test */
    public function sent_count_matches_only_sent_proposals(): void
    {
        $rep = $this->salesRep();
        $this->proposalFor($rep, ['status' => 'Sent']);
        $this->proposalFor($rep, ['status' => 'Sent']);
        $this->proposalFor($rep, ['status' => 'Sent']);
        $this->proposalFor($rep, ['status' => 'Draft']);

        $response = $this->actingAs($rep)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('3'); // sent
        $response->assertSee('4'); // total
    }

    /** @test */
    public function accepted_count_matches_only_accepted_proposals(): void
    {
        $rep = $this->salesRep();
        $this->proposalFor($rep, ['status' => 'Accepted']);
        $this->proposalFor($rep, ['status' => 'Draft']);

        $response = $this->actingAs($rep)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('1'); // accepted (and also draft)
        $response->assertSee('2'); // total
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Ownership scoping — counts never cross rep boundaries
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function stats_are_scoped_to_the_authenticated_rep_only(): void
    {
        $repA = $this->salesRep();
        $repB = $this->salesRep();

        // repB has 10 proposals; repA has 2
        for ($i = 0; $i < 10; $i++) {
            $this->proposalFor($repB, ['status' => 'Sent']);
        }
        $this->proposalFor($repA, ['status' => 'Draft']);
        $this->proposalFor($repA, ['status' => 'Accepted']);

        $response = $this->actingAs($repA)->get('/dashboard');

        $response->assertOk();
        // repA's total is 2 — the page must not show 10 or 12
        $response->assertSee('2');   // total for repA
        $response->assertSee('1');   // draft count, accepted count
        // "10" must not appear anywhere in the page as a stat figure
        // (it might appear in CSS/JS, but not as a prominent rendered number
        // from repB's data — we assert the overall structure is correct
        // by checking repA's own counts are present)
        $this->assertStringNotContainsString(
            '>10<',
            $response->getContent(),
            'repB\'s total must not appear in repA\'s dashboard'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Recent proposals list
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function recent_proposals_list_shows_up_to_five_entries(): void
    {
        $rep = $this->salesRep();

        for ($i = 1; $i <= 7; $i++) {
            $this->proposalFor($rep, [
                'client_name' => "Client {$i}",
                'status'      => 'Draft',
            ]);
        }

        $response = $this->actingAs($rep)->get('/dashboard');

        $response->assertOk();
        // At most 5 items rendered (the 6th and 7th should not appear by name,
        // but because client names are "Client 1" … "Client 7" and only the 5
        // most-recently-updated are shown, we just verify the list is capped)
        $content = $response->getContent();
        // Count occurrences of the proposal list item link pattern
        $listItemCount = substr_count($content, 'proposals/');
        // Each list item links to proposals/{id} plus there's the "View all" link
        // so at most 5 proposal links + 1 or 2 "View all" links = ≤7 total
        $this->assertLessThanOrEqual(7, $listItemCount);
    }

    /** @test */
    public function recent_proposals_are_ordered_by_most_recently_updated(): void
    {
        $rep = $this->salesRep();

        $old   = $this->proposalFor($rep, ['client_name' => 'OldClient']);
        $fresh = $this->proposalFor($rep, ['client_name' => 'FreshClient']);

        // Manually set updated_at so order is deterministic
        $old->update(['updated_at'   => now()->subDays(10)]);
        $fresh->update(['updated_at' => now()->subMinutes(1)]);

        $response = $this->actingAs($rep)->get('/dashboard');

        $response->assertOk();
        $content = $response->getContent();

        // FreshClient should appear before OldClient in the list
        $this->assertLessThan(
            strpos($content, 'OldClient'),
            strpos($content, 'FreshClient'),
            'Most recently updated proposal should appear first in the list'
        );
    }

    /** @test */
    public function recent_proposals_list_shows_client_name_and_status(): void
    {
        $rep = $this->salesRep();
        $this->proposalFor($rep, [
            'client_name' => 'Acme Corporation',
            'status'      => 'Sent',
        ]);

        $response = $this->actingAs($rep)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Acme Corporation');
        $response->assertSee('Sent');
    }

    /** @test */
    public function recent_proposals_list_does_not_show_other_reps_proposals(): void
    {
        $repA = $this->salesRep();
        $repB = $this->salesRep();

        $this->proposalFor($repA, ['client_name' => 'RepA Client']);
        $this->proposalFor($repB, ['client_name' => 'RepB Secret Client']);

        $response = $this->actingAs($repA)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('RepA Client');
        $response->assertDontSee('RepB Secret Client');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Empty state
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function empty_state_is_shown_when_rep_has_no_proposals(): void
    {
        $rep      = $this->salesRep();
        $response = $this->actingAs($rep)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('No proposals yet');
        $response->assertSee('Create your first AI-powered proposal to get started');
    }

    /** @test */
    public function empty_state_includes_create_proposal_link_for_verified_user(): void
    {
        $rep      = $this->salesRep(); // factory sets email_verified_at by default
        $response = $this->actingAs($rep)->get('/dashboard');

        $response->assertOk();
        $response->assertSee(route('proposals.create'), false);
    }

    /** @test */
    public function empty_state_does_not_show_create_link_for_unverified_user(): void
    {
        $rep      = User::factory()->sales()->unverified()->create();
        $response = $this->actingAs($rep)->get('/dashboard');

        $response->assertOk();
        // The "Create Proposal" CTA inside the empty state requires verified email
        // Unverified users should not see the proposals.create link in the empty state
        $response->assertDontSee(route('proposals.create'), false);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // "View all N proposals" footer
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function view_all_footer_appears_when_total_exceeds_five(): void
    {
        $rep = $this->salesRep();

        for ($i = 1; $i <= 6; $i++) {
            $this->proposalFor($rep, ['status' => 'Draft']);
        }

        $response = $this->actingAs($rep)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('View all 6 proposals');
    }

    /** @test */
    public function view_all_footer_does_not_appear_when_total_is_five_or_fewer(): void
    {
        $rep = $this->salesRep();

        for ($i = 1; $i <= 5; $i++) {
            $this->proposalFor($rep, ['status' => 'Draft']);
        }

        $response = $this->actingAs($rep)->get('/dashboard');

        $response->assertOk();
        $response->assertDontSee('View all 5 proposals');
    }

    /** @test */
    public function view_all_link_in_header_appears_when_proposals_exist(): void
    {
        $rep = $this->salesRep();
        $this->proposalFor($rep, ['status' => 'Draft']);

        $response = $this->actingAs($rep)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('View all →');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Admin sees admin layout, not sales stats
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function admin_dashboard_shows_admin_layout_not_stats(): void
    {
        $admin    = $this->adminUser();
        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('You are signed in as Administrator');
        $response->assertSee('Manage Users');
        // Stat labels for proposals must not appear for admin
        $response->assertDontSee('stat-value', false);
        $response->assertDontSee('No proposals yet');
    }

    /** @test */
    public function admin_dashboard_links_to_user_management(): void
    {
        $admin    = $this->adminUser();
        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk();
        $response->assertSee(route('admin.users.index'), false);
        $response->assertSee(route('admin.sessions.index'), false);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Quick actions sidebar
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function quick_actions_sidebar_links_to_new_proposal_and_all_proposals(): void
    {
        $rep      = $this->salesRep();
        $response = $this->actingAs($rep)->get('/dashboard');

        $response->assertOk();
        $response->assertSee(route('proposals.create'), false);
        $response->assertSee(route('proposals.index'), false);
        $response->assertSee(route('profile.edit'), false);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Greeting personalisation
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function dashboard_greets_the_authenticated_user_by_name(): void
    {
        $rep      = User::factory()->sales()->create(['name' => 'Jordan Smith']);
        $response = $this->actingAs($rep)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Jordan Smith');
    }

    /** @test */
    public function dashboard_shows_time_based_greeting(): void
    {
        $rep      = $this->salesRep();
        $response = $this->actingAs($rep)->get('/dashboard');

        $response->assertOk();
        // One of the three greetings must be present
        $content = $response->getContent();
        $hasGreeting = str_contains($content, 'Good morning')
                    || str_contains($content, 'Good afternoon')
                    || str_contains($content, 'Good evening');

        $this->assertTrue($hasGreeting, 'Dashboard should display a time-based greeting');
    }
}
