<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('sftp_os_username')->nullable()->after('sftp_credentials_generated_at');
            $table->string('sftp_os_password')->nullable()->after('sftp_os_username');
            $table->string('sftp_server_host')->nullable()->after('sftp_os_password');
            $table->unsignedSmallInteger('sftp_server_port')->default(22)->after('sftp_server_host');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['sftp_os_username', 'sftp_os_password', 'sftp_server_host', 'sftp_server_port']);
        });
    }
};
