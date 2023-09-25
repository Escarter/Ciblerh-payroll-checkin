<?php

use App\Models\Absence;
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
        Schema::create('absences', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index();
            $table->foreignId('user_id')->index()->constrained();
            $table->foreignId('company_id')->index()->constrained();
            $table->foreignId('department_id')->index()->constrained();
            $table->date('absence_date');
            $table->longText('absence_reason');
            $table->string('attachment_path')->nullable();
            $table->tinyInteger('approval_status')->default(Absence::APPROVAL_STATUS_PENDING);
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
        Schema::dropIfExists('absences');
    }
};
