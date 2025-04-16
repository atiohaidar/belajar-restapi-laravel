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
        Schema::table('tickets', function (Blueprint $table) {
            $table->boolean('is_read')->default(false)->after('assigned_to');
            $table->timestamp('read_at')->nullable()->after('is_read');
            $table->foreignId('read_by')->nullable()->after('read_at')->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['read_by']);
            $table->dropColumn(['is_read', 'read_at', 'read_by']);
        });
    }
};
