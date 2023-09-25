<?php

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index();
            $table->foreignId('company_id')->nullable()->constrained();
            $table->foreignId('department_id')->nullable()->constrained();
            $table->foreignId('service_id')->nullable()->constrained();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('username')->nullable();
            $table->string('professional_phone_number')->nullable();
            $table->string('personal_phone_number')->nullable();
            $table->string('matricule');
            $table->string('pdf_password')->nullable();
            $table->string('position')->nullable();
            $table->enum('preferred_language',['en','fr'])->default('en');
            $table->string('signature_path')->nullable();
            $table->string('salary_grade')->nullable();
            $table->bigInteger('net_salary')->nullable();
            $table->date('contract_end')->nullable();
            $table->time('work_start_time',0)->default('08:00:00');
            $table->time('work_end_time',0)->default('17:30:00');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->tinyInteger('status')->default(User::STATUS_ACTIVE);
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
};
