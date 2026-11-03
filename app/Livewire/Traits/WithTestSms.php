<?php

namespace App\Livewire\Traits;

use App\Models\Setting;
use App\Models\User;
use App\Services\AwsSnsSMS;
use App\Services\Nexah;
use App\Services\OrangeCameroonSMS;
use App\Services\SmsProvider;
use App\Services\TwilioSMS;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

trait WithTestSms
{
    public $testSmsEmployeeId = null;
    public $testSmsEmployeeName = '';
    public $testSmsPhones = [];
    public $testSmsPhone = '';
    public $testSmsMessage = '';

    public function openTestSms($employeeId)
    {
        $this->authorizeTestSms();

        $employee = User::withTrashed()->findOrFail($employeeId);

        $this->testSmsEmployeeId = $employee->id;
        $this->testSmsEmployeeName = trim((string) $employee->name);
        $this->testSmsPhones = collect([
            'professional' => $employee->professional_phone_number,
            'personal' => $employee->personal_phone_number,
        ])->filter(fn ($number) => !empty(trim((string) $number)))->unique()->all();
        $this->testSmsPhone = (string) (array_values($this->testSmsPhones)[0] ?? '');
        $this->testSmsMessage = __('employees.test_sms_default_message', ['app' => \App\Support\Branding::get('app_name')]);
        $this->resetErrorBag();
    }

    public function sendTestSms()
    {
        $this->authorizeTestSms();

        $this->validate([
            'testSmsPhone' => ['required', 'string'],
            'testSmsMessage' => ['required', 'string', 'max:160'],
        ]);

        $employee = User::withTrashed()->findOrFail($this->testSmsEmployeeId);
        $allowed = [$employee->professional_phone_number, $employee->personal_phone_number];

        if (!in_array($this->testSmsPhone, $allowed, true)) {
            $this->addError('testSmsPhone', __('employees.test_sms_invalid_number'));
            return;
        }

        $setting = Setting::first();

        if (empty($setting?->sms_provider_username) || empty($setting?->sms_provider_password)) {
            $this->closeModalAndFlashMessage(__('payslips.sms_provider_credentials_not_configured'), 'TestSmsModal', 'danger');
            return;
        }

        try {
            $client = $this->makeSmsClient($setting);
            $response = $client->sendSMS(['sms' => $this->testSmsMessage, 'mobiles' => $this->testSmsPhone]);
        } catch (\Throwable $e) {
            Log::error('Test SMS failed', ['employee_id' => $employee->id, 'error' => $e->getMessage()]);
            $response = ['responsecode' => 0, 'error' => $e->getMessage()];
        }

        if (($response['responsecode'] ?? 0) === 1) {
            $this->closeModalAndFlashMessage(__('employees.test_sms_sent', ['phone' => $this->testSmsPhone]), 'TestSmsModal');
            return;
        }

        $this->closeModalAndFlashMessage(
            __('employees.test_sms_failed') . (!empty($response['error']) ? ': ' . $response['error'] : ''),
            'TestSmsModal',
            'danger'
        );
    }

    private function authorizeTestSms(): void
    {
        abort_unless(
            auth()->user()?->hasAnyRole(['admin', 'manager']) || Gate::allows('employee-update'),
            403
        );
    }

    private function makeSmsClient(Setting $setting): SmsProvider
    {
        return match ($setting->sms_provider) {
            'twilio' => new TwilioSMS($setting),
            'aws_sns' => new AwsSnsSMS($setting),
            'orange_cm' => new OrangeCameroonSMS($setting),
            default => new Nexah($setting),
        };
    }
}
