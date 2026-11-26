<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Frequency type: everyMinute, everyFiveMinutes, everyTenMinutes,
            // everyThirtyMinutes, hourly, daily, weekly, monthly, everyOddHour,
            // or 'custom_days' for specific days of the week.
            $table->string('sftp_push_scan_frequency')->default('everyFiveMinutes')->after('sftp_push_path');

            // Optional: comma-separated day numbers (0=Sun…6=Sat) used when frequency = 'custom_days'
            $table->string('sftp_push_scan_days')->nullable()->after('sftp_push_scan_frequency');

            // Optional: HH:MM time used for daily / weekly / monthly / custom_days runs
            $table->string('sftp_push_scan_time')->default('00:00')->after('sftp_push_scan_days');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['sftp_push_scan_frequency', 'sftp_push_scan_days', 'sftp_push_scan_time']);
        });
    }
};
