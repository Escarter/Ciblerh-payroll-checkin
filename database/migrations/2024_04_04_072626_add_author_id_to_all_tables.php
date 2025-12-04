<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add author_id to users table (self-referencing)
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('author_id')->index()->nullable()->constrained('users');
        });
        
        // Add author_id to all other tables
        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('author_id')->index()->nullable()->constrained('users');
        });
        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('author_id')->index()->nullable()->constrained('users');
        });
        Schema::table('services', function (Blueprint $table) {
            $table->foreignId('author_id')->index()->nullable()->constrained('users');
        });
        Schema::table('absences', function (Blueprint $table) {
            $table->foreignId('author_id')->index()->nullable()->constrained('users');
        });
        Schema::table('overtimes', function (Blueprint $table) {
            $table->foreignId('author_id')->index()->nullable()->constrained('users');
        });
        Schema::table('advance_salaries', function (Blueprint $table) {
            $table->foreignId('author_id')->index()->nullable()->constrained('users');
        });
        Schema::table('tickings', function (Blueprint $table) {
            $table->foreignId('author_id')->index()->nullable()->constrained('users');
        });
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreignId('author_id')->index()->nullable()->constrained('users');
        });
        Schema::table('leaves', function (Blueprint $table) {
            $table->foreignId('author_id')->index()->nullable()->constrained('users');
        });
        Schema::table('send_payslip_processes', function (Blueprint $table) {
            $table->foreignId('author_id')->index()->nullable()->constrained('users');
        });
        Schema::table('payslips', function (Blueprint $table) {
            $table->foreignId('author_id')->index()->nullable()->constrained('users');
        });
        Schema::table('leave_types', function (Blueprint $table) {
            $table->foreignId('author_id')->index()->nullable()->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop author_id from all tables (in reverse order)
        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_id');
        });
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_id');
        });
        Schema::table('send_payslip_processes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_id');
        });
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_id');
        });
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_id');
        });
        Schema::table('tickings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_id');
        });
        Schema::table('advance_salaries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_id');
        });
        Schema::table('overtimes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_id');
        });
        Schema::table('absences', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_id');
        });
        Schema::table('services', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_id');
        });
        Schema::table('departments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_id');
        });
        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_id');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_id');
        });
    }
};