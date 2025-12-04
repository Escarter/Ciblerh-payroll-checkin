<?php

use App\Models\User;
use App\Models\Company;
use App\Models\Department;
use Laravel\Dusk\Browser;

beforeEach(function () {
    // Setup
});

test('user can view employees page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        
        $browser->visit("/portal/company/{$company->uuid}/employees")
            ->assertSee('Employees')
            ->assertPathIs("/portal/company/{$company->uuid}/employees");
    });
});

test('user can search for employees', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create([
            'department_id' => $department->id,
            'first_name' => 'John',
        ]);
        
        $browser->visit("/portal/company/{$company->uuid}/employees")
            ->type('#search', 'John')
            ->pause(1000)
            ->assertSee('John');
    });
});

test('user can open create employee modal', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        
        $browser->visit("/portal/company/{$company->uuid}/employees")
            ->click('button:contains("Create Employee")')
            ->pause(500)
            ->waitFor('#EmployeeModal', 5)
            ->assertSee('Create Employee');
    });
});

test('user can create an employee', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        
        $browser->visit("/portal/company/{$company->uuid}/employees")
            ->click('button:contains("Create Employee")')
            ->pause(500)
            ->waitFor('#EmployeeModal', 5)
            ->within('#EmployeeModal', function ($modal) use ($department) {
                $modal->type('#first_name', 'John')
                    ->type('#last_name', 'Doe')
                    ->type('#email', 'john@example.com')
                    ->type('#matricule', 'EMP001')
                    ->select('#department_id', (string)$department->id)
                    ->press('Save');
            })
            ->pause(1000)
            ->assertSee('John');
    });
});

test('user can edit an employee', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create([
            'department_id' => $department->id,
            'first_name' => 'Original',
        ]);
        
        $browser->visit("/portal/company/{$company->uuid}/employees")
            ->click("button[wire\\:click='initData({$employee->id})']")
            ->pause(500)
            ->waitFor('#EmployeeModal', 5)
            ->within('#EmployeeModal', function ($modal) {
                $modal->type('#first_name', 'Updated')
                    ->press('Update');
            })
            ->pause(1000)
            ->assertSee('Updated');
    });
});

test('user can delete an employee', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        
        $browser->visit("/portal/company/{$company->uuid}/employees")
            ->click("button[wire\\:click='initData({$employee->id})']")
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
        $employee->delete();
        
        $browser->visit("/portal/company/{$company->uuid}/employees")
            ->click('button:contains("Deleted")')
            ->pause(500)
            ->assertSee('Deleted');
    });
});

test('user can select all employees', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        User::factory()->count(3)->create(['department_id' => $department->id]);
        
        $browser->visit("/portal/company/{$company->uuid}/employees")
            ->check('input[type="checkbox"][wire\\:model="selectAll"]')
            ->pause(500)
            ->assertChecked('input[type="checkbox"][wire\\:model="selectAll"]');
    });
});

test('user can bulk delete employees', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employees = User::factory()->count(2)->create(['department_id' => $department->id]);
        
        $browser->visit("/portal/company/{$company->uuid}/employees")
            ->check("input[type='checkbox'][value='{$employees[0]->id}']")
            ->check("input[type='checkbox'][value='{$employees[1]->id}']")
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
        $company = Company::factory()->create();
        
        $browser->visit("/portal/company/{$company->uuid}/employees")
            ->select('#orderBy', 'last_name')
            ->pause(500)
            ->assertSelected('#orderBy', 'last_name');
    });
});

test('user can change items per page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        User::factory()->count(10)->create(['department_id' => $department->id]);
        
        $browser->visit("/portal/company/{$company->uuid}/employees")
            ->select('#perPage', '5')
            ->pause(500)
            ->assertSelected('#perPage', '5');
    });
});

test('user sees validation errors when creating employee without required fields', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        
        $browser->visit("/portal/company/{$company->uuid}/employees")
            ->click('button:contains("Create Employee")')
            ->pause(500)
            ->waitFor('#EmployeeModal', 5)
            ->within('#EmployeeModal', function ($modal) {
                $modal->press('Save');
            })
            ->pause(500)
            ->assertSee('required');
    });
});






