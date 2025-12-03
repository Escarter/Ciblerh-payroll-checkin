<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition(): array
    {
        return [
            'sms_provider' => 'nexah',
            'sms_provider_username' => 'test_username',
            'sms_provider_password' => 'test_password',
            'sms_provider_senderid' => 'TEST',
            'sms_content_en' => 'Hello :name:, your payslip for :month: :year: is ready. Password: :pdf_password:',
            'sms_content_fr' => 'Bonjour :name:, votre fiche de paie pour :month: :year: est prête. Mot de passe: :pdf_password:',
            'birthday_sms_message_en' => 'Happy Birthday :name:!',
            'birthday_sms_message_fr' => 'Joyeux Anniversaire :name:!',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 587,
            'smtp_username' => 'test@example.com',
            'smtp_password' => 'password',
            'smtp_encryption' => 'tls',
            'from_email' => 'noreply@example.com',
            'from_name' => 'Test Company',
            'smtp_provider' => 'smtp',
        ];
    }
}

