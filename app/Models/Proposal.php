<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Proposal extends Model
{
    use HasFactory;

    // ── AI status constants (WB-027) ─────────────────────────────────────────

    /** Job enqueued; generation not yet started. */
    const AI_PENDING    = 'pending';

    /** Job executing; API call in flight. */
    const AI_PROCESSING = 'processing';

    /** AI content successfully received and stored (AC-7). */
    const AI_GENERATED  = 'generated';

    /** All retries exhausted; fallback content stored (AC-13). */
    const AI_FALLBACK   = 'fallback';

    /** DB write failed; manual intervention needed (AC-23). */
    const AI_FAILED     = 'failed';

    // ─────────────────────────────────────────────────────────────────────────

    protected $fillable = [
        'user_id',
        // Proposal identity (WB-020)
        'proposal_title',
        // Client fields (WB-020)
        'client_name',
        'client_company',
        'client_email',
        // Deal context
        'industry',
        'pain_points',
        'requirements',
        'deal_size',
        // AI output + workflow
        'generated_content',
        'status',
        'public_token',
        // AI generation tracking (WB-027)
        'ai_status',
        'ai_attempts',
        'ai_generated_at',
        // Walnut AI interactive embed (WB-026)
        'walnut_embed_url',
        // View tracking (WB-032)
        'sent_at',
        'first_viewed_at',
    ];

    // Business-lifecycle statuses (ENUM in DB — WB-032 adds 'Viewed')
    public const STATUSES = ['Draft', 'Sent', 'Viewed', 'Accepted'];

    protected $casts = [
        'deal_size'       => 'decimal:2',
        'ai_attempts'     => 'integer',
        'ai_generated_at' => 'datetime',
        'sent_at'         => 'datetime',
        'first_viewed_at' => 'datetime',
    ];

    // ── Lifecycle hooks ───────────────────────────────────────────────────────

    protected static function booted(): void
    {
        /**
         * Auto-generate a unique public_token for every new proposal (WB-022).
         * UUID v4 gives 122 bits of randomness — negligible collision probability.
         */
        static::creating(function (Proposal $proposal): void {
            if (empty($proposal->public_token)) {
                $proposal->public_token = (string) Str::uuid();
            }
        });
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** All view events recorded for this proposal (WB-032). */
    public function views()
    {
        return $this->hasMany(ProposalView::class);
    }

    /** Human (non-bot) view events only (AC-17). */
    public function humanViews()
    {
        return $this->hasMany(ProposalView::class)->where('is_bot', false);
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    /**
     * Returns true when the proposal's AI content is still being generated.
     * Used by the show view to display the "generating" spinner (AC-21).
     */
    public function isAiPending(): bool
    {
        return in_array($this->ai_status, [self::AI_PENDING, self::AI_PROCESSING]);
    }

    /**
     * Returns true when the AI content is ready to display (generated or fallback).
     */
    public function hasAiContent(): bool
    {
        return in_array($this->ai_status, [self::AI_GENERATED, self::AI_FALLBACK])
            && ! empty($this->generated_content);
    }

    /**
     * Returns true when a Walnut AI embed URL is stored and appears to be a
     * non-empty, valid-looking URL (AC-2 / WB-026).
     *
     * We deliberately do NOT make an HTTP request here — validity is checked
     * client-side when the iframe fires its load/error event (AC-7).
     */
    public function hasEmbed(): bool
    {
        return ! empty($this->walnut_embed_url)
            && filter_var(trim($this->walnut_embed_url), FILTER_VALIDATE_URL) !== false;
    }

    // ── View-tracking helpers (WB-032) ───────────────────────────────────────

    /**
     * AC-10: True when the public URL should render full proposal content.
     * Draft, Sent, and Viewed are "active" states; Accepted is also readable.
     * Deleted proposals can never reach here (they're gone from the DB).
     */
    public function isPubliclyAccessible(): bool
    {
        return in_array($this->status, ['Sent', 'Viewed', 'Accepted'], true);
    }

    /**
     * AC-11: Draft proposals must not expose content on the public URL.
     */
    public function isDraft(): bool
    {
        return $this->status === 'Draft';
    }

    /**
     * AC-18: Regenerate the public_token, invalidating the previous URL.
     * The old token value stays on all historical ProposalView rows (AC-19).
     */
    public function regenerateToken(): string
    {
        $this->update(['public_token' => (string) Str::uuid()]);
        return $this->public_token;
    }

    /**
     * Total human (non-bot) view count for this proposal (AC-5/13).
     */
    public function totalViewCount(): int
    {
        return $this->humanViews()->count();
    }

    /**
     * Unique human viewer count (distinct IPs, non-bot) (AC-5).
     */
    public function uniqueViewCount(): int
    {
        return $this->humanViews()->where('is_unique', true)->count();
    }

    // ── Domain actions ────────────────────────────────────────────────────────

    /**
     * Clone this proposal for the given user (WB-017 / WB-022).
     *
     * The clone gets:
     *  - status = 'Draft'; ai_status = 'pending' (AI must be re-queued separately)
     *  - proposal_title and client_name prefixed "Copy of …"
     *  - All content fields copied verbatim
     *  - user_id set to actor; public_token regenerated by the creating hook
     *  - ai_attempts / ai_generated_at reset to defaults
     */
    public function cloneFor(User $actor): static
    {
        return static::create([
            'user_id'           => $actor->id,
            'proposal_title'    => $this->proposal_title
                                    ? 'Copy of ' . $this->proposal_title
                                    : null,
            'client_name'       => $this->proposal_title
                                    ? $this->client_name
                                    : 'Copy of ' . $this->client_name,
            'client_company'    => $this->client_company,
            'client_email'      => $this->client_email,
            'industry'          => $this->industry,
            'pain_points'       => $this->pain_points,
            'requirements'      => $this->requirements,
            'deal_size'         => $this->deal_size,
            'generated_content' => $this->generated_content,
            'status'            => 'Draft',
            // Walnut embed URL is copied — the same demo is still relevant (WB-026).
            'walnut_embed_url'  => $this->walnut_embed_url,
            // Copy the AI content as-is (already generated). The operator can
            // trigger regeneration via proposals:regenerate if desired (AC-25).
            'ai_status'         => $this->ai_status === self::AI_GENERATED
                                    ? self::AI_GENERATED
                                    : self::AI_PENDING,
            'ai_attempts'       => 0,
            'ai_generated_at'   => $this->ai_status === self::AI_GENERATED
                                    ? $this->ai_generated_at
                                    : null,
            // public_token omitted — creating hook generates a fresh UUID.
        ]);
    }

    // ── Route model binding ───────────────────────────────────────────────────

    /**
     * Scope route-model binding to the authenticated user's own proposals.
     *
     * Sales reps get a 404 for proposals they don't own (existence not revealed).
     * Admins/super-admins are not scoped — they can access any proposal.
     */
    public function resolveRouteBinding($value, $field = null): ?static
    {
        $query = $this->where($field ?? $this->getRouteKeyName(), $value);

        if (Auth::check() && Auth::user()->isSales()) {
            $query->where('user_id', Auth::id());
        }

        return $query->first();
    }
}
