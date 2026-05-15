<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Add public_token column to proposals.
     *
     * AC-3 / AC-17 (WB-022): Each proposal gets a unique, URL-safe token used
     * for shareable links and as the clone's distinct identifier.
     *
     * The column is NOT NULL because every proposal (new or cloned) must have
     * a token.  Existing rows are back-filled with a random UUID before the
     * unique constraint is added.
     */
    public function up(): void
    {
        // 1 — Add nullable first so existing rows don't violate NOT NULL
        Schema::table('proposals', function (Blueprint $table) {
            $table->string('public_token', 64)->nullable()->after('status');
        });

        // 2 — Back-fill existing rows with a unique UUID-based token
        DB::table('proposals')->whereNull('public_token')->lazyById()->each(
            fn ($row) => DB::table('proposals')
                ->where('id', $row->id)
                ->update(['public_token' => Str::uuid()->toString()])
        );

        // 3 — Now enforce NOT NULL + unique
        Schema::table('proposals', function (Blueprint $table) {
            $table->string('public_token', 64)->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropUnique(['public_token']);
            $table->dropColumn('public_token');
        });
    }
};
