<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('import_jobs') && !Schema::hasColumn('import_jobs', 'deleted_at')) {
            Schema::table('import_jobs', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (
            DB::table('migrations')->where('migration', '2026_05_11_105752_create_import_jobs_table')->exists()
            || !Schema::hasTable('import_jobs')
            || !Schema::hasColumn('import_jobs', 'deleted_at')
        ) {
            return;
        }

        Schema::table('import_jobs', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
