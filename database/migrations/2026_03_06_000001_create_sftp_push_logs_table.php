<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Create audit trail for SFTP push uploads
     */
    public function up(): void
    {
        Schema::create('sftp_push_logs', function (Blueprint $table) {
            $table->id();
            $table->string('username'); // Username used for authentication
            $table->string('filename'); // Original filename uploaded
            $table->string('stored_filename')->nullable(); // Filename stored locally
            $table->integer('file_size')->default(0); // Size in bytes
            $table->string('remote_ip'); // IP address of uploader
            $table->enum('status', ['success', 'failed', 'rejected'])->default('success');
            $table->text('error_message')->nullable(); // Error details if failed
            $table->string('rejection_reason')->nullable(); // Reason for rejection (e.g., not PDF, duplicate)
            $table->timestamps(); // created_at, updated_at
            
            // Indexes for performance
            $table->index('username');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sftp_push_logs');
    }
};
