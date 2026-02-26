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
        Schema::table('payslip_matching_proposals', function (Blueprint $table) {
            // Store the local file path after downloading from SFTP
            $table->string('local_file_path')->nullable()->after('file_path');
            
            // Track download status and any errors
            $table->enum('download_status', ['pending', 'downloaded', 'failed'])->default('pending')->after('local_file_path');
            $table->text('download_error')->nullable()->after('download_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payslip_matching_proposals', function (Blueprint $table) {
            $table->dropColumn(['local_file_path', 'download_status', 'download_error']);
        });
    }
};
