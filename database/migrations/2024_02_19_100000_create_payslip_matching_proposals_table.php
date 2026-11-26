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
        Schema::create('payslip_matching_proposals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // File information
            $table->string('file_path');
            $table->string('file_name');
            $table->integer('file_size')->nullable();
            $table->timestamp('file_timestamp')->nullable();
            
            // Proposed matches (JSON array of candidates)
            $table->json('proposed_match')->nullable();
            
            // Matched to (after validation)
            $table->unsignedBigInteger('matched_to_department_id')->nullable();
            $table->unsignedBigInteger('matched_to_company_id')->nullable();
            $table->integer('matched_month')->nullable();
            $table->integer('matched_year')->nullable();
            
            // Validation info
            $table->unsignedBigInteger('matched_by_user_id')->nullable();
            $table->timestamp('matched_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            
            // Status
            $table->enum('status', ['pending', 'validated', 'processed', 'rejected', 'failed'])->default('pending');
            $table->text('rejection_reason')->nullable();
            
            // Timestamps and soft deletes
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('matched_to_department_id')
                ->references('id')
                ->on('departments')
                ->nullOnDelete();
                
            $table->foreign('matched_to_company_id')
                ->references('id')
                ->on('companies')
                ->nullOnDelete();
                
            $table->foreign('matched_by_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            
            // Indexes
            $table->index('status');
            $table->index('created_at');
            $table->index('matched_to_department_id');
            $table->index('matched_to_company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslip_matching_proposals');
    }
};
