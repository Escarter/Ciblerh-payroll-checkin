<?php

use App\Models\Ticking;
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
        Schema::create('tickings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('user_full_name');
            $table->string('matricule');
            $table->string('email');
            $table->string('phone_number');
            $table->foreignId('company_id')->index()->nullable()->constrained();
            $table->string('company_name');
            $table->foreignId('department_id')->nullable()->constrained();
            $table->string('department_name');
            $table->foreignId('service_id')->nullable()->constrained();
            $table->string('service_name');
            $table->longText('checkin_comments')->nullable();
            $table->longText('checkout_comments')->nullable();
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->tinyInteger('supervisor_approval_status')->default(Ticking::SUPERVISOR_APPROVAL_PENDING);
            $table->longText('supervisor_approval_reason')->nullable();
            $table->tinyInteger('manager_approval_status')->default(Ticking::MANAGER_APPROVAL_PENDING);
            $table->longText('manager_approval_reason')->nullable();
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
        Schema::dropIfExists('tickings');
    }
};
