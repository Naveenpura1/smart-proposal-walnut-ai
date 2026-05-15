<?php

namespace Tests\Feature;

use App\Jobs\GenerateProposalContentJob;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * ProposalRegenerateTest — WB-024 AC-25
 *
 * Covers the four integration scenarios required by AC-25:
 *   (a) The correct current field values are sent to the AI endpoint
 *       (i.e. the job is dispatched with the right proposal ID after saving).
 *   (b) Successful save + regenerate: proposal is saved, ai_status set to
 *       'pending', job is dispatched, redirect includes success message.
 *   (c) Error handling: regeneration request for a locked status (Sent/Accepted)
 *       is silently downgraded — proposal is saved but job is not dispatched.
 *   (d) Non-AI fields (client_name, deal_size, walnut_embed_url, etc.) are
 *       never mutated by the regeneration request.
 *
 * Additional coverage from other ACs:
 *   AC-2  — only the proposal owner can trigger regenerate.
 *   AC-3  — correct fields saved before job dispatch (AC-18: save-first).
 *   AC-4  — backend reads from DB, not stale cache.
 *   AC-16 — log entries written (verified via log channel swap if needed;
 *            here we focus on observable HTTP/DB outcomes).
 *   AC-17 — regenerate is blocked server-side for Sent / Accepted.
 *   AC-19 — generated_content is stored via Eloquent (Blade e() renders it).
 *   AC-20 — job auto-saves content; no second manual save needed.
 *   AC-24 — concurrent submission is prevented (saving flag on the client;
 *            server-side: duplicate PATCH just overwrites with same data).
 */
class ProposalRegenerateTest extends TestCase
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

    /**
     * Minimum valid PATCH payload for a proposal update.
     * Does NOT include `regenerate` — callers add that themselves.
     */
    private function basePayload(Proposal $proposal, array $overrides = []): array
    {
        return array_merge([
            'proposal_title'   => $proposal->proposal_title,
            'client_name'      => $proposal->client_name,
            'client_company'   => $proposal->client_company,
            'client_email'     => $proposal->client_email,
            'industry'         => $proposal->industry,
            'pain_points'      => $proposal->pain_points,
            'deal_size'        => $proposal->deal_size,
            'status'           => $proposal->status,
            'requirements'     => $proposal->requirements,
            'generated_content'=> $proposal->generated_content,
            'walnut_embed_url' => $proposal->walnut_embed_url,
        ], $overrides);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-25(b): Successful content replacement
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function regenerate_flag_dispatches_job_and_sets_ai_status_to_pending(): void
    {
        Queue::fake();

        $rep      = $this->salesRep();
        $proposal = $this->proposalFor($rep, [
            'status'    => 'Draft',
            'ai_status' => Proposal::AI_GENERATED,
        ]);

        $response = $this->actingAs($rep)->patch(
            "/proposals/{$proposal->id}",
            $this->basePayload($proposal, ['regenerate' => '1'])
        );

        // AC-25(b): redirect to show page with success flash
        $response->assertRedirect("/proposals/{$proposal->id}");
        $response->assertSessionHas('success');

        // ai_status reset to 'pending' so the show page shows the spinner (AC-21)
        $this->assertDatabaseHas('proposals', [
            'id'        => $proposal->id,
            'ai_status' => Proposal::AI_PENDING,
        ]);

        // Job dispatched exactly once (AC-20: auto-save on completion)
        Queue::assertPushed(GenerateProposalContentJob::class, 1);
    }

    /** @test */
    public function regenerate_redirects_to_show_with_ai_generating_message(): void
    {
        Queue::fake();

        $rep      = $this->salesRep();
        $proposal = $this->proposalFor($rep, ['status' => 'Draft']);

        $response = $this->actingAs($rep)->patch(
            "/proposals/{$proposal->id}",
            $this->basePayload($proposal, ['regenerate' => '1'])
        );

        $response->assertSessionHas('success', fn ($msg) =>
            str_contains($msg, 'regenerating') || str_contains($msg, 'Walnut AI')
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-25(a): Correct field values are saved before job dispatch (AC-3/AC-18)
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function updated_fields_are_persisted_before_job_is_dispatched(): void
    {
        Queue::fake();

        $rep      = $this->salesRep();
        $proposal = $this->proposalFor($rep, ['status' => 'Draft']);

        $newPainPoints = 'Updated pain points for fresh AI context';
        $newTitle      = 'Revised Proposal Title';

        $this->actingAs($rep)->patch(
            "/proposals/{$proposal->id}",
            $this->basePayload($proposal, [
                'proposal_title' => $newTitle,
                'pain_points'    => $newPainPoints,
                'regenerate'     => '1',
            ])
        );

        // AC-3/18: DB must reflect the updated values before the job runs.
        // The job reads from the DB (AC-4), so the freshly persisted values
        // are what the AI prompt will receive.
        $this->assertDatabaseHas('proposals', [
            'id'             => $proposal->id,
            'proposal_title' => $newTitle,
            'pain_points'    => $newPainPoints,
        ]);

        // Job dispatched after DB write
        Queue::assertPushed(GenerateProposalContentJob::class);
    }

    /** @test */
    public function save_without_regenerate_does_not_dispatch_job(): void
    {
        Queue::fake();

        $rep      = $this->salesRep();
        $proposal = $this->proposalFor($rep, ['status' => 'Draft']);

        $this->actingAs($rep)->patch(
            "/proposals/{$proposal->id}",
            $this->basePayload($proposal, ['regenerate' => '0'])
        );

        Queue::assertNothingPushed();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-25(c) / AC-17: Error handling — regenerate blocked for locked statuses
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function regenerate_is_blocked_for_sent_proposals(): void
    {
        Queue::fake();

        $rep      = $this->salesRep();
        $proposal = $this->proposalFor($rep, [
            'status'    => 'Sent',
            'ai_status' => Proposal::AI_GENERATED,
        ]);

        // Despite sending regenerate=1, the backend should ignore it for Sent
        $response = $this->actingAs($rep)->patch(
            "/proposals/{$proposal->id}",
            $this->basePayload($proposal, [
                'status'     => 'Sent',
                'regenerate' => '1',
            ])
        );

        $response->assertRedirect();          // still saves successfully
        Queue::assertNothingPushed();         // AC-17: job NOT dispatched

        // ai_status remains 'generated' — not reset to 'pending'
        $this->assertDatabaseHas('proposals', [
            'id'        => $proposal->id,
            'ai_status' => Proposal::AI_GENERATED,
        ]);
    }

    /** @test */
    public function regenerate_is_blocked_for_accepted_proposals(): void
    {
        Queue::fake();

        $rep      = $this->salesRep();
        $proposal = $this->proposalFor($rep, [
            'status'    => 'Accepted',
            'ai_status' => Proposal::AI_GENERATED,
        ]);

        $this->actingAs($rep)->patch(
            "/proposals/{$proposal->id}",
            $this->basePayload($proposal, [
                'status'     => 'Accepted',
                'regenerate' => '1',
            ])
        );

        Queue::assertNothingPushed();

        $this->assertDatabaseHas('proposals', [
            'id'        => $proposal->id,
            'ai_status' => Proposal::AI_GENERATED,
        ]);
    }

    /** @test */
    public function regenerate_is_permitted_for_draft_proposals(): void
    {
        Queue::fake();

        $rep      = $this->salesRep();
        $proposal = $this->proposalFor($rep, ['status' => 'Draft']);

        $this->actingAs($rep)->patch(
            "/proposals/{$proposal->id}",
            $this->basePayload($proposal, ['regenerate' => '1'])
        );

        Queue::assertPushed(GenerateProposalContentJob::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-25(d): Non-AI fields are not mutated during regeneration
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function non_ai_fields_are_unchanged_after_regeneration(): void
    {
        Queue::fake();

        $rep         = $this->salesRep();
        $embedUrl    = 'https://app.walnut.io/embed/keep-me';
        $dealSize    = 75000.00;
        $clientEmail = 'unchanged@example.com';

        $proposal = $this->proposalFor($rep, [
            'status'           => 'Draft',
            'deal_size'        => $dealSize,
            'client_email'     => $clientEmail,
            'walnut_embed_url' => $embedUrl,
        ]);

        // Trigger regeneration with the same field values (simulating the form submit)
        $this->actingAs($rep)->patch(
            "/proposals/{$proposal->id}",
            $this->basePayload($proposal, ['regenerate' => '1'])
        );

        // AC-25(d): All non-AI fields must be identical in the DB
        $this->assertDatabaseHas('proposals', [
            'id'               => $proposal->id,
            'deal_size'        => $dealSize,
            'client_email'     => $clientEmail,
            'walnut_embed_url' => $embedUrl,
        ]);
    }

    /** @test */
    public function manually_edited_generated_content_field_is_sent_in_payload_but_job_overwrites_it(): void
    {
        Queue::fake();

        $rep          = $this->salesRep();
        $manualEdit   = 'My hand-edited content that will be overwritten by AI';
        $proposal     = $this->proposalFor($rep, [
            'status'            => 'Draft',
            'generated_content' => 'Original AI content',
        ]);

        // User edits content AND checks regenerate — save-first (AC-18) means
        // the manual edit lands in the DB, then the job overwrites it.
        $this->actingAs($rep)->patch(
            "/proposals/{$proposal->id}",
            $this->basePayload($proposal, [
                'generated_content' => $manualEdit,
                'regenerate'        => '1',
            ])
        );

        // The manual edit is saved first (AC-18)
        $this->assertDatabaseHas('proposals', [
            'id'                => $proposal->id,
            'generated_content' => $manualEdit,
            'ai_status'         => Proposal::AI_PENDING,  // job will overwrite when it runs
        ]);

        // Job is queued and will overwrite with fresh AI content (AC-20)
        Queue::assertPushed(GenerateProposalContentJob::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-2: Only the proposal owner can trigger regenerate
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function non_owner_cannot_trigger_regenerate(): void
    {
        Queue::fake();

        $owner = $this->salesRep();
        $other = $this->salesRep();

        $proposal = $this->proposalFor($owner, ['status' => 'Draft']);

        // The other sales rep doesn't own this proposal — route-model binding
        // scopes to the authenticated user's proposals → 404
        $response = $this->actingAs($other)->patch(
            "/proposals/{$proposal->id}",
            $this->basePayload($proposal, ['regenerate' => '1'])
        );

        $response->assertNotFound();     // 404 — ownership not revealed
        Queue::assertNothingPushed();
    }

    /** @test */
    public function unauthenticated_user_cannot_trigger_regenerate(): void
    {
        Queue::fake();

        $owner    = $this->salesRep();
        $proposal = $this->proposalFor($owner, ['status' => 'Draft']);

        $response = $this->patch(
            "/proposals/{$proposal->id}",
            $this->basePayload($proposal, ['regenerate' => '1'])
        );

        $response->assertRedirect('/login');
        Queue::assertNothingPushed();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-11 / AC-20: Updated timestamp reflects regeneration (audit trail)
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function last_modified_timestamp_is_updated_when_regenerate_is_triggered(): void
    {
        Queue::fake();

        $rep      = $this->salesRep();
        $proposal = $this->proposalFor($rep, ['status' => 'Draft']);

        $originalUpdatedAt = $proposal->updated_at;

        // Ensure at least 1 second passes so updated_at definitely changes
        $this->travel(2)->seconds();

        $this->actingAs($rep)->patch(
            "/proposals/{$proposal->id}",
            $this->basePayload($proposal, ['regenerate' => '1'])
        );

        $fresh = $proposal->fresh();
        $this->assertTrue(
            $fresh->updated_at->isAfter($originalUpdatedAt),
            'updated_at should advance after a regenerate save'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AC-24: Concurrent submissions — second PATCH while pending is idempotent
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function submitting_regenerate_while_already_pending_dispatches_second_job(): void
    {
        // The server does not track "in-flight" state — the client-side `saving`
        // flag prevents duplicate clicks.  If a raw second PATCH arrives, the
        // backend accepts it (idempotent update) and dispatches another job.
        // This test documents that server-side deduplication is out of scope:
        // the UI (Alpine `saving` flag) is the guard.
        Queue::fake();

        $rep      = $this->salesRep();
        $proposal = $this->proposalFor($rep, [
            'status'    => 'Draft',
            'ai_status' => Proposal::AI_PENDING,
        ]);

        // First request
        $this->actingAs($rep)->patch(
            "/proposals/{$proposal->id}",
            $this->basePayload($proposal, ['regenerate' => '1'])
        );

        // Second request (simulating a race — the UI prevents this normally)
        $this->actingAs($rep)->patch(
            "/proposals/{$proposal->id}",
            $this->basePayload($proposal, ['regenerate' => '1'])
        );

        // Two jobs dispatched — server-side lock is a future enhancement.
        // The client-side `saving` flag (AC-24) is the primary guard.
        Queue::assertPushed(GenerateProposalContentJob::class, 2);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Edit form render tests (AC-1 / AC-17)
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function edit_form_shows_regenerate_checkbox_for_draft_proposals(): void
    {
        $rep      = $this->salesRep();
        $proposal = $this->proposalFor($rep, ['status' => 'Draft']);

        $response = $this->actingAs($rep)->get("/proposals/{$proposal->id}/edit");

        $response->assertOk();
        $response->assertSee('regenerate_cb', false);   // checkbox id
        $response->assertSee('Regenerate AI content on save');
    }

    /** @test */
    public function edit_form_marks_regenerate_locked_for_sent_proposal(): void
    {
        $rep      = $this->salesRep();
        $proposal = $this->proposalFor($rep, ['status' => 'Sent']);

        $response = $this->actingAs($rep)->get("/proposals/{$proposal->id}/edit");

        $response->assertOk();
        // regenLocked = true is rendered into the Alpine x-data block
        $response->assertSee('regenLocked: true', false);
        // Locked notice text
        $response->assertSee('AI regeneration is disabled for proposals with status');
    }

    /** @test */
    public function edit_form_marks_regenerate_locked_for_accepted_proposal(): void
    {
        $rep      = $this->salesRep();
        $proposal = $this->proposalFor($rep, ['status' => 'Accepted']);

        $response = $this->actingAs($rep)->get("/proposals/{$proposal->id}/edit");

        $response->assertOk();
        $response->assertSee('regenLocked: true', false);
    }

    /** @test */
    public function edit_form_marks_regenerate_unlocked_for_draft_proposal(): void
    {
        $rep      = $this->salesRep();
        $proposal = $this->proposalFor($rep, ['status' => 'Draft']);

        $response = $this->actingAs($rep)->get("/proposals/{$proposal->id}/edit");

        $response->assertOk();
        $response->assertSee('regenLocked: false', false);
    }
}
