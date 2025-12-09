<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use App\Models\Service;
use App\Models\Department;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $environment = app()->environment();
        $isProduction = $environment === 'production';
        
        $this->command->info("Running database seeder for {$environment} environment");
        
        User::flushEventListeners();
        Department::flushEventListeners();
        Company::flushEventListeners();
        Service::flushEventListeners();

        // Always seed roles and permissions
        $this->call(RolesAndPermissionsSeeder::class);
        
        // Create admin user
        $adminUser = $this->createAdminUser();
        
        // Only create test data in non-production environments
        if (!$isProduction) {
            $this->command->info('Creating test data for development environment...');
            // $this->createTestData();
            $this->assignRoles();
        } else {
            $this->command->info('Skipping test data creation in production environment');
        }
    }
    
    private function createAdminUser()
    {
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@app.com'],
            [
                'uuid' => Str::uuid(),
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'matricule' => 'ADMIN001',
                'personal_phone_number' => '+237000000000',
                'professional_phone_number' => '+237000000000',
                'pdf_password' => Str::random(10),
                'email_verified_at' => now(),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'remember_token' => Str::random(10),
            ]
        );
        
        $adminUser->assignRole('admin');
        
        return $adminUser;
    }
    
    private function createTestData()
    {
        Company::factory()
            ->count(5)
            ->has(
                Department::factory()
                    ->count(5)
                    ->has(
                        Service::factory()->count(2)->state(function (array $attributes, Department $department) {
                            return ['company_id' => $department->company_id];
                        })->has(
                            User::factory()->count(1)->state(function (array $attributes, Service $service) {
                                return [
                                    'department_id' => $service->department_id,
                                    'company_id' => $service->company_id,
                                ];
                            })
                        )
                    )
            )->create();
    }
    
    private function assignRoles()
    {
        $employee_role = Role::where('name', 'employee')->first();

        User::all()->each(function ($user) use ($employee_role) {
            if(explode("@", $user->email)[1] !== "app.com"){
                return $user->assignRole($employee_role);
            }
        });  
    }
}
