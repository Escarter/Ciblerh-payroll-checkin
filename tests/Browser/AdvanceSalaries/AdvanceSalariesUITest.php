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
            ->click("#edit-advance-salary-{$advanceSalary->id}")
            ->pause(500)
            ->waitFor('#EditAdvanceSalaryModal', 5)
            ->within('#EditAdvanceSalaryModal', function ($modal) {
                $modal->select('#approval_status', '1')
                    ->type('#approval_reason', 'Approved')
                    ->press('#confirm-advance-salary-btn');
            })
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
            ->click("#edit-advance-salary-{$advanceSalary->id}")
            ->pause(500)
            ->waitFor('#EditAdvanceSalaryModal', 5)
            ->within('#EditAdvanceSalaryModal', function ($modal) {
                $modal->select('#approval_status', '2')
                    ->type('#approval_reason', 'Rejected')
                    ->press('#confirm-advance-salary-btn');
            })
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
            ->click('#select-all-advance-salaries-checkbox')
            ->pause(2000)  // Wait longer for Livewire to update
            ->click('#bulk-approve-advance-salaries-btn')
            ->pause(500)
            ->waitFor('#EditBulkAdvanceSalaryModal', 5)
            ->within('#EditBulkAdvanceSalaryModal', function ($modal) {
                $modal->type('#bulk_approval_reason', 'Bulk approved for testing')
                    ->press('#bulk-advance-salary-confirm-btn');
            })
            ->pause(1000)
            ->assertSee('updated');
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
            ->click("#delete-advance-salary-{$advanceSalary->id}")
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
        $advanceSalary = AdvanceSalary::factory()->create([
            'user_id' => $employee->id,
            'company_id' => $company->id,
        ]);
        $advanceSalary->delete();
        
        $browser->visit('/portal/advance-salaries')
            ->click('#deleted-advance-salaries-tab')
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
            ->click('#deleted-advance-salaries-tab')
            ->pause(1000)
            ->click("#restore-advance-salary-{$advanceSalary->id}")
            ->pause(1000)
            ->waitFor('#RestoreModal', 10)
            ->within('#RestoreModal', function ($modal) {
                $modal->pause(500)
                    ->press('#confirm-restore-btn');
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
            ->click('#export-advance-salaries-btn')
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









