<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SMS delivery tracking, used to correlate Orange Cameroun (Ngage) delivery-report callbacks back to the
     * payslip that triggered the SMS. txnId/clientTxnId are returned/echoed by POST /api/v1/sms/send; the DR
     * webhook reports the final handset delivery status against those ids.
     */
    public function up(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->string('sms_txn_id')->nullable()->after('sms_status_note');
            $table->string('sms_client_txn_id')->nullable()->after('sms_txn_id');
            $table->string('sms_delivery_status')->nullable()->after('sms_client_txn_id');
            $table->timestamp('sms_sent_at')->nullable()->after('sms_delivery_status');
            $table->timestamp('sms_delivered_at')->nullable()->after('sms_sent_at');
            $table->text('sms_delivery_note')->nullable()->after('sms_delivered_at');

            $table->index('sms_txn_id');
            $table->index('sms_client_txn_id');
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropIndex(['sms_txn_id']);
            $table->dropIndex(['sms_client_txn_id']);
            $table->dropColumn([
                'sms_txn_id',
                'sms_client_txn_id',
                'sms_delivery_status',
                'sms_sent_at',
                'sms_delivered_at',
                'sms_delivery_note',
            ]);
        });
    }
};
