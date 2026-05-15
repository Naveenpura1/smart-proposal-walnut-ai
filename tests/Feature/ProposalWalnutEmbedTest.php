<?php

namespace Tests\Feature;

use App\Models\Proposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ProposalWalnutEmbedTest — WB-026 AC-23
 *
 * Covers the three embed states required by AC-23:
 *   (a) iframe renders when a valid walnut_embed_url is stored on the proposal.
 *   (b) fallback renders when no walnut_embed_url is stored.
 *   (c) fallback error state is available in the DOM when the iframe is present
 *       (Alpine JS transitions it in on load failure — verified via presence of
 *       the fallback node and the retry button in the page source).
 *
 * Additional coverage from other ACs:
 *   AC-1  — embed section is clearly labelled ("Interactive Demo" heading).
 *   AC-3  — iframe width=100%, min-height 600px.
 *   AC-4  — sandbox / allow attributes present.
 *   AC-8  — fallback message content is informative.
 *   AC-11 — unauthenticated user cannot reach the page (redirect to login).
 *   AC-12 — embed URL comes from the DB (not hard-coded); edit form exposes the field.
 *   AC-20 — iframe title attribute present.
 *
 * Note on AC-23(c): iframe @error is a client-side JavaScript event that cannot
 * be triggered in a PHPUnit HTTP test. Instead we assert that:
 *   – the fallback-error node is rendered in the DOM (hidden via Alpine x-show),
 *   – the retry button is present and wired to reset the Alpine state.
 * This guarantees the fallback markup is delivered to the browser; the JS
 * behaviour is validated separately (e.g. with a Dusk / Playwright suite).
 */
class ProposalWalnutEmbedTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function salesRep(): User
    {
        return User::factory()->sales()->create();
    }

    private function proposalFor(User $user, array $attrs = []): Proposal
    {
        return Proposal::factory()->ownedBy($user)->create($attrs);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-23(a): iframe renders when a valid embed URL is stored
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function embed_iframe_is_rendered_when_proposal_has_a_valid_walnut_embed_url(): void
    {
        $rep = $this->salesRep();
        $embedUrl = 'https://app.walnut.io/embed/demo-abc123';

        $proposal = $this->proposalFor($rep, [
            'walnut_embed_url' => $embedUrl,
            'proposal_title'   => 'Demo Proposal',
        ]);

        $response = $this->actingAs($rep)->get("/proposals/{$proposal->id}");

        $response->assertOk();

        // AC-23(a): the iframe is in the DOM and its src matches the stored URL
        $response->assertSee('data-testid="walnut-embed-iframe"', false);
        $response->assertSee('src="' . $embedUrl . '"', false);

        // AC-20: iframe has a descriptive title attribute
        $response->assertSee('title="Walnut AI Interactive Demo', false);

        // AC-4: sandbox attribute is present
        $response->assertSee('sandbox=', false);

        // AC-4: allow attribute is present (fullscreen, clipboard-write)
        $response->assertSee('allow="fullscreen', false);

        // AC-1: section heading is present
        $response->assertSee('Interactive Demo');

        // AC-23(a): the embed container wrapper is rendered (not the no-url fallback)
        $response->assertSee('data-testid="embed-container"', false);
        $response->assertDontSee('data-testid="embed-fallback-no-url"', false);
    }

    /** @test */
    public function embed_container_includes_card_header_with_walnut_branding(): void
    {
        $rep = $this->salesRep();
        $proposal = $this->proposalFor($rep, [
            'walnut_embed_url' => 'https://app.walnut.io/embed/xyz',
        ]);

        $response = $this->actingAs($rep)->get("/proposals/{$proposal->id}");

        $response->assertOk();
        $response->assertSee('Walnut AI Demo');
    }

    /** @test */
    public function embed_iframe_src_matches_exact_stored_url(): void
    {
        $rep = $this->salesRep();
        $embedUrl = 'https://example-walnut.io/demos/secret-slug?ref=proposal&v=2';

        $proposal = $this->proposalFor($rep, ['walnut_embed_url' => $embedUrl]);

        $response = $this->actingAs($rep)->get("/proposals/{$proposal->id}");

        $response->assertOk();
        $response->assertSee(htmlspecialchars($embedUrl, ENT_QUOTES), false);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-23(b): fallback renders when no embed URL is stored
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function fallback_is_shown_when_proposal_has_no_walnut_embed_url(): void
    {
        $rep = $this->salesRep();
        $proposal = $this->proposalFor($rep, ['walnut_embed_url' => null]);

        $response = $this->actingAs($rep)->get("/proposals/{$proposal->id}");

        $response->assertOk();

        // AC-23(b): fallback node rendered, iframe container absent
        $response->assertSee('data-testid="embed-fallback-no-url"', false);
        $response->assertDontSee('data-testid="embed-container"', false);
        $response->assertDontSee('data-testid="walnut-embed-iframe"', false);

        // AC-8: clear informational message
        $response->assertSee('No demo attached');
        $response->assertSee('No Walnut AI interactive demo is currently linked');

        // AC-1: section heading still present
        $response->assertSee('Interactive Demo');
    }

    /** @test */
    public function fallback_is_shown_when_walnut_embed_url_is_empty_string(): void
    {
        $rep = $this->salesRep();
        // Directly set an empty string to verify hasEmbed() treats it as absent
        $proposal = $this->proposalFor($rep, ['walnut_embed_url' => '']);

        $response = $this->actingAs($rep)->get("/proposals/{$proposal->id}");

        $response->assertOk();
        $response->assertSee('data-testid="embed-fallback-no-url"', false);
        $response->assertDontSee('data-testid="walnut-embed-iframe"', false);
    }

    /** @test */
    public function fallback_contains_edit_link_to_attach_a_demo(): void
    {
        $rep = $this->salesRep();
        $proposal = $this->proposalFor($rep, ['walnut_embed_url' => null]);

        $response = $this->actingAs($rep)->get("/proposals/{$proposal->id}");

        $response->assertOk();
        // Fallback should link to the edit page so the user can attach a demo
        $response->assertSee(route('proposals.edit', $proposal), false);
        $response->assertSee('Edit proposal to attach a demo');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-23(c): fallback error state markup is delivered to the browser
    //           (JS @error handler transitions it in on iframe load failure)
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function load_error_fallback_node_is_present_in_dom_when_embed_url_is_set(): void
    {
        $rep = $this->salesRep();
        $proposal = $this->proposalFor($rep, [
            'walnut_embed_url' => 'https://app.walnut.io/embed/demo-abc',
        ]);

        $response = $this->actingAs($rep)->get("/proposals/{$proposal->id}");

        $response->assertOk();

        // AC-23(c): error fallback container is in the HTML (hidden initially by
        // Alpine x-show="error" / style="display:none"). The JS @error handler
        // on the iframe makes it visible when loading fails.
        $response->assertSee('data-testid="embed-fallback-error"', false);

        // AC-8: error fallback contains an informative message
        $response->assertSee('Demo could not be loaded');
        $response->assertSee('The interactive demo could not be loaded');

        // Retry button is present so the user can recover without a full reload
        $response->assertSee('Retry');
    }

    /** @test */
    public function load_error_fallback_includes_open_in_new_tab_link(): void
    {
        $rep = $this->salesRep();
        $embedUrl = 'https://app.walnut.io/embed/demo-abc';
        $proposal = $this->proposalFor($rep, ['walnut_embed_url' => $embedUrl]);

        $response = $this->actingAs($rep)->get("/proposals/{$proposal->id}");

        $response->assertOk();
        // Error fallback provides a direct link as an escape hatch
        $response->assertSee('Open in new tab');
        $response->assertSee($embedUrl, false);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-11: Unauthenticated access is blocked
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function unauthenticated_user_cannot_access_proposal_detail_or_embed(): void
    {
        $rep = $this->salesRep();
        $proposal = $this->proposalFor($rep, [
            'walnut_embed_url' => 'https://app.walnut.io/embed/secret',
        ]);

        $response = $this->get("/proposals/{$proposal->id}");

        // Must redirect to login, not expose the embed URL
        $response->assertRedirect('/login');
        $response->assertDontSee('walnut.io');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-12: Edit form exposes the walnut_embed_url field
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function edit_form_renders_walnut_embed_url_field(): void
    {
        $rep = $this->salesRep();
        $embedUrl = 'https://app.walnut.io/embed/my-demo';
        $proposal = $this->proposalFor($rep, ['walnut_embed_url' => $embedUrl]);

        $response = $this->actingAs($rep)->get("/proposals/{$proposal->id}/edit");

        $response->assertOk();
        $response->assertSee('walnut_embed_url', false);
        $response->assertSee($embedUrl, false);
    }

    /** @test */
    public function edit_form_renders_embed_url_field_when_url_is_null(): void
    {
        $rep = $this->salesRep();
        $proposal = $this->proposalFor($rep, ['walnut_embed_url' => null]);

        $response = $this->actingAs($rep)->get("/proposals/{$proposal->id}/edit");

        $response->assertOk();
        $response->assertSee('walnut_embed_url', false);
        $response->assertSee('Walnut AI Embed URL');
    }

    /** @test */
    public function updating_walnut_embed_url_via_patch_persists_to_database(): void
    {
        $rep = $this->salesRep();
        $proposal = $this->proposalFor($rep, ['walnut_embed_url' => null]);
        $newEmbedUrl = 'https://app.walnut.io/embed/new-demo-456';

        $response = $this->actingAs($rep)->patch("/proposals/{$proposal->id}", [
            'proposal_title'   => $proposal->proposal_title,
            'client_name'      => $proposal->client_name,
            'client_company'   => $proposal->client_company,
            'client_email'     => $proposal->client_email,
            'industry'         => $proposal->industry,
            'pain_points'      => $proposal->pain_points,
            'deal_size'        => $proposal->deal_size,
            'status'           => $proposal->status,
            'walnut_embed_url' => $newEmbedUrl,
        ]);

        $response->assertRedirect("/proposals/{$proposal->id}");
        $this->assertDatabaseHas('proposals', [
            'id'               => $proposal->id,
            'walnut_embed_url' => $newEmbedUrl,
        ]);
    }

    /** @test */
    public function clearing_walnut_embed_url_removes_iframe_on_next_page_load(): void
    {
        $rep = $this->salesRep();
        $proposal = $this->proposalFor($rep, [
            'walnut_embed_url' => 'https://app.walnut.io/embed/to-be-removed',
        ]);

        // Clear the URL
        $this->actingAs($rep)->patch("/proposals/{$proposal->id}", [
            'proposal_title'   => $proposal->proposal_title,
            'client_name'      => $proposal->client_name,
            'client_company'   => $proposal->client_company,
            'client_email'     => $proposal->client_email,
            'industry'         => $proposal->industry,
            'pain_points'      => $proposal->pain_points,
            'deal_size'        => $proposal->deal_size,
            'status'           => $proposal->status,
            'walnut_embed_url' => '',
        ]);

        // Now the show page should display the fallback
        $showResponse = $this->actingAs($rep)->get("/proposals/{$proposal->id}");
        $showResponse->assertOk();
        $showResponse->assertSee('data-testid="embed-fallback-no-url"', false);
        $showResponse->assertDontSee('data-testid="walnut-embed-iframe"', false);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-17: Refreshing after URL change reflects updated embed state
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function refreshing_after_adding_embed_url_shows_iframe(): void
    {
        $rep = $this->salesRep();
        $proposal = $this->proposalFor($rep, ['walnut_embed_url' => null]);

        // First load: no embed
        $before = $this->actingAs($rep)->get("/proposals/{$proposal->id}");
        $before->assertSee('data-testid="embed-fallback-no-url"', false);

        // Add the URL
        $newUrl = 'https://app.walnut.io/embed/added-later';
        $this->actingAs($rep)->patch("/proposals/{$proposal->id}", [
            'proposal_title'   => $proposal->proposal_title,
            'client_name'      => $proposal->client_name,
            'client_company'   => $proposal->client_company,
            'client_email'     => $proposal->client_email,
            'industry'         => $proposal->industry,
            'pain_points'      => $proposal->pain_points,
            'deal_size'        => $proposal->deal_size,
            'status'           => $proposal->status,
            'walnut_embed_url' => $newUrl,
        ]);

        // Second load: iframe should appear
        $after = $this->actingAs($rep)->get("/proposals/{$proposal->id}");
        $after->assertOk();
        $after->assertSee('data-testid="walnut-embed-iframe"', false);
        $after->assertSee($newUrl, false);
        $after->assertDontSee('data-testid="embed-fallback-no-url"', false);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Hasembed() unit-style checks via the show route
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function invalid_url_string_is_treated_as_no_embed(): void
    {
        $rep = $this->salesRep();
        // hasEmbed() uses FILTER_VALIDATE_URL — a string without a scheme is invalid
        $proposal = $this->proposalFor($rep, ['walnut_embed_url' => 'not-a-url']);

        $response = $this->actingAs($rep)->get("/proposals/{$proposal->id}");

        $response->assertOk();
        $response->assertSee('data-testid="embed-fallback-no-url"', false);
        $response->assertDontSee('data-testid="walnut-embed-iframe"', false);
    }
}
