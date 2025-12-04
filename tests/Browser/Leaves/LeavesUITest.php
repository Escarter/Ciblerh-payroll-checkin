<?php

use App\Models\User;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Company;
use App\Models\Department;
use Laravel\Dusk\Browser;

beforeEach(function () {
    // Setup
});

test('user can view leaves page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/leaves')
            ->assertSee('Leaves')
            ->assertPathIs('/portal/leaves');
    });
});

test('user can search for leaves', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $leaveType = LeaveType::factory()->create();
        $leave = Leave::factory()->create([
            'user_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
        ]);
        
        $browser->visit('/portal/leaves')
            ->type('#search', $employee->first_name)
            ->pause(1000)
            ->assertSee($employee->first_name);
    });
});

test('user can view leave details', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $leaveType = LeaveType::factory()->create();
        $leave = Leave::factory()->create([
            'user_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
        ]);
        
        $browser->visit('/portal/leaves')
            ->click("button[wire\\:click='initData({$leave->id})']")
            ->pause(500)
            ->assertSee($employee->name);
    });
});

test('supervisor can approve leave', function () {
    $this->browse(function (Browser $browser) {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');
        
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $leaveType = LeaveType::factory()->create();
        $leave = Leave::factory()->create([
            'user_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'supervisor_approval_status' => 0,
        ]);
        
        $browser->visit('/login')
            ->type('#email', $supervisor->email)
            ->type('#password', 'password')
            ->press('Login')
            ->pause(1000)
            ->visit('/portal/leaves')
            ->click("button[wire\\:click='initData({$leave->id})']")
            ->pause(500)
            ->select('#supervisor_approval_status', '1')
            ->type('#supervisor_approval_reason', 'Approved')
            ->click('button:contains("Update")')
            ->pause(1000)
            ->assertSee('updated');
    });
});

test('manager can approve leave', function () {
    $this->browse(function (Browser $browser) {
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        
        $company = Company::factory()->create();
        $company->managers()->attach($manager->id);
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $leaveType = LeaveType::factory()->create();
        $leave = Leave::factory()->create([
            'user_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'manager_approval_status' => 0,
        ]);
        
        $browser->visit('/login')
            ->type('#email', $manager->email)
            ->type('#password', 'password')
            ->press('Login')
            ->pause(1000)
            ->visit('/portal/leaves')
            ->click("button[wire\\:click='initData({$leave->id})']")
            ->pause(500)
            ->select('#manager_approval_status', '1')
            ->type('#manager_approval_reason', 'Approved')
            ->click('button:contains("Update")')
            ->pause(1000)
            ->assertSee('updated');
    });
});

test('user can bulk approve leaves', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $leaveType = LeaveType::factory()->create();
        $leaves = Leave::factory()->count(2)->create([
            'user_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'supervisor_approval_status' => 0,
        ]);
        
        $browser->visit('/portal/leaves')
            ->check("input[type='checkbox'][value='{$leaves[0]->id}']")
            ->check("input[type='checkbox'][value='{$leaves[1]->id}']")
            ->pause(500)
            ->select('#bulk_approval_status', '1')
            ->click('button:contains("Bulk Approve")')
            ->pause(1000)
            ->assertSee('approved');
    });
});

test('user can delete a leave', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $leaveType = LeaveType::factory()->create();
        $leave = Leave::factory()->create([
            'user_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
        ]);
        
        $browser->visit('/portal/leaves')
            ->click("button[wire\\:click='initData({$leave->id})']")
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
        $leaveType = LeaveType::factory()->create();
        $leave = Leave::factory()->create([
            'user_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
        ]);
        $leave->delete();
        
        $browser->visit('/portal/leaves')
            ->click('button:contains("Deleted")')
            ->pause(500)
            ->assertSee('Deleted');
    });
});

test('user can restore a deleted leave', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $leaveType = LeaveType::factory()->create();
        $leave = Leave::factory()->create([
            'user_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
        ]);
        $leave->delete();
        
        $browser->visit('/portal/leaves')
            ->click('button:contains("Deleted")')
            ->pause(500)
            ->click("button[wire\\:click='restore({$leave->id})']")
            ->pause(500)
            ->waitFor('#RestoreModal', 5)
            ->within('#RestoreModal', function ($modal) {
                $modal->press('Restore');
            })
            ->pause(1000)
            ->assertSee('restored');
    });
});

test('user can select all leaves', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $leaveType = LeaveType::factory()->create();
        Leave::factory()->count(3)->create([
            'user_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
        ]);
        
        $browser->visit('/portal/leaves')
            ->check('input[type="checkbox"][wire\\:model="selectAll"]')
            ->pause(500)
            ->assertChecked('input[type="checkbox"][wire\\:model="selectAll"]');
    });
});

test('user can change order by field', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/leaves')
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
        $leaveType = LeaveType::factory()->create();
        Leave::factory()->count(10)->create([
            'user_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
        ]);
        
        $browser->visit('/portal/leaves')
            ->select('#perPage', '5')
            ->pause(500)
            ->assertSelected('#perPage', '5');
    });
});

test('user can export leaves', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $leaveType = LeaveType::factory()->create();
        Leave::factory()->count(5)->create([
            'user_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
        ]);
        
        $browser->visit('/portal/leaves')
            ->click('button:contains("Export")')
            ->pause(1000)
            ->assertSee('Export');
    });
});






