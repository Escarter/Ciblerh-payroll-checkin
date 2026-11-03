<?php

use App\Livewire\Portal\Companies\Index as Companies;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    foreach (['admin', 'manager', 'employee', 'supervisor'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    $admin = User::factory()->create();
    $admin->syncRoles(['admin']);
    actingAs($admin);

    Gate::define('company-read', fn () => true);
});

test('select all, toggle one row and clear work through the shared trait', function () {
    $companies = Company::factory()->count(3)->create();
    $total = Company::count();

    $component = Livewire::test(Companies::class)
        ->set('selectAll', true)
        ->assertCount('selectedCompanies', $total)
        ->set('selectAll', false)
        ->assertCount('selectedCompanies', 0)
        ->call('toggleSelection', $companies[0]->id)
        ->assertSet('selectedCompanies', [(string) $companies[0]->id])
        ->call('toggleSelection', $companies[0]->id)
        ->assertCount('selectedCompanies', 0);

    $component->call('selectAllMatching')->assertCount('selectedCompanies', $total)
        ->call('clearSelection')->assertCount('selectedCompanies', 0);
});
