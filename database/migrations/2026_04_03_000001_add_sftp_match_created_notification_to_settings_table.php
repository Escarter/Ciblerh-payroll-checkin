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
            $table->boolean('sftp_match_created_notification_enabled')->default(false)->after('sftp_auto_match_notification_email');
            $table->text('sftp_match_created_notification_email')->nullable()->after('sftp_match_created_notification_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'sftp_match_created_notification_enabled',
                'sftp_match_created_notification_email',
            ]);
        });
    }
};
