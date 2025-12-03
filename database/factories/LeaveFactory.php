<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Leave>
 */
class LeaveFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = now()->addDays(7);
        
        return [
            'user_id' => \App\Models\User::factory(),
            'company_id' => \App\Models\Company::factory(),
            'department_id' => \App\Models\Department::factory(),
            'leave_type_id' => \App\Models\LeaveType::factory(),
            'start_date' => $startDate,
            'end_date' => $startDate->copy()->addDays(5),
            'leave_reason' => $this->faker->sentence(),
            'supervisor_approval_status' => \App\Models\Leave::SUPERVISOR_APPROVAL_PENDING,
            'supervisor_approval_reason' => null,
            'manager_approval_status' => \App\Models\Leave::MANAGER_APPROVAL_PENDING,
            'manager_approval_reason' => null,
            'author_id' => \App\Models\User::factory(),
        ];
    }
}
