<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use App\Models\Service;
use App\Models\Department;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
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
        User::flushEventListeners();
        Department::flushEventListeners();
        Company::flushEventListeners();
        Service::flushEventListeners();

        \App\Models\Company::factory()
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
 
        $this->call(RolesAndPermissionsSeeder::class);
        
        \App\Models\User::create([
            'uuid' => Str::uuid(),
            'first_name' => ucwords(str_replace('_', ' ', fake()->name())),
            'last_name' => ucwords(str_replace('_', ' ', fake()->name())),
            'email' => 'admin@app.com',
            'matricule' => Str::random(10),
            'personal_phone_number' => fake()->phoneNumber,
            'professional_phone_number' => fake()->phoneNumber,
            'pdf_password' => Str::random(10),
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
        ]);

        $user = User::where('email', 'admin@app.com')->first();

        $user->assignRole('admin');


        $employee_role = Role::where('name', 'employee')->first();

        User::all()->each(function ($user) use ($employee_role) {
            if(explode("@",$user->email)[1] !== "app.com"){
                return $user->assignRole($employee_role);
            }
        });  
    }
}
