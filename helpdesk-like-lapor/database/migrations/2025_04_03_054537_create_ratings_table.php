<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id') // User giving rating
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->foreignUuid('agency_id') // Agency being rated
                  ->constrained('agencies')
                  ->onDelete('cascade');
            $table->foreignUuid('complaint_id') // Optional link to complaint
                  ->nullable()
                  ->constrained('complaints')
                  ->onDelete('set null');
            $table->tinyInteger('stars'); // Validation for 1-5 stars
            $table->text('review')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};