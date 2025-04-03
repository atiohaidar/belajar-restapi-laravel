<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('complaint_id')
                  ->constrained('complaints')
                  ->onDelete('cascade');
            $table->foreignUuid('user_id') // User performing action (nullable for system)
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');
            $table->string('action'); // e.g., 'Created', 'Status Update'
            $table->text('details')->nullable(); // e.g., 'Status changed to Pending'
            $table->timestamp('timestamp'); // Specific timestamp for the log entry

            // No standard timestamps() needed if 'timestamp' field serves the purpose
            // $table->timestamps();

            $table->index(['complaint_id', 'timestamp']); // Index for sorting logs
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_logs');
    }
};