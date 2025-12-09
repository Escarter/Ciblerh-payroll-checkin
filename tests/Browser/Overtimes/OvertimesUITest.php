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
        
        $this->visitAndWait($browser, '/portal/overtimes');
        $browser->assertSee('Overtimes')
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
        
        $this->visitAndWait($browser, '/portal/overtimes');
        $browser->type('#overtimes-search', $employee->first_name)
            ->pause(2000)
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
            ->click("#edit-overtime-{$overtime->id}")
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
            ->click("#edit-overtime-{$overtime->id}")
            ->pause(500)
            ->waitFor('#EditOvertimeModal', 5)
            ->within('#EditOvertimeModal', function ($modal) {
                $modal->select('#approval_status', '1')
                    ->type('#approval_reason', 'Approved')
                    ->press('#confirm-overtime-btn');
            })
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
            ->click("#edit-overtime-{$overtime->id}")
            ->pause(500)
            ->waitFor('#EditOvertimeModal', 5)
            ->within('#EditOvertimeModal', function ($modal) {
                $modal->select('#approval_status', '2')
                    ->type('#approval_reason', 'Rejected')
                    ->press('#confirm-overtime-btn');
            })
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
            ->click('.card')  // Click first overtime card to select it
            ->pause(500)
            ->click('#bulk-approve-overtimes-btn')
            ->pause(500)
            ->waitFor('#EditBulkOvertimeModal', 5)
            ->within('#EditBulkOvertimeModal', function ($modal) {
                $modal->type('#bulk_approval_reason', 'Bulk approved for testing')
                    ->press('#bulk-overtime-confirm-btn');
            })
            ->pause(1000)
            ->assertSee('updated');
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
            ->click("#delete-overtime-{$overtime->id}")
            ->pause(500)
            ->waitFor('#DeleteModal', 5)
            ->within('#DeleteModal', function ($modal) {
                $modal->press('#confirm-delete-btn');
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
            ->click('#deleted-overtimes-tab')
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
            ->click('#deleted-overtimes-tab')
            ->pause(500)
            ->click("#restore-overtime-{$overtime->id}")
            ->pause(500)
            ->waitFor('#RestoreModal', 5)
            ->within('#RestoreModal', function ($modal) {
                $modal->press('#confirm-restore-btn');
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
            ->click('#export-overtimes-btn')
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








