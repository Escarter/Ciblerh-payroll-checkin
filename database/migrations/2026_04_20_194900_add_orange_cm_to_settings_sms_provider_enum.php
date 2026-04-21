<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (config('database.default') !== 'sqlite') {
            DB::statement("ALTER TABLE settings MODIFY COLUMN sms_provider ENUM('twilio','nexah','aws_sns','orange_cm') NOT NULL DEFAULT 'aws_sns'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') !== 'sqlite') {
            DB::table('settings')->where('sms_provider', 'orange_cm')->update(['sms_provider' => 'aws_sns']);
            DB::statement("ALTER TABLE settings MODIFY COLUMN sms_provider ENUM('twilio','nexah','aws_sns') NOT NULL DEFAULT 'aws_sns'");
        }
    }
};
