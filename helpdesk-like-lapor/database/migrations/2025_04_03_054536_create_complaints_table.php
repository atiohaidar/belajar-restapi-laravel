<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')
                  ->nullable() // User might be deleted
                  ->constrained('users')
                  ->onDelete('set null');
            $table->foreignUuid('agency_id')
                  ->nullable() // Might be unassigned or agency deleted
                  ->constrained('agencies')
                  ->onDelete('set null');
            $table->foreignUuid('category_id')
                  ->nullable() // Category might be deleted
                  ->constrained('complaint_categories')
                  ->onDelete('set null');
            $table->string('title');
            $table->text('description');
            $table->string('status')->default('Unprocessed'); // Validation for ('Unprocessed', 'Pending', etc.)
            $table->string('priority')->default('Medium'); // Validation for ('Low', 'Medium', 'High')
            $table->timestamps(); // created_at handled here

            // Indexes based on DDL examples
            $table->index('status');
            $table->index('agency_id');
            $table->index('user_id');
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};