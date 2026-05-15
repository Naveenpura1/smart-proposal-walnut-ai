<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add richer client-detail fields required by WB-020.
     *
     * AC-2 (mandatory): proposal_title, client_company, client_email
     * AC-3 (optional) : requirements (extra notes / specific requirements)
     *
     * All new columns are nullable so existing proposal rows are not broken.
     * The application-layer validation enforces required/optional rules.
     */
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            // Mandatory (AC-2) — nullable in DB, enforced by validation
            $table->string('proposal_title')->nullable()->after('id');
            $table->string('client_company')->nullable()->after('proposal_title');
            $table->string('client_email')->nullable()->after('client_company');

            // Optional (AC-3) — additional free-text notes to guide AI
            $table->text('requirements')->nullable()->after('pain_points');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn(['proposal_title', 'client_company', 'client_email', 'requirements']);
        });
    }
};
