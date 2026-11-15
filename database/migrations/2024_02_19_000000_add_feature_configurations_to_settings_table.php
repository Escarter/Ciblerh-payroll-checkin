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
            // Feature flags
            $table->boolean('inactivity_deactivation_enabled')->default(false);
            $table->boolean('sftp_sync_enabled')->default(false);

            // Inactivity deactivation configuration
            $table->integer('inactivity_months_threshold')->default(6);
            $table->string('deactivation_check_time')->default('02:00');

            // SFTP configuration
            $table->string('sftp_host')->nullable();
            $table->integer('sftp_port')->default(22);
            $table->string('sftp_username')->nullable();
            $table->string('sftp_password')->nullable();
            $table->string('sftp_private_key_path')->nullable();
            $table->string('sftp_passphrase')->nullable();
            $table->string('sftp_root')->default('/payslips');
            
            // SFTP sync configuration
            $table->enum('sftp_sync_frequency', ['hourly', 'daily', 'weekly'])->default('daily');
            $table->enum('sftp_auth_type', ['password', 'ssh_key'])->default('password');
            
            // SFTP matching strategies (JSON for storing selected strategies)
            $table->json('sftp_matching_strategies')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'inactivity_deactivation_enabled',
                'sftp_sync_enabled',
                'inactivity_months_threshold',
                'deactivation_check_time',
                'sftp_host',
                'sftp_port',
                'sftp_username',
                'sftp_password',
                'sftp_private_key_path',
                'sftp_passphrase',
                'sftp_root',
                'sftp_sync_frequency',
                'sftp_auth_type',
                'sftp_matching_strategies',
            ]);
        });
    }
};
