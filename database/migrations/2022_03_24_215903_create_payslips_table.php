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
            $table->foreignId('author_id')->index()->nullable()->constrained('users');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('matricule')->nullable();
            $table->string('month')->nullable();
            $table->string('year')->nullable();
            $table->string('file')->nullable();
            $table->enum('email_sent_status',['pending','failed','successful'])->default('pending');
            $table->enum('sms_sent_status',['pending','failed','successful'])->default('pending');
            $table->longText('failure_reason')->nullable();
            $table->timestamps();
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
