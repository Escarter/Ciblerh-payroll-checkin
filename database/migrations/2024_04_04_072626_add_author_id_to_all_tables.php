<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        foreach ([
            'users',
            'companies',
            'departments',
            'services',
            'absences',
            'overtimes',
            'advance_salaries',
            'tickings',
            'audit_logs',
            'leaves',
            'send_payslip_processes',
            'payslips',
            'leave_types',
        ] as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'author_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('author_id')->index()->nullable()->constrained('users');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (DB::table('migrations')->where('migration', '2022_04_04_072626_add_author_id_to_all_tables')->exists()) {
            return;
        }

        foreach ([
            'leave_types',
            'payslips',
            'send_payslip_processes',
            'leaves',
            'audit_logs',
            'tickings',
            'advance_salaries',
            'overtimes',
            'absences',
            'services',
            'departments',
            'companies',
            'users',
        ] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'author_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropConstrainedForeignId('author_id');
                });
            }
        }
    }
};