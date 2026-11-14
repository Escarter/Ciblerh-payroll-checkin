<?php

use App\Livewire\Portal\Employees\All;
use App\Livewire\Portal\Employees\Index;
use App\Livewire\Portal\Settings\Index as SettingsIndex;
use App\Models\Company;
use App\Models\Department;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\Service;
use App\Models\Setting;
use App\Models\SupervisorDepartment;
use App\Models\User;
use App\Services\FeatureConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
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

    Gate::define('employee-create', fn () => true);
    Gate::define('employee-update', fn () => true);
    Gate::define('employee-read', fn () => true);
    Gate::define('setting-read', fn () => true);
    Gate::define('setting-update', fn () => true);
});

test('employee company transfer setting defaults to false and can be toggled in settings', function () {
    expect(FeatureConfigurationService::isEmployeeCompanyTransferEnabled())->toBeFalse();

    Livewire::test(SettingsIndex::class)
        ->assertSet('allow_employee_company_transfer', false)
        ->set('allow_employee_company_transfer', true)
        ->call('saveFeatureConfiguration')
        ->assertHasNoErrors();

    expect(FeatureConfigurationService::isEmployeeCompanyTransferEnabled())->toBeTrue();
    expect((bool) Setting::first()->allow_employee_company_transfer)->toBeTrue();
});

test('transfer modal and company change in edit modal are blocked when setting is disabled', function () {
    Setting::create(['allow_employee_company_transfer' => false]);

    $companyA = Company::factory()->create(['name' => 'Alpha Corp']);
    $companyB = Company::factory()->create(['name' => 'Beta Corp']);

    $employee = User::factory()->create([
        'company_id' => $companyA->id,
        'professional_phone_number' => '+237670000111',
        'personal_phone_number' => '+237670000112',
    ]);
    $employee->syncRoles(['employee']);

    // Transfer modal action should not populate or transfer when setting is disabled
    Livewire::test(All::class)
        ->call('openTransferModal', $employee->id)
        ->assertSet('transferEmployeeIds', []);

    // Attempting to change editCompanyId inside update() when setting is disabled keeps original company
    Livewire::test(All::class)
        ->call('initData', $employee->id)
        ->set('editCompanyId', $companyB->id)
        ->call('update')
        ->assertHasNoErrors();

    expect($employee->fresh()->company_id)->toBe($companyA->id);
});

test('admin can transfer an employee via the edit employee modal when setting is enabled', function () {
    Setting::create(['allow_employee_company_transfer' => true]);

    $companyA = Company::factory()->create(['name' => 'Alpha Corp']);
    $deptA = Department::factory()->create(['company_id' => $companyA->id]);

    $companyB = Company::factory()->create(['name' => 'Beta Corp']);
    $deptB = Department::factory()->create(['company_id' => $companyB->id]);
    $serviceB = Service::factory()->create(['department_id' => $deptB->id]);

    $employee = User::factory()->create([
        'company_id' => $companyA->id,
        'department_id' => $deptA->id,
        'professional_phone_number' => '+237670000211',
        'personal_phone_number' => '+237670000212',
    ]);
    $employee->syncRoles(['employee', 'supervisor']);

    SupervisorDepartment::create([
        'supervisor_id' => $employee->id,
        'department_id' => $deptA->id,
    ]);

    Livewire::test(Index::class, ['company_uuid' => $companyA->id])
        ->call('initData', $employee->id)
        ->assertSet('editCompanyId', $companyA->id)
        ->set('editCompanyId', $companyB->id)
        ->assertSet('selectedDepartmentId', null)
        ->set('selectedDepartmentId', $deptB->id)
        ->set('service_id', $serviceB->id)
        ->call('update')
        ->assertHasNoErrors();

    $employee->refresh();

    expect($employee->company_id)->toBe($companyB->id)
        ->and($employee->department_id)->toBe($deptB->id)
        ->and($employee->service_id)->toBe($serviceB->id)
        ->and(SupervisorDepartment::where('supervisor_id', $employee->id)->where('department_id', $deptA->id)->exists())->toBeFalse();
});

test('admin can transfer single and multiple employees via transfer modal and historical records stay with old company', function () {
    Setting::create(['allow_employee_company_transfer' => true]);

    $companyA = Company::factory()->create(['name' => 'Origin Co']);
    $deptA = Department::factory()->create(['company_id' => $companyA->id]);

    $companyB = Company::factory()->create(['name' => 'Destination Co']);
    $deptB = Department::factory()->create(['company_id' => $companyB->id]);
    $serviceB = Service::factory()->create(['department_id' => $deptB->id]);

    $employee1 = User::factory()->create([
        'company_id' => $companyA->id,
        'department_id' => $deptA->id,
    ]);
    $employee1->syncRoles(['employee']);

    $employee2 = User::factory()->create([
        'company_id' => $companyA->id,
        'department_id' => $deptA->id,
    ]);
    $employee2->syncRoles(['employee']);

    $leaveType = LeaveType::factory()->create();
    $historicalLeave = Leave::create([
        'user_id' => $employee1->id,
        'company_id' => $companyA->id,
        'department_id' => $deptA->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => now()->subDays(10)->toDateString(),
        'end_date' => now()->subDays(5)->toDateString(),
        'leave_reason' => 'Annual vacation',
    ]);

    Livewire::test(All::class)
        ->set('selectedEmployees', [$employee1->id, $employee2->id])
        ->call('openBulkTransferModal')
        ->assertSet('transferEmployeeIds', [$employee1->id, $employee2->id])
        ->set('transferCompanyId', $companyB->id)
        ->set('transferDepartmentId', $deptB->id)
        ->set('transferServiceId', $serviceB->id)
        ->call('transferEmployeesToCompany')
        ->assertHasNoErrors();

    expect($employee1->fresh()->company_id)->toBe($companyB->id)
        ->and($employee1->fresh()->department_id)->toBe($deptB->id)
        ->and($employee1->fresh()->service_id)->toBe($serviceB->id)
        ->and($employee2->fresh()->company_id)->toBe($companyB->id)
        ->and($historicalLeave->fresh()->company_id)->toBe($companyA->id);
});

test('manager can only transfer employees between companies they manage', function () {
    Setting::create(['allow_employee_company_transfer' => true]);

    $managedCompanyA = Company::factory()->create(['name' => 'Managed A']);
    $managedCompanyB = Company::factory()->create(['name' => 'Managed B']);
    $unmanagedCompany = Company::factory()->create(['name' => 'Unmanaged C']);

    $manager = User::factory()->create(['company_id' => $managedCompanyA->id]);
    $manager->syncRoles(['manager']);

    $manager->managerCompanies()->attach([$managedCompanyA->id, $managedCompanyB->id]);

    $employee = User::factory()->create(['company_id' => $managedCompanyA->id]);
    $employee->syncRoles(['employee']);

    actingAs($manager);

    // Attempting to transfer to an unmanaged company fails validation
    Livewire::test(All::class)
        ->call('openTransferModal', $employee->id)
        ->set('transferCompanyId', $unmanagedCompany->id)
        ->call('transferEmployeesToCompany')
        ->assertHasErrors(['transferCompanyId']);

    expect($employee->fresh()->company_id)->toBe($managedCompanyA->id);

    // Transferring to a managed company succeeds
    Livewire::test(All::class)
        ->call('openTransferModal', $employee->id)
        ->set('transferCompanyId', $managedCompanyB->id)
        ->call('transferEmployeesToCompany')
        ->assertHasNoErrors();

    expect($employee->fresh()->company_id)->toBe($managedCompanyB->id);
});
