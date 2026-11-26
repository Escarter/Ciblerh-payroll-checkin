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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'receive_sms_notifications')) {
                $table->boolean('receive_sms_notifications')->default(true)->after('personal_phone_number');
            }
            if (!Schema::hasColumn('users', 'receive_email_notifications')) {
                $table->boolean('receive_email_notifications')->default(true)->after('receive_sms_notifications');
            }
            if (!Schema::hasColumn('users', 'alternative_email')) {
                $table->string('alternative_email')->nullable()->after('receive_email_notifications');
            }
            if (!Schema::hasColumn('users', 'email_bounced')) {
                $table->boolean('email_bounced')->default(false)->after('alternative_email');
            }
            if (!Schema::hasColumn('users', 'email_bounced_at')) {
                $table->timestamp('email_bounced_at')->nullable()->after('email_bounced');
            }
            if (!Schema::hasColumn('users', 'email_bounce_reason')) {
                $table->text('email_bounce_reason')->nullable()->after('email_bounced_at');
            }
        });

        Schema::table('payslips', function (Blueprint $table) {
            if (!Schema::hasColumn('payslips', 'email_retry_count')) {
                $table->unsignedInteger('email_retry_count')->default(0)->after('email_sent_status');
            }
            if (!Schema::hasColumn('payslips', 'last_email_retry_at')) {
                $table->timestamp('last_email_retry_at')->nullable()->after('email_retry_count');
            }
            if (!Schema::hasColumn('payslips', 'email_status_note')) {
                $table->text('email_status_note')->nullable()->after('last_email_retry_at');
            }
            if (!Schema::hasColumn('payslips', 'email_bounced')) {
                $table->boolean('email_bounced')->default(false)->after('failure_reason');
            }
            if (!Schema::hasColumn('payslips', 'email_bounced_at')) {
                $table->timestamp('email_bounced_at')->nullable()->after('email_bounced');
            }
            if (!Schema::hasColumn('payslips', 'email_bounce_reason')) {
                $table->text('email_bounce_reason')->nullable()->after('email_bounced_at');
            }
            if (!Schema::hasColumn('payslips', 'email_bounce_type')) {
                $table->string('email_bounce_type')->nullable()->after('email_bounce_reason');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach ([
                'receive_sms_notifications',
                'receive_email_notifications',
                'alternative_email',
                'email_bounced',
                'email_bounced_at',
                'email_bounce_reason',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $columnsToDrop[] = $column;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        Schema::table('payslips', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach ([
                'email_retry_count',
                'last_email_retry_at',
                'email_status_note',
                'email_bounced',
                'email_bounced_at',
                'email_bounce_reason',
                'email_bounce_type',
            ] as $column) {
                if (Schema::hasColumn('payslips', $column)) {
                    $columnsToDrop[] = $column;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
