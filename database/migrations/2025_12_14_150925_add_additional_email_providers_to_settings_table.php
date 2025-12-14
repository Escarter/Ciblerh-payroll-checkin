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
        Schema::table('settings', function (Blueprint $table) {
            // Update smtp_provider enum to include all supported providers
            $table->enum('smtp_provider', [
                'smtp',
                'mailpit',
                'ses',
                'mailgun',
                'postmark',
                'sendmail',
                'log',
                'array'
            ])->default('smtp')->change();

            // AWS SES configuration
            $table->string('ses_key')->nullable();
            $table->string('ses_secret')->nullable();
            $table->string('ses_region')->nullable();

            // Postmark configuration
            $table->string('postmark_token')->nullable();

            // Sendmail configuration
            $table->string('sendmail_path')->nullable();

            // Mailpit configuration (for development)
            $table->string('mailpit_host')->nullable();
            $table->string('mailpit_port')->nullable();

            // Log channel for log driver
            $table->string('log_channel')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Revert smtp_provider enum to original
            $table->enum('smtp_provider', ['smtp', 'mailpit', 'ses', 'mailgun'])->default('smtp')->change();

            // Remove new provider fields
            $table->dropColumn([
                'ses_key',
                'ses_secret',
                'ses_region',
                'postmark_token',
                'sendmail_path',
                'mailpit_host',
                'mailpit_port',
                'log_channel'
            ]);
        });
    }
};