<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AdvanceSalary>
 */
class AdvanceSalaryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'company_id' => \App\Models\Company::factory(),
            'department_id' => \App\Models\Department::factory(),
            'amount' => $this->faker->numberBetween(10000, 100000),
            'reason' => $this->faker->sentence(),
            'repayment_from_month' => now()->addMonth(),
            'repayment_to_month' => now()->addMonths(3),
            'beneficiary_name' => $this->faker->name(),
            'beneficiary_mobile_money_number' => $this->faker->phoneNumber(),
            'beneficiary_id_card_number' => $this->faker->numerify('##########'),
            'net_salary' => $this->faker->numberBetween(50000, 500000),
            'approval_status' => \App\Models\AdvanceSalary::APPROVAL_STATUS_PENDING,
            'approval_reason' => null,
        ];
    }
}
