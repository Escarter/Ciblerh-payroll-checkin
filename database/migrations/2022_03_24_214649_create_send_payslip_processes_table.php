<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSendPayslipProcessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('send_payslip_processes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreignId('company_id')->nullable()->constrained();
            $table->foreignId('department_id')->nullable()->constrained();
            $table->foreignId('service_id')->nullable()->constrained();
            $table->longText('raw_file')->nullable();
            $table->string('destination_directory')->nullable();
            $table->integer('percentage_completion')->nullable();
            $table->string('month')->nullable();
            $table->string('year')->nullable();
            $table->enum('status',['processing','failed','successful'])->default('processing');
            $table->longText('failure_reason')->nullable();
            $table->string('batch_id')->nullable();
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
        Schema::dropIfExists('send_payslip_processes');
    }
}
