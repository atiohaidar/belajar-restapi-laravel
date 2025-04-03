<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('password'); // Remember to hash passwords
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('role'); // Use validation rules for ('Admin', 'Agency Manager', 'Reporter')
            $table->timestamp('last_login')->nullable();
            $table->timestamp('last_active')->nullable();
            $table->foreignUuid('agency_id')
                  ->nullable()
                  ->constrained('agencies')
                  ->onDelete('set null');
            $table->rememberToken(); // Standard Laravel field
            $table->timestamps();
        });

        // Add index for username lookup
        Schema::table('users', function (Blueprint $table) {
            $table->index('username');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};