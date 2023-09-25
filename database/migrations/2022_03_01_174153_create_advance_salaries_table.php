<?php

use App\Models\AdvanceSalary;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('advance_salaries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index();
            $table->foreignId('user_id')->index()->constrained();
            $table->foreignId('company_id')->index()->constrained();
            $table->foreignId('department_id')->index()->constrained();
            $table->bigInteger('amount');
            $table->longText('reason')->nullable();
            $table->dateTime('repayment_from_month');
            $table->dateTime('repayment_to_month');
            $table->string('beneficiary_name');
            $table->string('beneficiary_mobile_money_number');
            $table->string('beneficiary_id_card_number');
            $table->bigInteger('net_salary')->nullable();
            $table->tinyInteger('approval_status')->default(AdvanceSalary::APPROVAL_STATUS_PENDING);
            $table->longText('approval_reason')->nullable();
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
        Schema::dropIfExists('advance_salaries');
    }
};
