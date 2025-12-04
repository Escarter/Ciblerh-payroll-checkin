<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Faker Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used by the Faker PHP library when generating fake
    | data for your database seeds. For example, this will be used to get
    | localized telephone numbers, street address information and more.
    |
    */

    'faker_locale' => 'en_US',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => 'file',
        // 'store'  => 'redis',
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Search Configuration
    |--------------------------------------------------------------------------
    |
    | Defines the list of navigable destinations that appear in the global search
    | palette. Each item references an existing route and the permission(s)
    | required to access it. Items are filtered per-user before reaching the UI.
    |
    */

    'global_search' => [
        'bypass_permissions' => env('GLOBAL_SEARCH_BYPASS_PERMISSIONS', false),
        'items' => [
            [
                'id' => 'dashboard',
                'label' => 'Dashboard',
                'group' => 'Navigation',
                'description' => 'Dashboard',
                'route' => 'portal.dashboard',
                'icon' => 'fa fa-tachometer-alt',
                'keywords' => ['Dashboard', 'dashboard', 'home', 'overview'],
            ],
            [
                'id' => 'companies',
                'label' => 'Companies',
                'group' => 'Navigation',
                'description' => 'Companies Management',
                'route' => 'portal.companies.index',
                'icon' => 'fa fa-building',
                'keywords' => ['Companies', 'companies', 'organization'],
                'permissions' => ['company-read'],
            ],
            [
                'id' => 'all-employees',
                'label' => 'Employees',
                'group' => 'Navigation', 
                'description' => 'Employees',
                'route' => 'portal.all-employees',
                'icon' => 'fa fa-users',
                'keywords' => ['Employees', 'employees', 'staff', 'personnel'],
            ],
            [
                'id' => 'departments-supervisor',
                'label' => 'My Departments',
                'group' => 'Navigation',
                'description' => 'My Departments',
                'route' => 'portal.departments.supervisor',
                'icon' => 'fa fa-building',
                'keywords' => ['departments', 'supervisor'],
                'roles' => ['supervisor'],
            ],
            [
                'id' => 'checkins',
                'label' => 'Checkins',
                'group' => 'Time Tracking',
                'description' => 'Checkins',
                'route' => 'portal.checklogs.index',
                'icon' => 'fa fa-clock',
                'keywords' => ['Checkins', 'checkins', 'attendance', 'time'],
                'permissions' => ['ticking-read'],
            ],
            [
                'id' => 'overtimes',
                'label' => 'Overtimes',
                'group' => 'Time Tracking',
                'description' => 'Overtimes',
                'route' => 'portal.overtimes.index',
                'icon' => 'fa fa-clock',
                'keywords' => ['Overtimes', 'overtimes', 'overtime', 'extra hours'],
                'permissions' => ['overtime-read'],
            ],
            [
                'id' => 'advance-salaries',
                'label' => 'Advance Salaries',
                'group' => 'Payroll',
                'description' => 'Advance Salaries',
                'route' => 'portal.advance-salaries.index',
                'icon' => 'fa fa-money-bill',
                'keywords' => ['Advance Salaries', 'advance', 'salary', 'payment'],
                'permissions' => ['advance_salary-read'],
            ],
            [
                'id' => 'absences',
                'label' => 'Absences',
                'group' => 'Leave Management',
                'description' => 'Absences',
                'route' => 'portal.absences.index',
                'icon' => 'fa fa-calendar-times',
                'keywords' => ['Absences', 'absences', 'time off'],
                'permissions' => ['absence-read'],
            ],
            [
                'id' => 'payslips-send',
                'label' => 'Send Payslips',
                'group' => 'Payroll',
                'description' => 'Send Payslips',
                'route' => 'portal.payslips.index',
                'icon' => 'fa fa-file-invoice',
                'keywords' => ['Send Payslips', 'payslips', 'salary', 'send'],
                'permissions' => ['payslip-sending'],
            ],
            [
                'id' => 'payslips-history',
                'label' => 'Payslip History',
                'group' => 'Payroll',
                'description' => 'Payslip History',
                'route' => 'portal.payslips.history',
                'icon' => 'fa fa-history',
                'keywords' => ['Payslip History', 'payslips', 'history', 'records'],
                'permissions' => ['payslip-read'],
            ],
            [
                'id' => 'leaves',
                'label' => 'Leaves',
                'group' => 'Leave Management',
                'description' => 'Leaves',
                'route' => 'portal.leaves.index',
                'icon' => 'fa fa-calendar-alt',
                'keywords' => ['Leaves', 'leaves', 'vacation', 'time off'],
                'permissions' => ['leave-read'],
            ],
            [
                'id' => 'leave-types',
                'label' => 'Leave Types',
                'group' => 'Leave Management',
                'description' => 'Leave Types',
                'route' => 'portal.leaves.types',
                'icon' => 'fa fa-list',
                'keywords' => ['Leave Types', 'leave types', 'categories'],
                'permissions' => ['leave_type-read'],
            ],
            [
                'id' => 'reports',
                'label' => 'Reports',
                'group' => 'Analytics',
                'description' => 'Reports',
                'route' => 'portal.reports.checklogs',
                'icon' => 'fa fa-chart-bar',
                'keywords' => ['Reports', 'reports', 'analytics', 'statistics'],
                'permissions' => ['report-read'],
            ],
            [
                'id' => 'audit-logs',
                'label' => 'Audit Logs',
                'group' => 'Administration',
                'description' => 'Audit Logs',
                'route' => 'portal.auditlogs.index',
                'icon' => 'fa fa-history',
                'keywords' => ['Audit Logs', 'audit', 'logs', 'history'],
                'permissions' => ['audit_log-read'],
            ],
            [
                'id' => 'roles',
                'label' => 'Roles',
                'group' => 'Administration',
                'description' => 'Roles',
                'route' => 'portal.roles.index',
                'icon' => 'fa fa-user-shield',
                'keywords' => ['Roles', 'roles', 'permissions', 'access'],
                'permissions' => ['role-read'],
            ],
            [
                'id' => 'settings',
                'label' => 'Settings',
                'group' => 'Administration',
                'description' => 'Settings',
                'route' => 'portal.settings.index',
                'icon' => 'fa fa-cog',
                'keywords' => ['Settings', 'settings', 'configuration'],
                'permissions' => ['setting-read'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => ServiceProvider::defaultProviders()->merge([
        /*
         * Package Service Providers...
         */

        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        // App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        // App\Providers\HorizonServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
    ])->toArray(),

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => Facade::defaultAliases()->merge([
        // 'Example' => App\Facades\Example::class,
        // 'PDF' => Barryvdh\DomPDF\Facade\Pdf::class,
    ])->toArray(),

];
