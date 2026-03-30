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
        Schema::table('credential_tokens', function (Blueprint $table) {
            $table->unsignedBigInteger('import_job_id')->nullable()->after('user_id');
            $table->foreign('import_job_id')->references('id')->on('import_jobs')->nullOnDelete();
            $table->index(['import_job_id', 'used', 'expires_at'], 'credential_tokens_import_job_pending_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credential_tokens', function (Blueprint $table) {
            $table->dropIndex('credential_tokens_import_job_pending_idx');
            $table->dropForeign(['import_job_id']);
            $table->dropColumn('import_job_id');
        });
    }
};
