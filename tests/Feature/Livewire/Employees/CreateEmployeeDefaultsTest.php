<?php

use App\Events\EmployeeCreated;
use App\Livewire\Portal\Employees\All;
use App\Livewire\Portal\Employees\Index;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);

    $admin = User::factory()->create();
    $admin->syncRoles(['admin']);

    actingAs($admin);

    Gate::define('employee-create', fn () => true);
    Gate::define('employee-update', fn () => true);
    Gate::define('employee-read', fn () => true);

    Event::fake([EmployeeCreated::class]);
});

test('company employee component stores default work times when inputs are blank', function () {
    $company = Company::factory()->create();

    Livewire::test(Index::class, ['company_uuid' => $company->id])
        ->set('first_name', 'Index')
        ->set('last_name', 'Employee')
        ->set('email', 'index-employee@example.com')
        ->set('professional_phone_number', '+237600000101')
        ->set('matricule', 'IDX-001')
        ->set('status', 'true')
        ->set('work_start_time', null)
        ->set('work_end_time', null)
        ->call('store')
        ->assertHasNoErrors();

    $employee = User::where('email', 'index-employee@example.com')->firstOrFail();

    expect(substr((string) $employee->work_start_time, 0, 5))->toBe('08:00')
        ->and(substr((string) $employee->work_end_time, 0, 5))->toBe('17:30')
        ->and($employee->hasRole('employee'))->toBeTrue();
});

test('all employees component stores default work times when inputs are blank', function () {
    Livewire::test(All::class)
        ->set('first_name', 'All')
        ->set('last_name', 'Employee')
        ->set('email', 'all-employee@example.com')
        ->set('professional_phone_number', '+237600000102')
        ->set('matricule', 'ALL-001')
        ->set('password', 'Secret123!')
        ->set('status', 'true')
        ->set('work_start_time', null)
        ->set('work_end_time', null)
        ->call('store')
        ->assertHasNoErrors();

    $employee = User::where('email', 'all-employee@example.com')->firstOrFail();

    expect(substr((string) $employee->work_start_time, 0, 5))->toBe('08:00')
        ->and(substr((string) $employee->work_end_time, 0, 5))->toBe('17:30')
        ->and($employee->hasRole('employee'))->toBeTrue();
});
