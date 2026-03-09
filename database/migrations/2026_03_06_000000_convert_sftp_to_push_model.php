<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Convert SFTP from PULL model (fetch from remote) to PUSH model (receive pushed files)
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Drop remote SFTP pull columns
            $table->dropColumn([
                'sftp_host',
                'sftp_port',
                'sftp_password',
                'sftp_private_key_path',
                'sftp_passphrase',
                'sftp_auth_type',
            ]);

            // Add SFTP push model columns
            $table->string('sftp_push_username')->nullable()->after('sftp_sync_enabled');
            $table->string('sftp_push_password')->nullable()->after('sftp_push_username');
            $table->string('sftp_push_path')->default('storage/app/sftp-push')->after('sftp_push_password');
            $table->timestamp('sftp_credentials_generated_at')->nullable()->after('sftp_push_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Restore remote SFTP columns
            $table->string('sftp_host')->nullable();
            $table->integer('sftp_port')->default(22);
            $table->string('sftp_password')->nullable();
            $table->string('sftp_private_key_path')->nullable();
            $table->string('sftp_passphrase')->nullable();
            $table->enum('sftp_auth_type', ['password', 'ssh_key'])->default('password');

            // Drop push model columns
            $table->dropColumn([
                'sftp_push_username',
                'sftp_push_password',
                'sftp_push_path',
                'sftp_credentials_generated_at',
            ]);
        });
    }
};
