<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('complaint_id')
                  ->constrained('complaints')
                  ->onDelete('cascade');
            $table->foreignUuid('user_id') // User writing comment
                  ->constrained('users')
                  ->onDelete('cascade'); // Delete comment if user deleted
            $table->text('message');
            $table->timestamps();

            $table->index(['complaint_id', 'created_at']); // Index for sorting comments
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};