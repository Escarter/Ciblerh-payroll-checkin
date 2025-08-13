<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('manager_companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manager_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('company_id')
                ->constrained('companies')
                ->onDelete('cascade');
            $table->timestamps();
            $table->unique(['manager_id', 'company_id']);
        });
    }
    public function down() {
        Schema::dropIfExists('manager_companies');
    }
};
