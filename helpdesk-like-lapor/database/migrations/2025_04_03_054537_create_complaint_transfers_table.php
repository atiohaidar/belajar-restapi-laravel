<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('complaint_id')
                  ->constrained('complaints')
                  ->onDelete('cascade');
            $table->foreignUuid('from_agency_id')
                  ->nullable() // Can be null if initially unassigned
                  ->constrained('agencies')
                  ->onDelete('set null');
            $table->foreignUuid('to_agency_id')
                  ->constrained('agencies')
                  ->onDelete('cascade'); // Or set null? Cascade means transfer history gone if agency deleted.
            $table->foreignUuid('user_id') // User initiating transfer
                    ->nullable()
                  ->constrained('users')
                  ->onDelete('set null'); // Keep history even if user deleted
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_transfers');
    }
};