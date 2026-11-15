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
        Schema::table('settings', function (Blueprint $table) {
            $table->string('sftp_push_archive_frequency')->nullable()->after('sftp_push_scan_time');
            $table->string('sftp_push_archive_days')->nullable()->after('sftp_push_archive_frequency');
            $table->string('sftp_push_archive_time')->nullable()->after('sftp_push_archive_days');
            $table->unsignedInteger('sftp_push_archive_min_age_minutes')->default(5)->after('sftp_push_archive_time');
            $table->boolean('sftp_push_archive_move_processed')->default(true)->after('sftp_push_archive_min_age_minutes');
            $table->boolean('sftp_push_archive_move_rejected')->default(true)->after('sftp_push_archive_move_processed');
            $table->boolean('sftp_push_archive_move_failed')->default(false)->after('sftp_push_archive_move_rejected');
            $table->boolean('sftp_push_archive_require_successful_process')->default(true)->after('sftp_push_archive_move_failed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'sftp_push_archive_frequency',
                'sftp_push_archive_days',
                'sftp_push_archive_time',
                'sftp_push_archive_min_age_minutes',
                'sftp_push_archive_move_processed',
                'sftp_push_archive_move_rejected',
                'sftp_push_archive_move_failed',
                'sftp_push_archive_require_successful_process',
            ]);
        });
    }
};
