<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Overtime>
 */
class OvertimeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $startTime = now()->subHours(2);
        $endTime = now();
        
        return [
            'user_id' => \App\Models\User::factory(),
            'company_id' => \App\Models\Company::factory(),
            'department_id' => \App\Models\Department::factory(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'minutes_worked' => $startTime->diffInMinutes($endTime),
            'reason' => $this->faker->sentence(),
            'approval_status' => \App\Models\Overtime::APPROVAL_STATUS_PENDING,
            'approval_reason' => null,
        ];
    }
}
