<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Add mailchimp to the smtp_provider enum
            $table->enum('smtp_provider', [
                'smtp',
                'mailpit',
                'ses',
                'mailgun',
                'postmark',
                'sendmail',
                'log',
                'array',
                'mailchimp',
            ])->default('smtp')->change();

            // Mailchimp Transactional (Mandrill) API key
            $table->string('mailchimp_api_key')->nullable()->after('log_channel');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->enum('smtp_provider', [
                'smtp',
                'mailpit',
                'ses',
                'mailgun',
                'postmark',
                'sendmail',
                'log',
                'array',
            ])->default('smtp')->change();

            $table->dropColumn('mailchimp_api_key');
        });
    }
};
