<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add AI generation tracking columns to proposals (WB-027).
     *
     * These columns are separate from the existing `status` ENUM which tracks
     * the proposal's business lifecycle (Draft → Sent → Accepted).
     *
     * ai_status    — AC-7/12/13/21/22: tracks AI generation state machine:
     *                  pending     → job enqueued, generation not yet started
     *                  processing  → job running, API call in flight
     *                  generated   → AI content successfully received and stored
     *                  fallback    → all retries exhausted, fallback content stored
     *                  failed      → fallback also failed (manual intervention needed)
     *
     * ai_attempts  — AC-18: number of API call attempts made (including retries)
     *
     * ai_generated_at — AC-6: timestamp of the successful generation or fallback storage
     */
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->enum('ai_status', ['pending', 'processing', 'generated', 'fallback', 'failed'])
                  ->default('pending')
                  ->after('generated_content');

            $table->unsignedTinyInteger('ai_attempts')
                  ->default(0)
                  ->after('ai_status');

            $table->timestamp('ai_generated_at')
                  ->nullable()
                  ->after('ai_attempts');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn(['ai_status', 'ai_attempts', 'ai_generated_at']);
        });
    }
};
