<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('complaint_id')
                  ->constrained('complaints')
                  ->onDelete('cascade'); // Delete attachment if complaint is deleted
            $table->string('file_path'); // Path or URL
            $table->string('file_name'); // Original file name
            $table->string('mime_type')->nullable();
            $table->timestamp('uploaded_at');
            // $table->timestamps(); // Usually not needed if uploaded_at serves the purpose
            $table->index('complaint_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_attachments');
    }
};