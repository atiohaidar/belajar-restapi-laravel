<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_follow_ups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('complaint_id')
                  ->constrained('complaints')
                  ->onDelete('cascade');
            $table->foreignUuid('user_id') // User performing the follow-up
                  ->constrained('users')
                  ->onDelete('cascade'); // Or set null if user is deleted? Cascade seems reasonable.
            $table->foreignUuid('agency_id') // Agency context if needed
                  ->nullable()
                  ->constrained('agencies')
                  ->onDelete('set null');
            $table->text('description');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_follow_ups');
    }
};