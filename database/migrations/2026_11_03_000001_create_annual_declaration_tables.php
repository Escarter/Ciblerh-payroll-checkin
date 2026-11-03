<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('send_annual_declaration_processes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('author_id')->nullable();
            $table->foreignId('company_id')->nullable()->constrained();
            $table->foreignId('department_id')->nullable()->constrained();
            $table->longText('raw_file')->nullable();
            $table->string('destination_directory')->nullable();
            $table->unsignedSmallInteger('year');
            $table->string('status')->default('processing');
            $table->unsignedTinyInteger('percentage_completion')->nullable();
            $table->unsignedInteger('total_pages')->default(0);
            $table->longText('failure_reason')->nullable();
            $table->string('batch_id')->nullable();
            $table->string('sftp_proposal_id', 36)->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'year']);
        });

        Schema::create('annual_declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('send_annual_declaration_process_id')->nullable()->constrained('send_annual_declaration_processes')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable()->index();
            $table->foreignId('company_id')->nullable()->constrained();
            $table->foreignId('department_id')->nullable()->constrained();
            $table->unsignedInteger('page_number')->nullable();
            // matched = sendable, unmatched = no employee found, empty = page without amounts (review only)
            $table->string('page_status', 20)->default('matched')->index();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('extracted_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('matricule')->nullable()->index();
            $table->string('cnps_number')->nullable();
            $table->unsignedSmallInteger('year');
            $table->decimal('gross_amount', 16, 2)->nullable();
            $table->decimal('tax_total', 16, 2)->nullable();
            $table->string('file')->nullable();
            $table->tinyInteger('encryption_status')->default(0);
            $table->text('encryption_status_note')->nullable();
            $table->tinyInteger('email_sent_status')->default(0);
            $table->string('email_delivery_status')->nullable();
            $table->unsignedInteger('email_retry_count')->default(0);
            $table->timestamp('last_email_retry_at')->nullable();
            $table->timestamp('email_sent_at')->nullable();
            $table->text('email_status_note')->nullable();
            $table->boolean('email_bounced')->default(false);
            $table->text('email_bounce_reason')->nullable();
            $table->tinyInteger('sms_sent_status')->default(0);
            $table->text('sms_status_note')->nullable();
            $table->timestamp('sms_sent_at')->nullable();
            $table->longText('failure_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'matricule', 'year']);
        });

        Schema::table('payslip_matching_proposals', function (Blueprint $table) {
            $table->string('document_type', 30)->default('payslip')->after('file_name')->index();
        });
    }

    public function down(): void
    {
        Schema::table('payslip_matching_proposals', function (Blueprint $table) {
            $table->dropColumn('document_type');
        });
        Schema::dropIfExists('annual_declarations');
        Schema::dropIfExists('send_annual_declaration_processes');
    }
};
