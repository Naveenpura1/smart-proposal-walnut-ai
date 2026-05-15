<?php

namespace Tests\Feature;

use App\Models\Proposal;
use App\Models\ProposalView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AdminAnalyticsTest — WB-029 / WB-030
 *
 * Covers all testable acceptance criteria for the Admin Analytics Dashboard:
 *
 *   AC-1   Access control — admin-only, reps get 403
 *   AC-2   Platform KPI counts (total, draft, sent, accepted)
 *   AC-3   Conversion rate calculation
 *   AC-4/5 Date-range filter (presets + custom range)
 *   AC-6   Rep performance table columns present
 *   AC-7   Rep table sortable by column
 *   AC-8   Rep table searchable by name
 *   AC-9   Per-proposal breakdown columns (ID, title, rep, client, status, deal)
 *   AC-10  Proposal filter by status
 *   AC-11  Proposal filter by rep
 *   AC-12  Proposal table paginated (25 per page)
 *   AC-13  Proposal rows are clickable (href present)
 *   AC-16  Empty state when no proposals match filter
 *   AC-17  Deal value formatted with currency symbol
 *   AC-18  CSV export for proposals returns valid CSV
 *   AC-19  CSV export for reps returns valid CSV
 *   AC-21  Last-updated timestamp displayed
 *   AC-22  Avg time-to-acceptance shown (platform + per-rep)
 *   AC-24  No cross-tenant data (scoped to all proposals in single-tenant app)
 *   AC-25  Auth + role-based protection
 */
class AdminAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function salesRep(string $name = ''): User
    {
        return User::factory()->sales()->create($name ? ['name' => $name] : []);
    }

    private function proposal(User $owner, string $status = 'Draft', array $extra = []): Proposal
    {
        return Proposal::factory()->ownedBy($owner)->create(array_merge(['status' => $status], $extra));
    }

    private function humanView(Proposal $p): void
    {
        ProposalView::create([
            'proposal_id' => $p->id,
            'token_used'  => $p->public_token,
            'ip_address'  => '1.2.3.4',
            'user_agent'  => 'Mozilla/5.0',
            'is_bot'      => false,
            'is_unique'   => true,
            'viewed_at'   => now(),
        ]);
    }

    private function botView(Proposal $p): void
    {
        ProposalView::create([
            'proposal_id' => $p->id,
            'token_used'  => $p->public_token,
            'ip_address'  => '9.9.9.9',
            'user_agent'  => 'Googlebot/2.1',
            'is_bot'      => true,
            'is_unique'   => false,
            'viewed_at'   => now(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-1/25: Access control
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function analytics_page_requires_authentication(): void
    {
        $this->get(route('admin.analytics'))->assertRedirect('/login');
    }

    /** @test */
    public function sales_rep_cannot_access_analytics_page(): void
    {
        $this->actingAs($this->salesRep())->get(route('admin.analytics'))->assertForbidden();
    }

    /** @test */
    public function admin_can_access_analytics_page(): void
    {
        $this->actingAs($this->admin())->get(route('admin.analytics'))->assertOk();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-2: Platform KPI counts
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function kpi_total_shows_all_proposals_across_all_reps(): void
    {
        $repA = $this->salesRep();
        $repB = $this->salesRep();
        $this->proposal($repA, 'Draft');
        $this->proposal($repA, 'Sent');
        $this->proposal($repB, 'Accepted');

        $response = $this->actingAs($this->admin())->get(route('admin.analytics'));

        $response->assertOk();
        $response->assertSee('data-testid="kpi-total"', false);
        $response->assertSeeText('3');
    }

    /** @test */
    public function kpi_shows_draft_and_sent_sub_counts(): void
    {
        $rep = $this->salesRep();
        $this->proposal($rep, 'Draft');
        $this->proposal($rep, 'Draft');
        $this->proposal($rep, 'Sent');

        $response = $this->actingAs($this->admin())->get(route('admin.analytics'));

        $response->assertOk();
        $response->assertSeeText('2 draft');
        $response->assertSeeText('1 sent');
    }

    /** @test */
    public function zero_data_state_renders_without_errors(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.analytics'));

        $response->assertOk();
        $response->assertDontSee('Exception', false);
        $response->assertDontSee('Stack trace', false);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-3: Conversion rate
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function conversion_rate_is_accepted_over_sent_percentage(): void
    {
        $rep = $this->salesRep();
        $this->proposal($rep, 'Sent');
        $this->proposal($rep, 'Sent');
        $this->proposal($rep, 'Accepted');
        $this->proposal($rep, 'Accepted');

        // sent + accepted = 4 sent-or-beyond; accepted = 2 → 50%
        $response = $this->actingAs($this->admin())->get(route('admin.analytics'));

        $response->assertOk();
        $response->assertSee('data-testid="kpi-conversion"', false);
        $response->assertSeeText('50');
    }

    /** @test */
    public function conversion_rate_is_zero_when_nothing_sent(): void
    {
        $rep = $this->salesRep();
        $this->proposal($rep, 'Draft');

        $response = $this->actingAs($this->admin())->get(route('admin.analytics'));

        $response->assertOk();
        // 0% conversion
        $response->assertSeeText('0');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-4/5: Date-range filter
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function date_preset_filter_is_rendered_on_the_page(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.analytics'));

        $response->assertOk();
        $response->assertSee('data-testid="date-filter-form"', false);
        $response->assertSee('data-testid="date-preset-select"', false);
    }

    /** @test */
    public function last_7_days_preset_filters_proposals_by_created_at(): void
    {
        $rep = $this->salesRep();

        // Old proposal — 30 days ago
        Proposal::factory()->ownedBy($rep)->create([
            'status'     => 'Sent',
            'created_at' => now()->subDays(30),
            'updated_at' => now()->subDays(30),
        ]);

        // Recent proposal — today
        $recent = Proposal::factory()->ownedBy($rep)->create([
            'status'     => 'Sent',
            'proposal_title' => 'Recent Proposal',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.analytics', ['date_preset' => '7d']));

        $response->assertOk();
        // Only 1 proposal matches the 7-day window → total shows 1
        $response->assertSee('data-testid="kpi-total"', false);
        // The count badge on the breakdown table should say "1 total"
        $response->assertSee('1 total');
    }

    /** @test */
    public function custom_date_range_shows_date_inputs(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.analytics', ['date_preset' => 'custom']));

        $response->assertOk();
        $response->assertSee('data-testid="date-from-input"', false);
        $response->assertSee('data-testid="date-to-input"', false);
    }

    /** @test */
    public function custom_date_range_filters_proposals_correctly(): void
    {
        $rep = $this->salesRep();

        Proposal::factory()->ownedBy($rep)->create([
            'status'         => 'Accepted',
            'proposal_title' => 'In Range',
            'created_at'     => '2026-03-15',
            'updated_at'     => '2026-03-15',
        ]);
        Proposal::factory()->ownedBy($rep)->create([
            'status'         => 'Accepted',
            'proposal_title' => 'Out of Range',
            'created_at'     => '2026-01-01',
            'updated_at'     => '2026-01-01',
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.analytics', [
            'date_preset' => 'custom',
            'date_from'   => '2026-03-01',
            'date_to'     => '2026-03-31',
        ]));

        $response->assertOk();
        $response->assertSee('1 total');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-6: Rep performance table columns
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function rep_table_shows_required_columns(): void
    {
        $this->salesRep('Column Test Rep');

        $response = $this->actingAs($this->admin())->get(route('admin.analytics'));

        $response->assertOk();
        $response->assertSee('data-testid="rep-table"', false);
        $response->assertSeeText('Total');
        $response->assertSeeText('Sent');
        $response->assertSeeText('Accepted');
        $response->assertSeeText('Open %');
        $response->assertSeeText('Accept %');
        $response->assertSeeText('Column Test Rep');
    }

    /** @test */
    public function rep_table_does_not_show_admin_users(): void
    {
        $admin2 = User::factory()->admin()->create(['name' => 'Should Not Appear']);

        $response = $this->actingAs($this->admin())->get(route('admin.analytics'));

        $response->assertOk();
        $response->assertDontSeeText('Should Not Appear');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-7: Rep table sortable
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function rep_table_sort_links_are_present(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.analytics'));

        $response->assertOk();
        // All sortable column links should be present
        $response->assertSee('rep_sort=accepted', false);
        $response->assertSee('rep_sort=name', false);
        $response->assertSee('rep_sort=total', false);
    }

    /** @test */
    public function rep_table_sorts_by_accepted_descending_by_default(): void
    {
        $lowRep  = $this->salesRep('Low Rep');
        $highRep = $this->salesRep('High Rep');

        $this->proposal($lowRep,  'Accepted');
        $this->proposal($highRep, 'Accepted');
        $this->proposal($highRep, 'Accepted');
        $this->proposal($highRep, 'Accepted');

        $response = $this->actingAs($this->admin())->get(route('admin.analytics'));

        $response->assertOk();
        $content = $response->getContent();
        // High Rep (3 accepted) should appear before Low Rep (1 accepted)
        $this->assertLessThan(
            strpos($content, 'Low Rep'),
            strpos($content, 'High Rep')
        );
    }

    /** @test */
    public function rep_table_can_be_sorted_by_name_ascending(): void
    {
        $this->salesRep('Zebra Rep');
        $this->salesRep('Alpha Rep');

        $response = $this->actingAs($this->admin())
            ->get(route('admin.analytics', ['rep_sort' => 'name', 'rep_dir' => 'asc']));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertLessThan(
            strpos($content, 'Zebra Rep'),
            strpos($content, 'Alpha Rep')
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-8: Rep table searchable by name
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function rep_search_filters_table_to_matching_reps(): void
    {
        $this->salesRep('Findable Rep');
        $this->salesRep('Other Rep');

        $response = $this->actingAs($this->admin())
            ->get(route('admin.analytics', ['rep_search' => 'Findable']));

        $response->assertOk();
        $response->assertSeeText('Findable Rep');
        $response->assertDontSeeText('Other Rep');
    }

    /** @test */
    public function rep_search_shows_empty_state_when_no_match(): void
    {
        $this->salesRep('Someone Else');

        $response = $this->actingAs($this->admin())
            ->get(route('admin.analytics', ['rep_search' => 'Nonexistent']));

        $response->assertOk();
        $response->assertSee('data-testid="rep-empty"', false);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-9: Per-proposal breakdown columns
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function proposal_breakdown_shows_id_title_rep_status_deal(): void
    {
        $rep = User::factory()->sales()->create(['name' => 'Visible Rep']);
        Proposal::factory()->ownedBy($rep)->create([
            'proposal_title' => 'The Big Pitch',
            'client_name'    => 'ACME Corp',
            'status'         => 'Sent',
            'deal_size'      => 50000,
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.analytics'));

        $response->assertOk();
        $response->assertSee('data-testid="proposal-breakdown"', false);
        $response->assertSeeText('The Big Pitch');
        $response->assertSeeText('Visible Rep');
        $response->assertSeeText('Sent');
        $response->assertSeeText('50,000');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-10: Filter by status
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function proposal_status_filter_shows_only_matching_proposals(): void
    {
        $rep = $this->salesRep();
        Proposal::factory()->ownedBy($rep)->create(['proposal_title' => 'Draft One',    'status' => 'Draft']);
        Proposal::factory()->ownedBy($rep)->create(['proposal_title' => 'Accepted One', 'status' => 'Accepted']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.analytics', ['filter_status' => 'Accepted']));

        $response->assertOk();
        $response->assertSeeText('Accepted One');
        $response->assertDontSeeText('Draft One');
    }

    /** @test */
    public function status_filter_dropdown_is_rendered(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.analytics'));

        $response->assertOk();
        $response->assertSee('data-testid="status-filter-select"', false);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-11: Filter by rep
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function proposal_rep_filter_shows_only_that_reps_proposals(): void
    {
        $repA = $this->salesRep('Alpha');
        $repB = $this->salesRep('Beta');

        Proposal::factory()->ownedBy($repA)->create(['proposal_title' => 'Alpha Proposal', 'status' => 'Sent']);
        Proposal::factory()->ownedBy($repB)->create(['proposal_title' => 'Beta Proposal',  'status' => 'Sent']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.analytics', ['filter_rep' => $repA->id]));

        $response->assertOk();
        $response->assertSeeText('Alpha Proposal');
        $response->assertDontSeeText('Beta Proposal');
    }

    /** @test */
    public function rep_filter_dropdown_lists_all_sales_reps(): void
    {
        $this->salesRep('Drop Rep One');
        $this->salesRep('Drop Rep Two');

        $response = $this->actingAs($this->admin())->get(route('admin.analytics'));

        $response->assertOk();
        $response->assertSee('data-testid="rep-filter-select"', false);
        $response->assertSeeText('Drop Rep One');
        $response->assertSeeText('Drop Rep Two');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-12: Pagination
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function proposal_breakdown_paginates_at_25_per_page(): void
    {
        $rep = $this->salesRep();
        // Create 30 proposals
        Proposal::factory()->count(30)->ownedBy($rep)->create(['status' => 'Draft']);

        $response = $this->actingAs($this->admin())->get(route('admin.analytics'));

        $response->assertOk();
        // 30 total, 25 per page → pagination controls should appear
        // The count badge should say 30 total
        $response->assertSee('30 total');
        // Pagination links rendered (page 2 link)
        $response->assertSee('page=2', false);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-13: Clickable proposal rows
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function proposal_rows_contain_href_to_proposal_detail(): void
    {
        $rep      = $this->salesRep();
        $proposal = Proposal::factory()->ownedBy($rep)->create(['status' => 'Sent']);

        $response = $this->actingAs($this->admin())->get(route('admin.analytics'));

        $response->assertOk();
        // The onclick navigates to the proposal show route
        $response->assertSee(route('proposals.show', $proposal->id), false);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-16: Empty state
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function empty_state_shown_when_filter_matches_no_proposals(): void
    {
        $rep = $this->salesRep();
        $this->proposal($rep, 'Draft');

        // Filter for Accepted — none exist
        $response = $this->actingAs($this->admin())
            ->get(route('admin.analytics', ['filter_status' => 'Accepted']));

        $response->assertOk();
        $response->assertSee('data-testid="proposal-empty-state"', false);
        $response->assertSeeText('No proposals found for the selected period');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-17: Currency formatting
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function deal_values_are_formatted_with_dollar_sign_and_commas(): void
    {
        $rep = $this->salesRep();
        Proposal::factory()->ownedBy($rep)->create(['deal_size' => 125000, 'status' => 'Sent']);

        $response = $this->actingAs($this->admin())->get(route('admin.analytics'));

        $response->assertOk();
        $response->assertSee('$125,000', false);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-18: CSV export — proposals
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function export_proposals_returns_csv_with_correct_headers(): void
    {
        $rep = $this->salesRep();
        Proposal::factory()->ownedBy($rep)->create([
            'proposal_title' => 'Export Me',
            'status'         => 'Sent',
            'deal_size'      => 9999,
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.analytics.export.proposals'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertSee('ID,Title,Rep,Client,Company,Status,Deal Size,Created,Sent,Views');
        $response->assertSee('Export Me');
    }

    /** @test */
    public function export_proposals_requires_admin_role(): void
    {
        $this->actingAs($this->salesRep())
             ->get(route('admin.analytics.export.proposals'))
             ->assertForbidden();
    }

    /** @test */
    public function export_proposals_respects_status_filter(): void
    {
        $rep = $this->salesRep();
        Proposal::factory()->ownedBy($rep)->create(['proposal_title' => 'Draft Proposal',    'status' => 'Draft']);
        Proposal::factory()->ownedBy($rep)->create(['proposal_title' => 'Accepted Proposal', 'status' => 'Accepted']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.analytics.export.proposals', ['filter_status' => 'Accepted']));

        $response->assertOk();
        $response->assertSee('Accepted Proposal');
        $response->assertDontSee('Draft Proposal');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-19: CSV export — rep performance
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function export_reps_returns_csv_with_correct_headers(): void
    {
        $rep = User::factory()->sales()->create(['name' => 'Export Rep']);
        $this->proposal($rep, 'Accepted');

        $response = $this->actingAs($this->admin())
            ->get(route('admin.analytics.export.reps'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertSee('Name,Email,Total,Sent,Accepted,Open %,Accept %,Avg Days to Accept,Views');
        $response->assertSee('Export Rep');
    }

    /** @test */
    public function export_reps_requires_admin_role(): void
    {
        $this->actingAs($this->salesRep())
             ->get(route('admin.analytics.export.reps'))
             ->assertForbidden();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-21: Last-updated timestamp
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function analytics_page_shows_last_updated_timestamp(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.analytics'));

        $response->assertOk();
        $response->assertSee('as of', false);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-22: Avg time-to-acceptance
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function avg_days_to_accept_column_is_shown_in_rep_table(): void
    {
        $rep = $this->salesRep('Avg Days Rep');
        Proposal::factory()->ownedBy($rep)->create([
            'status'   => 'Accepted',
            'sent_at'  => now()->subDays(5),
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.analytics'));

        $response->assertOk();
        $response->assertSeeText('Avg Days');
    }

    /** @test */
    public function platform_avg_days_to_accept_appears_in_deal_value_kpi(): void
    {
        $rep = $this->salesRep();
        Proposal::factory()->ownedBy($rep)->create([
            'status'  => 'Accepted',
            'sent_at' => now()->subDays(10),
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.analytics'));

        $response->assertOk();
        $response->assertSee('data-testid="kpi-deal-value"', false);
        $response->assertSee('to accept');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Bot views excluded from analytics totals
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function bot_views_are_excluded_from_total_views_kpi(): void
    {
        $rep      = $this->salesRep();
        $proposal = $this->proposal($rep, 'Viewed');

        $this->humanView($proposal);  // counted
        $this->botView($proposal);    // excluded

        $response = $this->actingAs($this->admin())->get(route('admin.analytics'));

        $response->assertOk();
        $response->assertSee('data-testid="kpi-open-rate"', false);
        // "1 total views" sub-label
        $response->assertSee('1 total views');
    }
}
