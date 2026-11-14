<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create platform_features table
        if (!Schema::hasTable('platform_features')) {
            Schema::create('platform_features', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('name');
                $table->string('category')->default('general');
                $table->text('description')->nullable();
                $table->boolean('is_enabled')->default(true);
                $table->string('icon')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        // 2. Ensure superadmin role exists
        $role = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);

        // 3. Assign superadmin role to admin@app.com
        $admin = User::where('email', 'admin@app.com')->first();
        if ($admin && !$admin->hasRole('superadmin')) {
            $admin->assignRole($role);
        }

        // 4. Seed default features
        $features = [
            [
                'key' => 'annual_declarations',
                'name' => 'Annual Declarations',
                'category' => 'compliance',
                'description' => 'Employee tax & annual declaration PDF splitting, review, and sending pipeline.',
                'is_enabled' => true,
                'icon' => 'document-report',
            ],
            [
                'key' => 'advance_salaries',
                'name' => 'Salary Advances',
                'category' => 'finance',
                'description' => 'Salary advance request workflows, approvals, and monthly deduction tracking.',
                'is_enabled' => true,
                'icon' => 'cash',
            ],
            [
                'key' => 'overtimes',
                'name' => 'Overtimes',
                'category' => 'attendance',
                'description' => 'Overtime hour logging, approval chains, and overtime summary exports.',
                'is_enabled' => true,
                'icon' => 'clock',
            ],
            [
                'key' => 'checklogs',
                'name' => 'Check-in & Ticking',
                'category' => 'attendance',
                'description' => 'Daily check-in / check-out logging, geolocation tracking, and presence reports.',
                'is_enabled' => true,
                'icon' => 'location-marker',
            ],
            [
                'key' => 'sftp_validator',
                'name' => 'SFTP Payslip Validator',
                'category' => 'payroll',
                'description' => 'Automated SFTP scan, proposal validation, and PDF employee-matching engine.',
                'is_enabled' => true,
                'icon' => 'shield-check',
            ],
            [
                'key' => 'orange_sms',
                'name' => 'Orange SMS Integration',
                'category' => 'notifications',
                'description' => 'Direct SMS dispatching to employees for payslip credentials and security alerts.',
                'is_enabled' => true,
                'icon' => 'chat-alt-2',
            ],
            [
                'key' => 'two_factor_auth',
                'name' => 'Two-Factor Authentication (MFA)',
                'category' => 'security',
                'description' => 'Mandatory or optional Multi-Factor Authentication via Authenticator apps, SMS, or Email.',
                'is_enabled' => true,
                'icon' => 'key',
            ],
            [
                'key' => 'employee_transfer',
                'name' => 'Employee Company Transfer',
                'category' => 'employees',
                'description' => 'Ability to transfer employees and managers between different subsidiaries and companies.',
                'is_enabled' => true,
                'icon' => 'switch-horizontal',
            ],
            [
                'key' => 'employee_data_purge',
                'name' => 'Employee Full Data Purge',
                'category' => 'employees',
                'description' => 'Permit authorized admins to permanently force-delete an employee along with historical data.',
                'is_enabled' => true,
                'icon' => 'trash',
            ],
            [
                'key' => 'audit_logs',
                'name' => 'Audit Logs',
                'category' => 'security',
                'description' => 'Detailed system action tracking, IP logging, and change records for compliance.',
                'is_enabled' => true,
                'icon' => 'clipboard-list',
            ],
        ];

        foreach ($features as $f) {
            \Illuminate\Support\Facades\DB::table('platform_features')->updateOrInsert(
                ['key' => $f['key']],
                array_merge($f, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_features');
        $role = Role::where('name', 'superadmin')->first();
        if ($role) {
            $role->delete();
        }
    }
};
