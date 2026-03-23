<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sftp_users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('password'); // stored plain-text (same policy as sftp_push_password)
            $table->string('home_directory')->default('storage/app/sftp-push'); // relative to base_path
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sftp_users');
    }
};
