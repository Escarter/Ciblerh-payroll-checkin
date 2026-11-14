<?php

use App\Livewire\Portal\Employees\All;
use App\Livewire\Portal\Employees\Index;
use App\Livewire\Portal\Settings\Index as SettingsIndex;
use App\Models\Company;
use App\Models\Leave;
use App\Models\Payslip;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\FeatureConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);

    $admin = User::factory()->create();
    $admin->syncRoles(['admin']);

    actingAs($admin);

    Gate::define('employee-delete', fn () => true);
    Gate::define('employee-read', fn () => true);
    Gate::define('manage-settings', fn () => true);

    FeatureConfigurationService::refreshConfiguration();
});

test('force deleting an employee with payslips is blocked when setting is disabled', function () {
    $company = Company::factory()->create();
    $employee = User::factory()->create(['company_id' => $company->id]);
    $employee->syncRoles(['employee']);

    $payslip = Payslip::create([
        'employee_id' => $employee->id,
        'company_id' => $company->id,
        'month' => 'January',
        'year' => '2026',
        'file' => 'payslips/test_emp.pdf',
    ]);

    Livewire::test(Index::class, ['company_uuid' => $company->id])
        ->call('forceDelete', $employee->id);

    expect(User::withTrashed()->find($employee->id))->not->toBeNull()
        ->and(Payslip::withTrashed()->find($payslip->id))->not->toBeNull();
});

test('force deleting an employee with payslips and associated data succeeds when setting is enabled', function () {
    Storage::fake('modified');
    Storage::disk('modified')->put('payslips/test_emp.pdf', 'dummy-pdf-content');

    Setting::updateOrCreate(
        ['company_id' => 1],
        ['allow_force_delete_employees_with_data' => true]
    );
    FeatureConfigurationService::refreshConfiguration();

    $company = Company::factory()->create();
    $employee = User::factory()->create(['company_id' => $company->id]);
    $employee->syncRoles(['employee']);

    $payslip = Payslip::create([
        'employee_id' => $employee->id,
        'company_id' => $company->id,
        'month' => 'January',
        'year' => '2026',
        'file' => 'payslips/test_emp.pdf',
    ]);

    $leave = Leave::factory()->create([
        'user_id' => $employee->id,
        'company_id' => $company->id,
    ]);

    Livewire::test(Index::class, ['company_uuid' => $company->id])
        ->call('forceDelete', $employee->id);

    expect(User::withTrashed()->find($employee->id))->toBeNull()
        ->and(Payslip::withTrashed()->find($payslip->id))->toBeNull()
        ->and(Leave::withTrashed()->find($leave->id))->toBeNull()
        ->and(Storage::disk('modified')->exists('payslips/test_emp.pdf'))->toBeFalse();
});

test('bulk force deleting employees with payslips succeeds in All component when setting is enabled', function () {
    Storage::fake('modified');
    Storage::disk('modified')->put('payslips/emp_bulk.pdf', 'dummy-pdf-content');

    Setting::updateOrCreate(
        ['company_id' => 1],
        ['allow_force_delete_employees_with_data' => true]
    );
    FeatureConfigurationService::refreshConfiguration();

    $company = Company::factory()->create();
    $employee = User::factory()->create(['company_id' => $company->id]);
    $employee->syncRoles(['employee']);
    $employee->delete(); // Move to trash

    $payslip = Payslip::create([
        'employee_id' => $employee->id,
        'company_id' => $company->id,
        'month' => 'February',
        'year' => '2026',
        'file' => 'payslips/emp_bulk.pdf',
    ]);

    Livewire::test(All::class)
        ->set('activeTab', 'deleted')
        ->set('selectedEmployeesForDelete', [$employee->id])
        ->call('bulkForceDelete');

    expect(User::withTrashed()->find($employee->id))->toBeNull()
        ->and(Payslip::withTrashed()->find($payslip->id))->toBeNull()
        ->and(Storage::disk('modified')->exists('payslips/emp_bulk.pdf'))->toBeFalse();
});

test('settings component saves allow_force_delete_employees_with_data toggle and switches tabs', function () {
    Livewire::test(SettingsIndex::class)
        ->assertSet('activeTab', 'sms')
        ->call('switchTab', 'features')
        ->assertSet('activeTab', 'features')
        ->set('allow_force_delete_employees_with_data', true)
        ->call('saveFeatureConfiguration')
        ->assertHasNoErrors();

    expect(FeatureConfigurationService::isForceDeleteEmployeesWithDataEnabled())->toBeTrue();
});
