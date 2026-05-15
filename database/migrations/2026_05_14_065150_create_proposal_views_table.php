<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * proposal_views — WB-032
 *
 * Records every public token URL access event.
 *
 * Columns:
 *   proposal_id  — the proposal that was viewed
 *   token_used   — the public_token value at time of view (AC-19: historical
 *                  views are retained and labelled when token is regenerated)
 *   ip_address   — viewer's IP (nullable; may be masked in GDPR regions — AC-29)
 *   user_agent   — raw UA string for device/browser detection (AC-16)
 *   referrer     — HTTP Referer header where present (AC-4)
 *   is_bot       — true when UA matches a known bot/crawler pattern (AC-17)
 *   is_unique    — true when this is the first view from this IP on this proposal
 *   viewed_at    — UTC timestamp of the view event (AC-4)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_views', function (Blueprint $table) {
            $table->id();

            $table->foreignId('proposal_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // AC-19: store which token was in use at time of view
            $table->string('token_used', 36)->index();

            // AC-4: capture metadata per view
            $table->string('ip_address', 45)->nullable();   // supports IPv6
            $table->text('user_agent')->nullable();
            $table->string('referrer', 2048)->nullable();

            // AC-17: bot flag — excluded from public view counts
            $table->boolean('is_bot')->default(false)->index();

            // AC-5: unique-viewer flag (first view from this IP on this proposal)
            $table->boolean('is_unique')->default(false)->index();

            // AC-4: precise timestamp
            $table->timestamp('viewed_at')->useCurrent();

            // Composite index to efficiently query per-proposal view history
            $table->index(['proposal_id', 'viewed_at']);
            $table->index(['proposal_id', 'is_bot', 'viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_views');
    }
};
