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
        Schema::table('payslips', function (Blueprint $table) {
            $table->unsignedInteger('email_retry_count')->default(0)->after('email_sent_status');
            $table->timestamp('last_email_retry_at')->nullable()->after('email_retry_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn(['email_retry_count', 'last_email_retry_at']);
        });
    }
};
