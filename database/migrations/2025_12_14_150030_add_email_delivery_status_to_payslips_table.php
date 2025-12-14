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
            $table->enum('email_delivery_status', ['pending', 'sent', 'delivered', 'bounced', 'complained'])
                  ->default('pending')
                  ->after('email_sent_status');
            $table->timestamp('email_sent_at')->nullable()->after('email_delivery_status');
            $table->timestamp('email_delivery_confirmed_at')->nullable()->after('email_sent_at');
            $table->timestamp('email_delivered_at')->nullable()->after('email_delivery_confirmed_at');
            $table->text('email_delivery_note')->nullable()->after('email_delivered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn([
                'email_delivery_status',
                'email_sent_at',
                'email_delivery_confirmed_at',
                'email_delivered_at',
                'email_delivery_note'
            ]);
        });
    }
};
