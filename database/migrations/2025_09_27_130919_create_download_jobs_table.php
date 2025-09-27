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
        Schema::create('download_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Report Type & Configuration
            $table->string('job_type'); // 'bulk_payslip_download', 'payslip_report', 'overtime_report', 'checklog_report', etc.
            $table->string('report_format')->default('xlsx'); // 'xlsx', 'pdf', 'zip'
            $table->json('filters'); // Store all filter criteria
            $table->json('report_config'); // Additional report configuration
            
            // Job Status & Progress
            $table->string('status')->default('pending'); // pending, processing, completed, failed, cancelled
            $table->integer('total_records')->default(0); // Total records to process
            $table->integer('processed_records')->default(0); // Records processed
            $table->integer('failed_records')->default(0); // Failed records
            
            // File Information
            $table->string('file_path')->nullable(); // Path to generated file
            $table->string('file_name')->nullable(); // Original filename
            $table->integer('file_size')->nullable(); // Size in bytes
            $table->string('mime_type')->nullable(); // File MIME type
            
            // Error Handling
            $table->text('error_message')->nullable();
            $table->json('error_details')->nullable(); // Detailed error information
            
            // Timing
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // Auto-cleanup
            
            // Metadata
            $table->json('metadata')->nullable(); // Additional metadata
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['job_type', 'status']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('download_jobs');
    }
};