<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;
use App\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [

            ['name' => 'user-read'],
            ['name' => 'user-create'],
            ['name' => 'user-update'],
            ['name' => 'user-delete'],

            ['name' => 'user-export'],
            ['name' => 'user-import'],

            ['name' => 'company-read'],
            ['name' => 'company-create'],
            ['name' => 'company-update'],
            ['name' => 'company-delete'],

            ['name' => 'company-import'],
            ['name' => 'company-export'],

            ['name' => 'department-read'],
            ['name' => 'department-create'],
            ['name' => 'department-update'],
            ['name' => 'department-delete'],

            ['name' => 'department-import'],
            ['name' => 'department-export'],

            ['name' => 'service-read'],
            ['name' => 'service-create'],
            ['name' => 'service-update'],
            ['name' => 'service-delete'],

            ['name' => 'service-import'],
            ['name' => 'service-export'],

            ['name' => 'payslip-read'],
            ['name' => 'payslip-create'],
            ['name' => 'payslip-update'],
            ['name' => 'payslip-delete'],

            ['name' => 'payslip-sending'],
            ['name' => 'payslip-export'],

            ['name' => 'audit_log-read_all'],
            ['name' => 'audit_log-read_own_only'],
            ['name' => 'audit_log-delete'],

            ['name' => 'role-read'],
            ['name' => 'role-create'],
            ['name' => 'role-update'],
            ['name' => 'role-delete'],

            ['name' => 'role-import'],
            ['name' => 'role-export'],

            ['name' => 'advance_salary-read'],
            ['name' => 'advance_salary-create'],
            ['name' => 'advance_salary-update'],
            ['name' => 'advance_salary-delete'],
            ['name' => 'advance_salary-import'],
            ['name' => 'advance_salary-export'],
            
            ['name' => 'profile-read'],
            ['name' => 'profile-update'],
            ['name' => 'profile-delete'],

            ['name' => 'employee-read'],
            ['name' => 'employee-create'],
            ['name' => 'employee-update'],
            ['name' => 'employee-delete'],
            ['name' => 'employee-import'],
            ['name' => 'employee-export'],

            ['name' => 'absence-read'],
            ['name' => 'absence-create'],
            ['name' => 'absence-update'],
            ['name' => 'absence-delete'],
            ['name' => 'absence-export'],

            ['name' => 'overtime-read'],
            ['name' => 'overtime-create'],
            ['name' => 'overtime-update'],
            ['name' => 'overtime-delete'],
            ['name' => 'overtime-import'],
            ['name' => 'overtime-export'],

            ['name' => 'ticking-read'],
            ['name' => 'ticking-create'],
            ['name' => 'ticking-update'],
            ['name' => 'ticking-delete'],
            ['name' => 'ticking-export'],
            ['name' => 'ticking-import'],

            ['name' => 'leave-read'],
            ['name' => 'leave-create'],
            ['name' => 'leave-update'],
            ['name' => 'leave-delete'],
            ['name' => 'leave-export'],
            ['name' => 'leave-import'],

            ['name' => 'leave_type-read'],
            ['name' => 'leave_type-create'],
            ['name' => 'leave_type-update'],
            ['name' => 'leave_type-delete'],
            ['name' => 'leave_type-export'],
            ['name' => 'leave_type-import'],

            ['name' => 'importjob-read'],
            ['name' => 'importjob-create'],
            ['name' => 'importjob-update'],
            ['name' => 'importjob-delete'],
            ['name' => 'importjob-import'],
            ['name' => 'importjob-export'],

            ['name' => 'setting-read'],
            ['name' => 'setting-save'],
            ['name' => 'setting-sms'],
            ['name' => 'setting-smtp'],

            ['name' => 'report-checkin-read'],
            ['name' => 'report-payslip-read'],
            ['name' => 'report-export'],

        ];

        $insert_data = [];
        $time_stamp = Carbon::now()->toDateTimeString();
        foreach ($data as $d) {
            $this->command->info('Creating Permissions');
            Permission::firstOrCreate([
                'name' => $d['name']
            ],[
                'guard_name' => 'web',
                'created_at' => $time_stamp, 
            ]);
        }
        // $this->command->info('Creating Permissions');
        // Permission::firstOrCr($insert_data);

        $this->command->info('Creating Default Roles');

        $this->command->info('Creating Admin\'s Role');
        $admin = Role::firstOrCreate(['name' => 'admin']);

        $this->command->info('Creating Manager\'s Role');
        $manager = Role::firstOrCreate(['name' => 'manager']);

        $this->command->info('Creating Supervisor User\'s Role');
        $supervisor = Role::firstOrCreate(['name' => 'supervisor']);

        $this->command->info('Creating employee\'s Role');
        $user_role = Role::firstOrCreate(['name' => 'employee']);

        $this->command->info('Syncing Permissions for default Roles');
        $all_permissions = Permission::where('guard_name', 'web')->get();
        $admin->syncPermissions($all_permissions);

        $this->command->info('Syncing Permissions for default User Role');
        $manager->syncPermissions([
            'profile-read','profile-update', 'profile-delete',
            'employee-read', 'employee-create', 'employee-update', 'employee-delete',
            'absence-read', 'absence-create', 'absence-update', 'absence-delete',
            'advance_salary-read', 'advance_salary-create', 'advance_salary-update', 'advance_salary-delete',
            'company-read', 'company-create', 'company-update', 'company-delete',
            'department-read', 'department-create', 'department-update', 'department-delete',
            'service-read', 'service-create', 'service-update', 'service-delete',
            'overtime-read', 'overtime-create', 'overtime-update', 'overtime-delete',
            'ticking-read', 'ticking-create', 'ticking-update', 'ticking-delete',
            'payslip-read', 'payslip-create', 'payslip-update', 'payslip-delete',
            'leave_type-read', 'leave_type-create', 'leave_type-update', 'leave_type-delete',
            'leave-read', 'leave-create', 'leave-update', 'leave-delete',
            'role-read', 'role-create', 'role-update', 'role-delete',
            'importjob-read', 'importjob-create', 'importjob-update', 'importjob-delete', 'importjob-import', 'importjob-export',
            'setting-read', 'setting-save', 'setting-sms', 'setting-smtp',
        ]);

        $this->command->info('Syncing Permissions for default Supervisor Role');
        $supervisor->syncPermissions([
            'profile-read', 'profile-update', 'profile-delete',
            'employee-read', 'employee-create', 'employee-update', 'employee-delete',
            'department-read', 'department-create', 'department-update', 'department-delete',
            'service-read', 'service-create', 'service-update', 'service-delete',
            'absence-read', 'absence-create', 'absence-update', 'absence-delete',
            'advance_salary-read', 'advance_salary-create', 'advance_salary-update', 'advance_salary-delete',
            'overtime-read', 'overtime-create', 'overtime-update', 'overtime-delete',
            'ticking-read', 'ticking-create', 'ticking-update', 'ticking-delete',
            'payslip-read', 'payslip-create', 'payslip-update', 'payslip-delete',
            'leave-read', 'leave-create', 'leave-update', 'leave-delete',
        ]);

        $this->command->info('Syncing Permissions for default User Role');
        $user_role->syncPermissions([
            'profile-read', 'profile-update', 'profile-delete',
            'absence-read', 'absence-create', 'absence-update', 'absence-delete',
            'advance_salary-read', 'advance_salary-create', 'advance_salary-update', 'advance_salary-delete',
            'overtime-read', 'overtime-create', 'overtime-update',
            'ticking-read', 'ticking-create', 'ticking-update',
            'payslip-read', 'payslip-create', 'payslip-update',
            'leave-read', 'leave-create', 'leave-update', 'leave-delete',
        ]);
        
    }

    /**
     * Truncates all the laratrust tables and the users table
     *
     * @return  void
     */
    public function truncateLaratrustTables()
    {
        $this->command->info('Truncating User, Role and Permission tables');
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
