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
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Name of the scheduled report
            $table->string('job_type'); // Report type (payslip_report, overtime_report, etc.)
            $table->string('report_format')->default('xlsx'); // xlsx, pdf, zip
            $table->json('filters'); // Report filters (company, department, date range, etc.)
            $table->json('report_config')->nullable(); // Additional report configuration
            $table->json('recipients'); // Array of email addresses to send to
            $table->string('frequency')->default('monthly'); // monthly, weekly, daily
            $table->integer('day_of_month')->default(1); // Day of month to run (1-28)
            $table->string('time')->default('09:00'); // Time to send the report (HH:mm format)
            $table->string('timezone')->default('Africa/Douala'); // Timezone
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->integer('run_count')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_reports');
    }
};
