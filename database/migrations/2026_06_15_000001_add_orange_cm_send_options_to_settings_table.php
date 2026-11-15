<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Orange Cameroun (Business Messaging Ngage) per-company send options: SMS category, country, and the
     * optional delivery-report callback URL. These map to the mandatory `category`/`country` body fields and
     * the optional `drCallback` field of POST /api/v1/sms/send.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('sms_orange_category')->nullable()->after('sms_provider_senderid');
            $table->string('sms_orange_country')->nullable()->after('sms_orange_category');
            $table->string('sms_orange_dr_callback')->nullable()->after('sms_orange_country');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['sms_orange_category', 'sms_orange_country', 'sms_orange_dr_callback']);
        });
    }
};
