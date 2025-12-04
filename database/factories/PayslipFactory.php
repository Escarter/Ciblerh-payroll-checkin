<?php

namespace Database\Factories;

use App\Models\Payslip;
use App\Models\User;
use App\Models\Company;
use App\Models\Department;
use App\Models\Service;
use App\Models\SendPayslipProcess;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayslipFactory extends Factory
{
    protected $model = Payslip::class;

    public function definition(): array
    {
        return [
            'employee_id' => User::factory(),
            'company_id' => Company::factory(),
            'department_id' => Department::factory(),
            'service_id' => Service::factory(),
            'send_payslip_process_id' => SendPayslipProcess::factory(),
            'user_id' => User::factory(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'matricule' => strtoupper($this->faker->bothify('EMP####')),
            'month' => $this->faker->month(),
            'year' => now()->year,
            'file' => 'payslips/' . $this->faker->uuid() . '.pdf',
            'encryption_status' => Payslip::STATUS_SUCCESSFUL,
            'email_sent_status' => Payslip::STATUS_PENDING,
            'sms_sent_status' => Payslip::STATUS_PENDING,
            'email_retry_count' => 0,
            'email_bounced' => false,
        ];
    }

    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'encryption_status' => Payslip::STATUS_SUCCESSFUL,
            'email_sent_status' => Payslip::STATUS_SUCCESSFUL,
            'sms_sent_status' => Payslip::STATUS_SUCCESSFUL,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_sent_status' => Payslip::STATUS_FAILED,
            'sms_sent_status' => Payslip::STATUS_FAILED,
        ]);
    }

    public function encryptionFailed(): static
    {
        return $this->state(fn (array $attributes) => [
            'encryption_status' => Payslip::STATUS_FAILED,
            'email_sent_status' => Payslip::STATUS_FAILED,
            'sms_sent_status' => Payslip::STATUS_FAILED,
            'failure_reason' => 'Encryption failed',
        ]);
    }

    public function emailBounced(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_bounced' => true,
            'email_bounced_at' => now(),
            'email_bounce_reason' => 'Mailbox does not exist',
            'email_bounce_type' => 'hard',
        ]);
    }
}








