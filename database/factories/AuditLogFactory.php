<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = \App\Models\User::factory()->create();
        $company = \App\Models\Company::factory()->create();
        $department = \App\Models\Department::factory()->create(['company_id' => $company->id]);

        return [
            'user_id' => $user->id,
            'user' => $user->name,
            'action_type' => $this->faker->randomElement([
                'user_created', 'user_updated', 'user_deleted',
                'company_created', 'company_updated', 'company_deleted',
                'login_success', 'logout_success'
            ]),
            'channel' => $this->faker->randomElement(['web', 'api', 'mobile']),
            'action_perform' => $this->faker->sentence(),
            'company_id' => $company->id,
            'department_id' => $department->id,
            'author_id' => $user->id,
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'updated_at' => now(),
        ];
    }
}
