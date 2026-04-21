<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Messaging Pro Cameroon login (api.orange.cm/authenticate), separate from OAuth Client ID/Secret.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('sms_msp_username')->nullable()->after('sms_provider_app_id');
            $table->string('sms_msp_password')->nullable()->after('sms_msp_username');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['sms_msp_username', 'sms_msp_password']);
        });
    }
};
