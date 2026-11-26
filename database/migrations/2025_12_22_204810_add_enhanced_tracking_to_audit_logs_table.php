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
        Schema::table('audit_logs', function (Blueprint $table) {
            // Model tracking (similar to StratagemAI)
            $table->string('model_type')->nullable()->after('action_type');
            $table->string('model_id')->nullable()->after('model_type');
            $table->string('model_name')->nullable()->after('model_id');
            
            // Change tracking
            $table->json('old_values')->nullable()->after('action_perform');
            $table->json('new_values')->nullable()->after('old_values');
            $table->json('changes')->nullable()->after('new_values');
            
            // Request context tracking
            $table->string('ip_address', 45)->nullable()->after('department_id');
            $table->text('user_agent')->nullable()->after('ip_address');
            $table->string('url')->nullable()->after('user_agent');
            $table->string('method', 10)->nullable()->after('url');
            
            // Additional metadata
            $table->json('metadata')->nullable()->after('method');
            
            // Indexes for performance
            $table->index(['model_type', 'model_id']);
            $table->index('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['model_type', 'model_id']);
            $table->dropIndex(['ip_address']);
            
            $table->dropColumn([
                'model_type',
                'model_id',
                'model_name',
                'old_values',
                'new_values',
                'changes',
                'ip_address',
                'user_agent',
                'url',
                'method',
                'metadata',
            ]);
        });
    }
};
