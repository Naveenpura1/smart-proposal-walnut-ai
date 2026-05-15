<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ProposalView — one row per public-token URL access (WB-032).
 *
 * @property int         $id
 * @property int         $proposal_id
 * @property string      $token_used      Token in use at the time of the view (AC-19)
 * @property string|null $ip_address      Viewer IP — may be null if masked (AC-29)
 * @property string|null $user_agent      Raw User-Agent header (AC-16)
 * @property string|null $referrer        HTTP Referer header (AC-4)
 * @property bool        $is_bot          True when UA matches a bot pattern (AC-17)
 * @property bool        $is_unique       True on first view from this IP (AC-5)
 * @property \Carbon\Carbon $viewed_at    UTC timestamp of the event (AC-4)
 */
class ProposalView extends Model
{
    public $timestamps = false;   // we use viewed_at; no created_at / updated_at needed

    protected $fillable = [
        'proposal_id',
        'token_used',
        'ip_address',
        'user_agent',
        'referrer',
        'is_bot',
        'is_unique',
        'viewed_at',
    ];

    protected $casts = [
        'is_bot'    => 'boolean',
        'is_unique' => 'boolean',
        'viewed_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /** Only human (non-bot) views. */
    public function scopeHuman($query)
    {
        return $query->where('is_bot', false);
    }

    /** Only unique (first-visit) views. */
    public function scopeUnique($query)
    {
        return $query->where('is_unique', true);
    }
}
