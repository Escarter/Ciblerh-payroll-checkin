<?php

use App\Models\User;
use App\Models\AdvanceSalary;
use App\Models\Company;
use App\Models\Department;
use Laravel\Dusk\Browser;

beforeEach(function () {
    // Setup
});

test('user can view advance salaries page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/advance-salaries')
            ->assertSee('Advance Salaries')
            ->assertPathIs('/portal/advance-salaries');
    });
});

test('user can search for advance salaries', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $advanceSalary = AdvanceSalary::factory()->create([
            'user_id' => $employee->id,
            'company_id' => $company->id,
        ]);
        
        $browser->visit('/portal/advance-salaries')
            ->type('#search', $employee->first_name)
            ->pause(1000)
            ->assertSee($employee->first_name);
    });
});

test('user can view advance salary details', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $advanceSalary = AdvanceSalary::factory()->create([
            'user_id' => $employee->id,
            'company_id' => $company->id,
        ]);
        
        $browser->visit('/portal/advance-salaries')
            ->click("button[wire\\:click='initData({$advanceSalary->id})']")
            ->pause(500)
            ->assertSee($employee->name);
    });
});

test('user can approve advance salary', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $advanceSalary = AdvanceSalary::factory()->create([
            'user_id' => $employee->id,
            'company_id' => $company->id,
            'approval_status' => 0,
        ]);
        
        $browser->visit('/portal/advance-salaries')
            ->click("button[wire\\:click='initData({$advanceSalary->id})']")
            ->pause(500)
            ->select('#approval_status', '1')
            ->type('#approval_reason', 'Approved')
            ->click('button:contains("Update")')
            ->pause(1000)
            ->assertSee('updated');
    });
});

test('user can reject advance salary', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $advanceSalary = AdvanceSalary::factory()->create([
            'user_id' => $employee->id,
            'company_id' => $company->id,
            'approval_status' => 0,
        ]);
        
        $browser->visit('/portal/advance-salaries')
            ->click("button[wire\\:click='initData({$advanceSalary->id})']")
            ->pause(500)
            ->select('#approval_status', '2')
            ->type('#approval_reason', 'Rejected')
            ->click('button:contains("Update")')
            ->pause(1000)
            ->assertSee('updated');
    });
});

test('user can bulk approve advance salaries', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $advanceSalaries = AdvanceSalary::factory()->count(2)->create([
            'user_id' => $employee->id,
            'company_id' => $company->id,
            'approval_status' => 0,
        ]);
        
        $browser->visit('/portal/advance-salaries')
            ->check("input[type='checkbox'][value='{$advanceSalaries[0]->id}']")
            ->check("input[type='checkbox'][value='{$advanceSalaries[1]->id}']")
            ->pause(500)
            ->select('#bulk_approval_status', '1')
            ->click('button:contains("Bulk Approve")')
            ->pause(1000)
            ->assertSee('approved');
    });
});

test('user can delete an advance salary', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $advanceSalary = AdvanceSalary::factory()->create([
            'user_id' => $employee->id,
            'company_id' => $company->id,
        ]);
        
        $browser->visit('/portal/advance-salaries')
            ->click("button[wire\\:click='initData({$advanceSalary->id})']")
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
        $advanceSalary = AdvanceSalary::factory()->create([
            'user_id' => $employee->id,
            'company_id' => $company->id,
        ]);
        $advanceSalary->delete();
        
        $browser->visit('/portal/advance-salaries')
            ->click('button:contains("Deleted")')
            ->pause(500)
            ->assertSee('Deleted');
    });
});

test('user can restore a deleted advance salary', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $advanceSalary = AdvanceSalary::factory()->create([
            'user_id' => $employee->id,
            'company_id' => $company->id,
        ]);
        $advanceSalary->delete();
        
        $browser->visit('/portal/advance-salaries')
            ->click('button:contains("Deleted")')
            ->pause(500)
            ->click("button[wire\\:click='restore({$advanceSalary->id})']")
            ->pause(500)
            ->waitFor('#RestoreModal', 5)
            ->within('#RestoreModal', function ($modal) {
                $modal->press('Restore');
            })
            ->pause(1000)
            ->assertSee('restored');
    });
});

test('user can export advance salaries', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        AdvanceSalary::factory()->count(5)->create([
            'user_id' => $employee->id,
            'company_id' => $company->id,
        ]);
        
        $browser->visit('/portal/advance-salaries')
            ->click('button:contains("Export")')
            ->pause(1000)
            ->assertSee('Export');
    });
});

test('user can change order by field', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/advance-salaries')
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
        AdvanceSalary::factory()->count(10)->create([
            'user_id' => $employee->id,
            'company_id' => $company->id,
        ]);
        
        $browser->visit('/portal/advance-salaries')
            ->select('#perPage', '5')
            ->pause(500)
            ->assertSelected('#perPage', '5');
    });
});







