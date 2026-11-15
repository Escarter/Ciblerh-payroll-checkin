<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PlatformFeature extends Model
{
    use HasFactory;

    protected $table = 'platform_features';

    protected $guarded = [];

    protected $casts = [
        'is_enabled' => 'boolean',
        'metadata' => 'json',
    ];

    public const CACHE_KEY = 'platform.features.all';

    protected static function booted()
    {
        static::saved(function () {
            Cache::forget(self::CACHE_KEY);
        });

        static::deleted(function () {
            Cache::forget(self::CACHE_KEY);
        });
    }

    /**
     * Define the full catalog of platform features in the codebase.
     */
    public static function defaultFeatures(): array
    {
        return [
            // Attendance
            [
                'key' => 'checklogs',
                'name' => 'Check-in & Ticking',
                'category' => 'attendance',
                'description' => 'Daily check-in / check-out logging, geolocation tracking, and presence reports.',
                'is_enabled' => true,
                'icon' => 'location-marker',
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
                'key' => 'absences',
                'name' => 'Absences Tracking',
                'category' => 'attendance',
                'description' => 'Employee justified and unjustified absence recording and monitoring.',
                'is_enabled' => true,
                'icon' => 'calendar',
            ],
            [
                'key' => 'holiday_calendar',
                'name' => 'Holiday Calendar',
                'category' => 'attendance',
                'description' => 'Company-wide holiday schedules, non-working days, and calendar event planner.',
                'is_enabled' => true,
                'icon' => 'calendar',
            ],
            // Payroll & Finance
            [
                'key' => 'advance_salaries',
                'name' => 'Salary Advances',
                'category' => 'finance',
                'description' => 'Salary advance request workflows, manager approvals, and deduction tracking.',
                'is_enabled' => true,
                'icon' => 'cash',
            ],
            [
                'key' => 'payslip_sending',
                'name' => 'Payslip Distribution',
                'category' => 'payroll',
                'description' => 'Bulk and scheduled payslip delivery to staff via email and SMS.',
                'is_enabled' => true,
                'icon' => 'mail',
            ],
            [
                'key' => 'payslip_history',
                'name' => 'Payslip History & Archive',
                'category' => 'payroll',
                'description' => 'Historical payslip archive, records lookup, and PDF re-downloads.',
                'is_enabled' => true,
                'icon' => 'archive',
            ],
            [
                'key' => 'sftp_validator',
                'name' => 'SFTP Payslip Validator',
                'category' => 'payroll',
                'description' => 'Automated SFTP scan, proposal validation, and PDF employee-matching engine.',
                'is_enabled' => true,
                'icon' => 'shield-check',
            ],
            // Compliance & HR
            [
                'key' => 'annual_declarations',
                'name' => 'Annual Declarations',
                'category' => 'compliance',
                'description' => 'Employee tax & annual declaration PDF splitting, review, and sending pipeline.',
                'is_enabled' => true,
                'icon' => 'document-report',
            ],
            [
                'key' => 'leaves',
                'name' => 'Leave Management',
                'category' => 'compliance',
                'description' => 'Annual leaves, leave type policies, approval chains, and quota balances.',
                'is_enabled' => true,
                'icon' => 'briefcase',
            ],
            // Portals & Self-Service
            [
                'key' => 'employee_portal',
                'name' => 'Employee Self-Service Portal',
                'category' => 'portals',
                'description' => 'Dedicated self-service portal for employees to review payslips, check in, and submit requests.',
                'is_enabled' => true,
                'icon' => 'user-group',
            ],
            // Security & System
            [
                'key' => 'audit_logs',
                'name' => 'Audit Logs',
                'category' => 'security',
                'description' => 'Detailed system action tracking, IP logging, and change records for compliance.',
                'is_enabled' => true,
                'icon' => 'clipboard-list',
            ],
            [
                'key' => 'two_factor_auth',
                'name' => 'Two-Factor Authentication (MFA)',
                'category' => 'security',
                'description' => 'Multi-Factor Authentication via Authenticator apps, SMS, or Email.',
                'is_enabled' => true,
                'icon' => 'key',
            ],
            // Employees & Operations
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
            // Notifications & Integrations
            [
                'key' => 'orange_sms',
                'name' => 'Orange SMS Integration',
                'category' => 'notifications',
                'description' => 'Direct SMS dispatching to employees for payslip credentials and security alerts.',
                'is_enabled' => true,
                'icon' => 'chat-alt-2',
            ],
            // Operations & Tools
            [
                'key' => 'import_export',
                'name' => 'Data Import & Export Tools',
                'category' => 'operations',
                'description' => 'Batch CSV/Excel employee and attendance import wizards and data export tools.',
                'is_enabled' => true,
                'icon' => 'upload',
            ],
        ];
    }

    /**
     * Ensure all default features exist in the database.
     */
    public static function syncDefaultFeatures(): void
    {
        try {
            static $synced = false;
            if ($synced) {
                return;
            }
            $synced = true;

            $existing = static::pluck('key')->flip();
            foreach (static::defaultFeatures() as $f) {
                if (!isset($existing[$f['key']])) {
                    static::create($f);
                }
            }
        } catch (\Throwable $e) {
            // DB table might not be ready yet
        }
    }

    /**
     * Get all features cached as key => boolean.
     */
    public static function featureMap(): array
    {
        return Cache::remember(self::CACHE_KEY, 3600, function () {
            try {
                return static::pluck('is_enabled', 'key')->toArray();
            } catch (\Throwable $e) {
                return [];
            }
        });
    }

    /**
     * Check if a feature is enabled.
     * When allowSuperadminBypass is true, superadmins bypass disabled checks.
     * By default, disabled features are strictly disabled for everyone (including admins).
     */
    public static function isEnabled(string $key, bool $allowSuperadminBypass = false): bool
    {
        if ($allowSuperadminBypass && auth()->check() && auth()->user()->hasRole('superadmin')) {
            return true;
        }

        $features = self::featureMap();

        // If the feature key is not yet recorded, default to true so it doesn't break unexpected calls.
        return array_key_exists($key, $features) ? (bool) $features[$key] : true;
    }

    /**
     * Check if a feature is strictly enabled globally (regardless of who is viewing).
     */
    public static function isGloballyEnabled(string $key): bool
    {
        return self::isEnabled($key, false);
    }
}
