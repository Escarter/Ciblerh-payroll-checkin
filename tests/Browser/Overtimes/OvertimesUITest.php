<?php

use App\Models\User;
use App\Models\Overtime;
use App\Models\Company;
use App\Models\Department;
use Laravel\Dusk\Browser;

beforeEach(function () {
    // Setup
});

test('user can view overtimes page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/overtimes')
            ->assertSee('Overtimes')
            ->assertPathIs('/portal/overtimes');
    });
});

test('user can search for overtimes', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $overtime = Overtime::factory()->create(['user_id' => $employee->id]);
        
        $browser->visit('/portal/overtimes')
            ->type('#search', $employee->first_name)
            ->pause(1000)
            ->assertSee($employee->first_name);
    });
});

test('user can view overtime details', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $overtime = Overtime::factory()->create(['user_id' => $employee->id]);
        
        $browser->visit('/portal/overtimes')
            ->click("button[wire\\:click='initData({$overtime->id})']")
            ->pause(500)
            ->assertSee($employee->name);
    });
});

test('user can approve overtime', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $overtime = Overtime::factory()->create([
            'user_id' => $employee->id,
            'approval_status' => 0,
        ]);
        
        $browser->visit('/portal/overtimes')
            ->click("button[wire\\:click='initData({$overtime->id})']")
            ->pause(500)
            ->select('#approval_status', '1')
            ->type('#approval_reason', 'Approved')
            ->click('button:contains("Update")')
            ->pause(1000)
            ->assertSee('updated');
    });
});

test('user can reject overtime', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $overtime = Overtime::factory()->create([
            'user_id' => $employee->id,
            'approval_status' => 0,
        ]);
        
        $browser->visit('/portal/overtimes')
            ->click("button[wire\\:click='initData({$overtime->id})']")
            ->pause(500)
            ->select('#approval_status', '2')
            ->type('#approval_reason', 'Rejected')
            ->click('button:contains("Update")')
            ->pause(1000)
            ->assertSee('updated');
    });
});

test('user can bulk approve overtimes', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $overtimes = Overtime::factory()->count(2)->create([
            'user_id' => $employee->id,
            'approval_status' => 0,
        ]);
        
        $browser->visit('/portal/overtimes')
            ->check("input[type='checkbox'][value='{$overtimes[0]->id}']")
            ->check("input[type='checkbox'][value='{$overtimes[1]->id}']")
            ->pause(500)
            ->select('#bulk_approval_status', '1')
            ->click('button:contains("Bulk Approve")')
            ->pause(1000)
            ->assertSee('approved');
    });
});

test('user can delete an overtime', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $overtime = Overtime::factory()->create(['user_id' => $employee->id]);
        
        $browser->visit('/portal/overtimes')
            ->click("button[wire\\:click='initData({$overtime->id})']")
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
        $overtime = Overtime::factory()->create(['user_id' => $employee->id]);
        $overtime->delete();
        
        $browser->visit('/portal/overtimes')
            ->click('button:contains("Deleted")')
            ->pause(500)
            ->assertSee('Deleted');
    });
});

test('user can restore a deleted overtime', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $overtime = Overtime::factory()->create(['user_id' => $employee->id]);
        $overtime->delete();
        
        $browser->visit('/portal/overtimes')
            ->click('button:contains("Deleted")')
            ->pause(500)
            ->click("button[wire\\:click='restore({$overtime->id})']")
            ->pause(500)
            ->waitFor('#RestoreModal', 5)
            ->within('#RestoreModal', function ($modal) {
                $modal->press('Restore');
            })
            ->pause(1000)
            ->assertSee('restored');
    });
});

test('user can export overtimes', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        Overtime::factory()->count(5)->create(['user_id' => $employee->id]);
        
        $browser->visit('/portal/overtimes')
            ->click('button:contains("Export")')
            ->pause(1000)
            ->assertSee('Export');
    });
});

test('user can change order by field', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/overtimes')
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
        Overtime::factory()->count(10)->create(['user_id' => $employee->id]);
        
        $browser->visit('/portal/overtimes')
            ->select('#perPage', '5')
            ->pause(500)
            ->assertSelected('#perPage', '5');
    });
});








