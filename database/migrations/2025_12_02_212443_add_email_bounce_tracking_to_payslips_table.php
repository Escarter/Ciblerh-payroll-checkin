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
            $table->boolean('email_bounced')->default(false)->after('last_email_retry_at');
            $table->timestamp('email_bounced_at')->nullable()->after('email_bounced');
            $table->text('email_bounce_reason')->nullable()->after('email_bounced_at');
            $table->string('email_bounce_type')->nullable()->after('email_bounce_reason'); // 'hard', 'soft', 'complaint'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn([
                'email_bounced',
                'email_bounced_at',
                'email_bounce_reason',
                'email_bounce_type'
            ]);
        });
    }
};
