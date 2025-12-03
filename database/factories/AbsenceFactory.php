<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Absence>
 */
class AbsenceFactory extends Factory
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
            'absence_date' => $this->faker->date(),
            'absence_reason' => $this->faker->sentence(),
            'approval_status' => 0, // PENDING
            'approval_reason' => null,
        ];
    }
}
