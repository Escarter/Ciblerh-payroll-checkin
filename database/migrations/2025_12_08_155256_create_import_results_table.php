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
        Schema::create('import_results', function (Blueprint $table) {
            $table->id();
            $table->string('import_id')->unique();
            $table->string('import_type'); // employees, departments, etc.
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('file_name');
            $table->string('file_path')->nullable();
            $table->integer('total_rows')->default(0);
            $table->integer('imported_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->boolean('success')->default(false);
            $table->text('error_message')->nullable();
            $table->json('validation_errors')->nullable(); // Store detailed errors
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');

            $table->index(['user_id', 'import_type']);
            $table->index(['success', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_results');
    }
};
