<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use App\Models\Service;
use App\Models\Department;
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

        $employee_role = Role::where('name', 'employee')->first();

        User::all()->each(function ($user) use ($employee_role) {
            if(explode("@",$user->email)[1] !== "app.com"){
                return $user->assignRole($employee_role);
            }
        });  
    }
}
