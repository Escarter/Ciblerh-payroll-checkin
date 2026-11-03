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
        // Use raw statement for MySQL, skip for SQLite
        if (config('database.default') !== 'sqlite') {
            DB::statement("ALTER TABLE send_payslip_processes MODIFY COLUMN status ENUM('processing','failed','successful','cancelled') NOT NULL DEFAULT 'processing'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE send_payslip_processes MODIFY COLUMN status ENUM('processing','failed','successful') NOT NULL DEFAULT 'processing'");
    }
};
