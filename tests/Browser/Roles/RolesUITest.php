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

test('user can search for roles', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $role = Role::factory()->create(['name' => 'Test Role']);
        
        $browser->visit('/portal/roles')
            ->type('#search', 'Test')
            ->pause(1000)
            ->assertSee('Test Role');
    });
});

test('user can open create role modal', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/roles')
            ->click('button:contains("Create Role")')
            ->pause(500)
            ->waitFor('#CreateRoleModal', 5)
            ->assertSee('Create Role');
    });
});

test('user can view role details', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $role = Role::factory()->create();
        
        $browser->visit('/portal/roles')
            ->click("button[wire\\:click='initData({$role->id})']")
            ->pause(500)
            ->assertSee($role->name);
    });
});

test('user can edit role', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $role = Role::factory()->create();
        
        $browser->visit('/portal/roles')
            ->click("button[wire\\:click='editRole({$role->id})']")
            ->pause(500)
            ->waitFor('#EditRoleModal', 5)
            ->assertSee('Edit Role');
    });
});

test('user can delete a role without users', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $role = Role::factory()->create();
        
        $browser->visit('/portal/roles')
            ->click("button[wire\\:click='initData({$role->id})']")
            ->pause(500)
            ->click('button:contains("Delete")')
            ->pause(500)
            ->waitFor('#DeleteModal', 5)
            ->within('#DeleteModal', function ($modal) {
                $modal->press('Delete');
            })
            ->pause(1000)
            ->assertSee('moved to trash');
    });
});

test('user cannot delete role with assigned users', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $role = Role::factory()->create();
        $employee = User::factory()->create();
        $employee->assignRole($role->name);
        
        $browser->visit('/portal/roles')
            ->click("button[wire\\:click='initData({$role->id})']")
            ->pause(500)
            ->click('button:contains("Delete")')
            ->pause(500)
            ->waitFor('#DeleteModal', 5)
            ->within('#DeleteModal', function ($modal) {
                $modal->press('Delete');
            })
            ->pause(1000)
            ->assertSee('cannot be deleted');
    });
});

test('user can switch between active and deleted tabs', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $role = Role::factory()->create();
        $role->delete();
        
        $browser->visit('/portal/roles')
            ->click('button:contains("Deleted")')
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
            ->click('button:contains("Deleted")')
            ->pause(500)
            ->click("button[wire\\:click='restore({$role->id})']")
            ->pause(500)
            ->waitFor('#RestoreModal', 5)
            ->within('#RestoreModal', function ($modal) {
                $modal->press('Restore');
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
            ->click('button:contains("Deleted")')
            ->pause(500)
            ->click("button[wire\\:click='initData({$role->id})']")
            ->pause(500)
            ->click('button:contains("Permanently Delete")')
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
            ->check('input[type="checkbox"][wire\\:model="selectAll"]')
            ->pause(500)
            ->assertChecked('input[type="checkbox"][wire\\:model="selectAll"]');
    });
});

test('user can bulk delete roles', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $roles = Role::factory()->count(2)->create();
        
        $browser->visit('/portal/roles')
            ->check("input[type='checkbox'][value='{$roles[0]->id}']")
            ->check("input[type='checkbox'][value='{$roles[1]->id}']")
            ->pause(500)
            ->click('button:contains("Bulk Delete")')
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




