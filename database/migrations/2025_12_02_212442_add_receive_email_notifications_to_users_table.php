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
            $table->boolean('receive_email_notifications')->default(true)->after('receive_sms_notifications');
            $table->string('alternative_email')->nullable()->after('receive_email_notifications');
            $table->boolean('email_bounced')->default(false)->after('alternative_email');
            $table->timestamp('email_bounced_at')->nullable()->after('email_bounced');
            $table->text('email_bounce_reason')->nullable()->after('email_bounced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'receive_email_notifications',
                'alternative_email',
                'email_bounced',
                'email_bounced_at',
                'email_bounce_reason'
            ]);
        });
    }
};
