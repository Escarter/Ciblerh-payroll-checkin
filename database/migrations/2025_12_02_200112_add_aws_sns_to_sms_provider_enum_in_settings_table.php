<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite doesn't support MODIFY COLUMN with ENUM
        // For SQLite, we skip this migration as it doesn't enforce enum constraints
        if (DB::getDriverName() !== 'sqlite') {
            // Modify the enum to include 'aws_sns'
            DB::statement("ALTER TABLE settings MODIFY COLUMN sms_provider ENUM('twilio', 'nexah', 'aws_sns') DEFAULT 'nexah'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // SQLite doesn't support MODIFY COLUMN with ENUM
        if (DB::getDriverName() !== 'sqlite') {
            // Revert back to original enum values
            DB::statement("ALTER TABLE settings MODIFY COLUMN sms_provider ENUM('twilio', 'nexah') DEFAULT 'nexah'");
        }
    }
};
