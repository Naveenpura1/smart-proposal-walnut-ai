<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // The Sales Rep
            $table->string('client_name');
            $table->string('industry');
            $table->text('pain_points');
            $table->decimal('deal_size', 15, 2);
            $table->text('generated_content')->nullable(); // For Walnut AI output
            $table->enum('status', ['Draft', 'Sent', 'Accepted'])->default('Draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
