<?php

namespace Database\Factories;

use App\Models\SendPayslipProcess;
use App\Models\User;
use App\Models\Company;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SendPayslipProcessFactory extends Factory
{
    protected $model = SendPayslipProcess::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company_id' => Company::factory(),
            'department_id' => Department::factory(),
            'month' => $this->faker->monthName(), // or use specific month like 'January'
            'year' => now()->year,
            'destination_directory' => 'payslips/' . Str::uuid(),
            'status' => 'processing',
            'percentage_completion' => 0,
        ];
    }

    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'successful',
            'percentage_completion' => 100,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'failure_reason' => 'Process failed',
        ]);
    }
}

