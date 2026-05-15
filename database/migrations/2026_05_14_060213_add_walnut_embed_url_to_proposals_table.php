<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add walnut_embed_url to proposals (WB-026).
     *
     * AC-12: The embed URL is retrieved from the backend as part of the
     *        proposal detail payload — not hard-coded on the frontend.
     * AC-17: Updating the URL on the record and refreshing the page always
     *        reflects the latest embed state.
     *
     * Nullable: not every proposal will have a Walnut demo attached.
     * Max 2 048 chars covers any realistic Walnut embed URL.
     */
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->string('walnut_embed_url', 2048)
                  ->nullable()
                  ->after('ai_generated_at');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn('walnut_embed_url');
        });
    }
};
