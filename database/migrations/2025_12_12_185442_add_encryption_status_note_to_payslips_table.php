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
            $table->text('encryption_status_note')->nullable()->after('encryption_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn('encryption_status_note');
        });
    }
};
