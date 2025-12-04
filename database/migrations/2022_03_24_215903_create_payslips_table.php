<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayslipsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('send_payslip_process_id')->nullable();
            $table->foreignId('employee_id')->nullable();
            $table->foreignId('company_id')->nullable()->constrained();
            $table->foreignId('department_id')->nullable()->constrained();
            $table->foreignId('service_id')->nullable()->constrained();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('matricule')->nullable();
            $table->string('month')->nullable();
            $table->string('year')->nullable();
            $table->string('file')->nullable();
            $table->tinyInteger('encryption_status')->default(0);
            $table->tinyInteger('email_sent_status')->default(0);
            $table->unsignedInteger('email_retry_count')->default(0);
            $table->timestamp('last_email_retry_at')->nullable();
            $table->text('email_status_note')->nullable();
            $table->tinyInteger('sms_sent_status')->default(0);
            $table->text('sms_status_note')->nullable();
            $table->boolean('email_bounced')->default(false);
            $table->timestamp('email_bounced_at')->nullable();
            $table->text('email_bounce_reason')->nullable();
            $table->string('email_bounce_type')->nullable();
            $table->longText('failure_reason')->nullable();
            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payslips');
    }
}
