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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
                //smtp
            $table->enum('smtp_provider',['smtp','mailpit', 'ses','mailgun'])->default('smtp');
            $table->string('mailgun_domain')->nullable();
            $table->string('mailgun_secret')->nullable();
            $table->string('mailgun_endpoint')->nullable();
            $table->string('mailgun_scheme')->nullable();
            $table->string('smtp_host')->nullable();
            $table->string('smtp_port')->nullable();
            $table->string('smtp_username')->nullable();
            $table->string('smtp_password')->nullable();
            $table->string('smtp_encryption')->nullable();
            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();
            $table->string('replyTo_email')->nullable();
            $table->string('replyTo_name')->nullable();
            //sms 
            $table->enum('sms_provider',['twilio','nexah'])->default('nexah');
            $table->integer('sms_balance')->nullable();
            $table->string('sms_provider_username')->nullable();
            $table->string('sms_provider_password')->nullable();
            $table->string('sms_provider_senderid')->nullable();

            $table->string('email_subject_fr')->nullable();
            $table->string('email_subject_en')->nullable();

            $table->longText('sms_content_fr')->nullable();
            $table->longText('sms_content_en')->nullable();
            $table->longText('email_content_fr')->nullable();
            $table->longText('email_content_en')->nullable();

            $table->foreignId('company_id')->nullable();
            $table->foreignId('author_id')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
