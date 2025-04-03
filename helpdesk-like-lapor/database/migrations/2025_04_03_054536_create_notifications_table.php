<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')
                  ->constrained('users')
                  ->onDelete('cascade'); // Delete notification if user deleted
            $table->foreignUuid('complaint_id')
                  ->nullable() // Notification might not relate to a specific complaint
                  ->constrained('complaints')
                  ->onDelete('set null'); // Keep notification if complaint deleted
            $table->text('message');
            $table->boolean('is_read')->default(false); // 0/1 handled by boolean type
            $table->timestamps(); // created_at handled here

            $table->index(['user_id', 'is_read']); // Index for querying unread notifications
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};