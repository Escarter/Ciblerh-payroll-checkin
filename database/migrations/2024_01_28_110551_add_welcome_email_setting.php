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
        Schema::table('settings', function (Blueprint $table) {
            // Welcome email
            $table->string('welcome_email_subject_fr')->nullable();
            $table->string('welcome_email_subject_en')->nullable();

            $table->longText('welcome_email_content_fr')->nullable();
            $table->longText('welcome_email_content_en')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Welcome email
            $table->dropColumn('welcome_email_subject_fr');
            $table->dropColumn('welcome_email_subject_en');

            $table->dropColumn('welcome_email_content_fr');
            $table->dropColumn('welcome_email_content_en');
        });
    }
};
