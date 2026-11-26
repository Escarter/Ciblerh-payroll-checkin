<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('annual_declarations', function (Blueprint $table) {
            $table->string('sms_txn_id')->nullable()->index();
            $table->string('sms_client_txn_id')->nullable()->index();
            $table->string('sms_delivery_status', 20)->nullable();
            $table->text('sms_delivery_note')->nullable();
            $table->timestamp('sms_delivered_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('annual_declarations', function (Blueprint $table) {
            $table->dropColumn(['sms_txn_id', 'sms_client_txn_id', 'sms_delivery_status', 'sms_delivery_note', 'sms_delivered_at']);
        });
    }
};
