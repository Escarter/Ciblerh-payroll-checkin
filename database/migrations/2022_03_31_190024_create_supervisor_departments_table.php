<?php

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
        Schema::create('supervisor_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supervisor_id')->index()->constrained('users');
            $table->foreignId('department_id')->index()->constrained();
            $table->timestamps();
            $table->unique(['department_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('supervisor_departments');
    }
};
