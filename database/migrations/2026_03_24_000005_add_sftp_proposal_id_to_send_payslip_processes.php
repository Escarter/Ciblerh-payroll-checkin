<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('send_payslip_processes', function (Blueprint $table) {
            // Direct link to the SFTP matching proposal that triggered this process.
            // Replaces fragile path/company/month/year matching in PayslipSendingPlan.
            $table->uuid('sftp_proposal_id')->nullable()->after('raw_file');
        });
    }

    public function down(): void
    {
        Schema::table('send_payslip_processes', function (Blueprint $table) {
            $table->dropColumn('sftp_proposal_id');
        });
    }
};
