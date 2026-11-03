<?php

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
        Schema::table('users', function (Blueprint $table) {
            // Add column for tracking last payslip received
            if (!Schema::hasColumn('users', 'last_payslip_received_at')) {
                $table->timestamp('last_payslip_received_at')->nullable()->after('email');
            }
            
            // Add column for tracking deactivation
            if (!Schema::hasColumn('users', 'deactivated_at')) {
                $table->timestamp('deactivated_at')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_payslip_received_at', 'deactivated_at']);
        });
    }
};
