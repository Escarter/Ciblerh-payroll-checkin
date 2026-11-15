<?php

use App\Models\AdvanceSalary;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('advance_salaries', function (Blueprint $table) {
            $table->string('type')->default('advance')->after('department_id'); // advance | loan
            $table->date('advance_for_month')->nullable()->after('repayment_to_month'); // for advance: one per month rule
            $table->string('attachment_path')->nullable()->after('beneficiary_id_card_number'); // for loan supporting docs
            $table->bigInteger('amount_repaid')->default(0)->after('net_salary');
            $table->boolean('is_fully_repaid')->default(false)->after('amount_repaid');
            $table->tinyInteger('supervisor_approval_status')->nullable()->after('approval_reason');
            $table->longText('supervisor_approval_reason')->nullable()->after('supervisor_approval_status');
            $table->tinyInteger('manager_approval_status')->nullable()->after('supervisor_approval_reason');
            $table->longText('manager_approval_reason')->nullable()->after('manager_approval_status');
        });

        // Backfill: existing records use approval_status for both supervisor and manager
        DB::table('advance_salaries')->whereNull('supervisor_approval_status')->update([
            'supervisor_approval_status' => DB::raw('approval_status'),
            'manager_approval_status' => DB::raw('approval_status'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('advance_salaries', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'advance_for_month',
                'attachment_path',
                'amount_repaid',
                'is_fully_repaid',
                'supervisor_approval_status',
                'supervisor_approval_reason',
                'manager_approval_status',
                'manager_approval_reason',
            ]);
        });
    }
};
