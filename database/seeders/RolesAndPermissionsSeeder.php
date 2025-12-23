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
            ['name' => 'payslip-bulkresend-email'],
            ['name' => 'payslip-bulkresend-sms'],
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
            ['name' => 'importjob-cancel'],

            ['name' => 'setting-read'],
            ['name' => 'setting-save'],
            ['name' => 'setting-sms'],
            ['name' => 'setting-smtp'],

            ['name' => 'report-checkin-read'],
            ['name' => 'report-payslip-read'],
            ['name' => 'report-export'],

            // Download Jobs permissions
            ['name' => 'downloadjob-read'],
            ['name' => 'downloadjob-create'],
            ['name' => 'downloadjob-update'],
            ['name' => 'downloadjob-delete'],
            ['name' => 'downloadjob-cancel'],
            ['name' => 'downloadjob-restore'],
            ['name' => 'downloadjob-bulkdelete'],
            ['name' => 'downloadjob-bulkrestore'],

            // Restore, Bulk Delete, and Bulk Restore permissions for modules with soft delete
            // Role permissions
            ['name' => 'role-restore'],
            ['name' => 'role-bulkdelete'],
            ['name' => 'role-bulkrestore'],

            // Company permissions
            ['name' => 'company-restore'],
            ['name' => 'company-bulkdelete'],
            ['name' => 'company-bulkrestore'],

            // Department permissions
            ['name' => 'department-restore'],
            ['name' => 'department-bulkdelete'],
            ['name' => 'department-bulkrestore'],

            // Service permissions
            ['name' => 'service-restore'],
            ['name' => 'service-bulkdelete'],
            ['name' => 'service-bulkrestore'],

            // Employee permissions
            ['name' => 'employee-restore'],
            ['name' => 'employee-bulkdelete'],
            ['name' => 'employee-bulkrestore'],

            // Absence permissions
            ['name' => 'absence-restore'],
            ['name' => 'absence-bulkdelete'],
            ['name' => 'absence-bulkrestore'],

            // Advance Salary permissions
            ['name' => 'advance_salary-restore'],
            ['name' => 'advance_salary-bulkdelete'],
            ['name' => 'advance_salary-bulkrestore'],

            // Leave permissions
            ['name' => 'leave-restore'],
            ['name' => 'leave-bulkdelete'],
            ['name' => 'leave-bulkrestore'],

            // Leave Type permissions
            ['name' => 'leave_type-restore'],
            ['name' => 'leave_type-bulkdelete'],
            ['name' => 'leave_type-bulkrestore'],

            // Overtime permissions
            ['name' => 'overtime-restore'],
            ['name' => 'overtime-bulkdelete'],
            ['name' => 'overtime-bulkrestore'],

            // Ticking (Checklog) permissions
            ['name' => 'ticking-restore'],
            ['name' => 'ticking-bulkdelete'],
            ['name' => 'ticking-bulkrestore'],

            // Payslip permissions
            ['name' => 'payslip-restore'],
            ['name' => 'payslip-bulkdelete'],
            ['name' => 'payslip-bulkrestore'],

            // Import Job permissions
            ['name' => 'importjob-restore'],
            ['name' => 'importjob-bulkdelete'],
            ['name' => 'importjob-bulkrestore'],

            // Audit Log permissions
            ['name' => 'audit_log-restore'],
            ['name' => 'audit_log-bulkdelete'],
            ['name' => 'audit_log-bulkrestore'],

            // Bulk Approval and Rejection permissions
            // Leave permissions
            ['name' => 'leave-bulkapproval'],
            ['name' => 'leave-bulkrejection'],

            // Absence permissions
            ['name' => 'absence-bulkapproval'],
            ['name' => 'absence-bulkrejection'],

            // Advance Salary permissions
            ['name' => 'advance_salary-bulkapproval'],
            ['name' => 'advance_salary-bulkrejection'],

            // Overtime permissions
            ['name' => 'overtime-bulkapproval'],
            ['name' => 'overtime-bulkrejection'],

            // Ticking (Checklog) permissions
            ['name' => 'ticking-bulkapproval'],
            ['name' => 'ticking-bulkrejection'],

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
            'employee-read', 'employee-create', 'employee-update', 'employee-delete', 'employee-restore', 'employee-bulkdelete', 'employee-bulkrestore',
            'absence-read', 'absence-create', 'absence-update', 'absence-delete', 'absence-restore', 'absence-bulkdelete', 'absence-bulkrestore',
            'advance_salary-read', 'advance_salary-create', 'advance_salary-update', 'advance_salary-delete', 'advance_salary-restore', 'advance_salary-bulkdelete', 'advance_salary-bulkrestore',
            'company-read', 'company-create', 'company-update', 'company-delete', 'company-restore', 'company-bulkdelete', 'company-bulkrestore',
            'department-read', 'department-create', 'department-update', 'department-delete', 'department-restore', 'department-bulkdelete', 'department-bulkrestore',
            'service-read', 'service-create', 'service-update', 'service-delete', 'service-restore', 'service-bulkdelete', 'service-bulkrestore',
            'overtime-read', 'overtime-create', 'overtime-update', 'overtime-delete', 'overtime-restore', 'overtime-bulkdelete', 'overtime-bulkrestore',
            'ticking-read', 'ticking-create', 'ticking-update', 'ticking-delete', 'ticking-restore', 'ticking-bulkdelete', 'ticking-bulkrestore',
            'payslip-read', 'payslip-create', 'payslip-update', 'payslip-delete', 'payslip-restore', 'payslip-bulkdelete', 'payslip-bulkrestore', 'payslip-sending', 'payslip-bulkresend-email', 'payslip-bulkresend-sms',
            'leave_type-read', 'leave_type-create', 'leave_type-update', 'leave_type-delete', 'leave_type-restore', 'leave_type-bulkdelete', 'leave_type-bulkrestore',
            'leave-read', 'leave-create', 'leave-update', 'leave-delete', 'leave-restore', 'leave-bulkdelete', 'leave-bulkrestore',
            'role-read', 'role-create', 'role-update', 'role-delete', 'role-restore', 'role-bulkdelete', 'role-bulkrestore',
            'importjob-read', 'importjob-create', 'importjob-update', 'importjob-delete', 'importjob-restore', 'importjob-bulkdelete', 'importjob-bulkrestore', 'importjob-import', 'importjob-export', 'importjob-cancel',
            'setting-read', 'setting-save', 'setting-sms', 'setting-smtp',
        ]);

        $this->command->info('Syncing Permissions for default Supervisor Role');
        $supervisor->syncPermissions([
            'profile-read', 'profile-update', 'profile-delete',
            'employee-read', 'employee-create', 'employee-update', 'employee-delete', 'employee-restore', 'employee-bulkdelete', 'employee-bulkrestore',
            'department-read', 'department-create', 'department-update', 'department-delete', 'department-restore', 'department-bulkdelete', 'department-bulkrestore',
            'service-read', 'service-create', 'service-update', 'service-delete', 'service-restore', 'service-bulkdelete', 'service-bulkrestore',
            'absence-read', 'absence-create', 'absence-update', 'absence-delete', 'absence-restore', 'absence-bulkdelete', 'absence-bulkrestore',
            'advance_salary-read', 'advance_salary-create', 'advance_salary-update', 'advance_salary-delete', 'advance_salary-restore', 'advance_salary-bulkdelete', 'advance_salary-bulkrestore',
            'overtime-read', 'overtime-create', 'overtime-update', 'overtime-delete', 'overtime-restore', 'overtime-bulkdelete', 'overtime-bulkrestore',
            'ticking-read', 'ticking-create', 'ticking-update', 'ticking-delete', 'ticking-restore', 'ticking-bulkdelete', 'ticking-bulkrestore',
            'payslip-read', 'payslip-create', 'payslip-update', 'payslip-delete', 'payslip-restore', 'payslip-bulkdelete', 'payslip-bulkrestore', 'payslip-sending', 'payslip-bulkresend-email', 'payslip-bulkresend-sms',
            'leave-read', 'leave-create', 'leave-update', 'leave-delete', 'leave-restore', 'leave-bulkdelete', 'leave-bulkrestore',
        ]);

        $this->command->info('Syncing Permissions for default User Role');
        $user_role->syncPermissions([
            'profile-read', 'profile-update', 'profile-delete',
            'absence-read', 'absence-create', 'absence-update', 'absence-delete', 'absence-restore', 'absence-bulkdelete', 'absence-bulkrestore',
            'advance_salary-read', 'advance_salary-create', 'advance_salary-update', 'advance_salary-delete', 'advance_salary-restore', 'advance_salary-bulkdelete', 'advance_salary-bulkrestore',
            'overtime-read', 'overtime-create', 'overtime-update', 'overtime-restore', 'overtime-bulkdelete', 'overtime-bulkrestore',
            'ticking-read', 'ticking-create', 'ticking-update', 'ticking-restore', 'ticking-bulkdelete', 'ticking-bulkrestore',
            'payslip-read', 'payslip-create', 'payslip-update', 'payslip-restore', 'payslip-bulkdelete', 'payslip-bulkrestore',
            'leave-read', 'leave-create', 'leave-update', 'leave-delete', 'leave-restore', 'leave-bulkdelete', 'leave-bulkrestore',
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
