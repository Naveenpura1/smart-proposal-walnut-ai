<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add view-tracking columns to proposals (WB-032).
 *
 * first_viewed_at  — timestamp of the very first public-URL view (AC-14/27)
 * sent_at          — timestamp when status was set to Sent, used for
 *                    time-to-first-view metric (AC-27)
 *
 * The `status` ENUM is extended to include 'Viewed' (AC-7).
 * MySQL requires a full column redefinition to change an ENUM.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            // AC-27: when the proposal was first sent (needed for time-to-first-view)
            $table->timestamp('sent_at')->nullable()->after('ai_generated_at');

            // AC-14: when the proposal was first viewed via public URL
            $table->timestamp('first_viewed_at')->nullable()->after('sent_at');
        });

        // Extend the status ENUM to include 'Viewed' (AC-7).
        // DB::statement is required because Blueprint doesn't support ENUM changes.
        \Illuminate\Support\Facades\DB::statement(
            "ALTER TABLE proposals MODIFY COLUMN status ENUM('Draft','Sent','Viewed','Accepted') NOT NULL DEFAULT 'Draft'"
        );
    }

    public function down(): void
    {
        // Revert status ENUM first (removing 'Viewed')
        \Illuminate\Support\Facades\DB::statement(
            "ALTER TABLE proposals MODIFY COLUMN status ENUM('Draft','Sent','Accepted') NOT NULL DEFAULT 'Draft'"
        );

        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn(['sent_at', 'first_viewed_at']);
        });
    }
};
