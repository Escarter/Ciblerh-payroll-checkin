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
            $table->boolean('sftp_auto_match_enabled')->default(false)->after('sftp_matching_strategies');
            $table->unsignedTinyInteger('sftp_auto_match_threshold')->default(80)->after('sftp_auto_match_enabled');
            $table->string('sftp_auto_match_min_strategy')->default('reverse_partial_match')->after('sftp_auto_match_threshold');
            $table->string('sftp_auto_match_notification_email')->nullable()->after('sftp_auto_match_min_strategy');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'sftp_auto_match_enabled',
                'sftp_auto_match_threshold',
                'sftp_auto_match_min_strategy',
                'sftp_auto_match_notification_email',
            ]);
        });
    }
};
