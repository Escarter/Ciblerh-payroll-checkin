<?php

use App\Models\User;
use App\Models\Role;
use Laravel\Dusk\Browser;

beforeEach(function () {
    // Setup
});

test('user can view roles page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/roles')
            ->assertSee('Roles')
            ->assertPathIs('/portal/roles');
    });
});

test('user can open create role modal', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');

        $this->visitAndWait($browser, '/portal/roles');
        $browser->click('#create-role-btn')
            ->pause(1000)
            ->waitFor('#CreateRoleModal', 5)
            ->assertSee('Create Role');
    });
});


test('user can edit role', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $role = Role::factory()->create();

        $this->visitAndWait($browser, '/portal/roles');
        $browser->click("#edit-role-{$role->id}")
            ->pause(1000)
            ->waitFor('#EditRoleModal', 5)
            ->assertSee('Edit Role');
    });
});

test('user can delete a role without users', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $role = Role::factory()->create();

        $browser->visit('/portal/roles')
            ->click("#delete-role-{$role->id}")
            ->pause(500)
            ->waitFor('#DeleteModal', 5)
            ->within('#DeleteModal', function ($modal) {
                $modal->press('#confirm-delete-btn');
            })
            ->pause(2000)
            ->screenshot('role-delete-debug')
            ->assertSee('Role moved to trash successfully!');
    });
});

test('user cannot delete role with assigned users', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $role = Role::factory()->create();
        $employee = User::factory()->create();
        $employee->assignRole($role->name);

        $browser->visit('/portal/roles')
            ->click("#delete-role-{$role->id}")
            ->pause(500)
            ->waitFor('#DeleteModal', 5)
            ->within('#DeleteModal', function ($modal) {
                $modal->press('#confirm-delete-btn');
            })
            ->pause(1000)
            ->assertSee('The role cannot be deleted because it is still assigned to users!');
    });
});

test('user can switch between active and deleted tabs', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $role = Role::factory()->create();
        $role->delete();
        
        $browser->visit('/portal/roles')
            ->click('#deleted-roles-tab')
            ->pause(500)
            ->assertSee('Deleted');
    });
});

test('user can restore a deleted role', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $role = Role::factory()->create();
        $role->delete();
        
        $browser->visit('/portal/roles')
            ->click('#deleted-roles-tab')
            ->pause(500)
            ->click("#restore-role-{$role->id}")
            ->pause(500)
            ->waitFor('#RestoreModal', 5)
            ->within('#RestoreModal', function ($modal) {
                $modal->press('#confirm-restore-btn');
            })
            ->pause(1000)
            ->assertSee('restored');
    });
});

test('user can permanently delete a role', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $role = Role::factory()->create();
        $role->delete();
        
        $browser->visit('/portal/roles')
            ->click('#deleted-roles-tab')
            ->pause(500)
            ->click('.card')  // Click the role card to select it
            ->pause(500)
            ->waitFor('#ForceDeleteModal', 5)
            ->within('#ForceDeleteModal', function ($modal) {
                $modal->press('Delete');
            })
            ->pause(1000)
            ->assertSee('permanently deleted');
    });
});

test('user can select all roles', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        Role::factory()->count(3)->create();
        
        $browser->visit('/portal/roles')
            ->click('#select-all-roles-checkbox')
            ->pause(500)
            ->assertPresent('#select-all-roles-checkbox');
    });
});

test('user can bulk delete roles', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $roles = Role::factory()->count(2)->create();
        
        $browser->visit('/portal/roles')
            ->click('#select-all-roles-checkbox')
            ->pause(500)
            ->click('#bulk-delete-roles-btn')
            ->pause(500)
            ->waitFor('#BulkDeleteModal', 5)
            ->within('#BulkDeleteModal', function ($modal) {
                $modal->press('Delete');
            })
            ->pause(1000)
            ->assertSee('moved to trash');
    });
});

test('user can change order by field', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/roles')
            ->select('#orderBy', 'created_at')
            ->pause(500)
            ->assertSelected('#orderBy', 'created_at');
    });
});

test('user can change items per page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        Role::factory()->count(10)->create();
        
        $browser->visit('/portal/roles')
            ->select('#perPage', '5')
            ->pause(500)
            ->assertSelected('#perPage', '5');
    });
});









