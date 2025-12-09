<?php

use App\Models\User;
use App\Models\Ticking;
use App\Models\Company;
use App\Models\Department;
use Laravel\Dusk\Browser;

beforeEach(function () {
    // Setup
});

test('user can view checklogs page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $this->visitAndWait($browser, '/portal/checklogs');
        $browser->assertSee('Checkins')
            ->assertPathIs('/portal/checklogs');
    });
});

test('user can search for checklogs', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $checklog = Ticking::factory()->create(['user_id' => $employee->id]);
        
        $this->visitAndWait($browser, '/portal/checklogs');
        $browser->type('#checklogs-search', $employee->first_name)
            ->pause(2000)
            ->assertSee($employee->first_name);
    });
});

test('user can view checklog details', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $checklog = Ticking::factory()->create(['user_id' => $employee->id]);
        
        $this->visitAndWait($browser, '/portal/checklogs');
        $browser->click("#view-checklog-btn-{$checklog->id}")
            ->pause(1000)
            ->assertSee($employee->name);
    });
});

test('supervisor can approve checklog', function () {
    $this->browse(function (Browser $browser) {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');
        
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $checklog = Ticking::factory()->create([
            'user_id' => $employee->id,
            'supervisor_approval_status' => 0,
        ]);
        
        $browser->visit('/login')
            ->type('#email', $supervisor->email)
            ->type('#password', 'password')
            ->press('Login')
            ->pause(1000)
            ->visit('/portal/checklogs')
            ->click("button[wire\\:click='initData({$checklog->id})']")
            ->pause(500)
            ->select('#supervisor_approval_status', '1')
            ->type('#supervisor_approval_reason', 'Approved')
            ->click('button:contains("Update")')
            ->pause(1000)
            ->assertSee('updated');
    });
});

test('manager can approve checklog', function () {
    $this->browse(function (Browser $browser) {
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        
        $company = Company::factory()->create();
        $company->managers()->attach($manager->id);
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $checklog = Ticking::factory()->create([
            'user_id' => $employee->id,
            'manager_approval_status' => 0,
        ]);
        
        $browser->visit('/login')
            ->type('#email', $manager->email)
            ->type('#password', 'password')
            ->press('Login')
            ->pause(1000)
            ->visit('/portal/checklogs')
            ->click("button[wire\\:click='initData({$checklog->id})']")
            ->pause(500)
            ->select('#manager_approval_status', '1')
            ->type('#manager_approval_reason', 'Approved')
            ->click('button:contains("Update")')
            ->pause(1000)
            ->assertSee('updated');
    });
});

test('user can bulk approve checklogs', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $checklogs = Ticking::factory()->count(2)->create([
            'user_id' => $employee->id,
            'supervisor_approval_status' => 0,
        ]);
        
        $browser->visit('/portal/checklogs')
            ->check("input[type='checkbox'][value='{$checklogs[0]->id}']")
            ->check("input[type='checkbox'][value='{$checklogs[1]->id}']")
            ->pause(500)
            ->select('#bulk_approval_status', '1')
            ->click('button:contains("Bulk Approve")')
            ->pause(1000)
            ->assertSee('approved');
    });
});

test('user can delete a checklog', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $checklog = Ticking::factory()->create(['user_id' => $employee->id]);
        
        $browser->visit('/portal/checklogs')
            ->click("button[wire\\:click='initData({$checklog->id})']")
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

test('user can switch between active and deleted tabs', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $checklog = Ticking::factory()->create(['user_id' => $employee->id]);
        $checklog->delete();
        
        $browser->visit('/portal/checklogs')
            ->click('button:contains("Deleted")')
            ->pause(500)
            ->assertSee('Deleted');
    });
});

test('user can restore a deleted checklog', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $checklog = Ticking::factory()->create(['user_id' => $employee->id]);
        $checklog->delete();
        
        $browser->visit('/portal/checklogs')
            ->click('button:contains("Deleted")')
            ->pause(500)
            ->click("button[wire\\:click='restore({$checklog->id})']")
            ->pause(500)
            ->waitFor('#RestoreModal', 5)
            ->within('#RestoreModal', function ($modal) {
                $modal->press('Restore');
            })
            ->pause(1000)
            ->assertSee('restored');
    });
});

test('user can export checklogs', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        Ticking::factory()->count(5)->create(['user_id' => $employee->id]);
        
        $browser->visit('/portal/checklogs')
            ->click('button:contains("Export")')
            ->pause(1000)
            ->assertSee('Export');
    });
});

test('user can change order by field', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/checklogs')
            ->select('#orderBy', 'created_at')
            ->pause(500)
            ->assertSelected('#orderBy', 'created_at');
    });
});

test('user can change items per page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        Ticking::factory()->count(10)->create(['user_id' => $employee->id]);
        
        $browser->visit('/portal/checklogs')
            ->select('#perPage', '5')
            ->pause(500)
            ->assertSelected('#perPage', '5');
    });
});









