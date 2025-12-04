<?php

use App\Models\Leave;
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
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->index();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->foreignId('company_id')->index()->constrained();
            $table->foreignId('department_id')->index()->constrained();
            $table->foreignId('leave_type_id')->index()->constrained();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->longText('leave_reason')->nullable();
            $table->tinyInteger('supervisor_approval_status')->default(Leave::SUPERVISOR_APPROVAL_PENDING);
            $table->longText('supervisor_approval_reason')->nullable();
            $table->tinyInteger('manager_approval_status')->default(Leave::MANAGER_APPROVAL_PENDING);
            $table->longText('manager_approval_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
