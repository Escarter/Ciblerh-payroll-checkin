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
        if(!Schema::hasColumn('users','author_id')){

            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('author_id')->index()->nullable()->constrained('users');
            });
        }
        if(!Schema::hasColumn('companies', 'author_id')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->foreignId('author_id')->index()->nullable()->constrained('users');
            });
        }
        if (!Schema::hasColumn('departments', 'author_id')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->foreignId('author_id')->index()->nullable()->constrained('users');
            });
        }
        if (!Schema::hasColumn('services', 'author_id')) {
            Schema::table('services', function (Blueprint $table) {
                $table->foreignId('author_id')->index()->nullable()->constrained('users');
            });
        }
        if (!Schema::hasColumn('tickings', 'author_id')) {
            Schema::table('tickings', function (Blueprint $table) {
                $table->foreignId('author_id')->index()->nullable()->constrained('users');
            });
        }
        if (!Schema::hasColumn('overtimes', 'author_id')) {
            Schema::table('overtimes', function (Blueprint $table) {
                $table->foreignId('author_id')->index()->nullable()->constrained('users');
            });
        }
        if (!Schema::hasColumn('absences', 'author_id')) {
            Schema::table('absences', function (Blueprint $table) {
                $table->foreignId('author_id')->index()->nullable()->constrained('users');
            });
        }
        if (!Schema::hasColumn('advance_salaries', 'author_id')) {
            Schema::table('advance_salaries', function (Blueprint $table) {
                $table->foreignId('author_id')->index()->nullable()->constrained('users');
            });
        }
        if (!Schema::hasColumn('audit_logs', 'author_id')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->foreignId('author_id')->index()->nullable()->constrained('users');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_id');
        });
        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_id');
        });
        Schema::table('departments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_id');
        });
        Schema::table('services', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_id');
        });
        Schema::table('tickings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_id');
        });
        Schema::table('overtimes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_id');
        });
        Schema::table('absences', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_id');
        });
        Schema::table('advance_salaries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_id');
        });
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_id');
        });
    }
};
