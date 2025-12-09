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
        
        $this->visitAndWait($browser, "/portal/company/{$company->uuid}/employees");
        $browser->assertPathIs("/portal/company/{$company->uuid}/employees");
    });
});

test('user can access employees page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');

        // First check if we can access the dashboard
        $this->visitAndWait($browser, "/portal/dashboard");
        $browser->assertSee('Dashboard');

        // Now try the employees page
        $company = Company::factory()->create();
        $this->visitAndWait($browser, "/portal/company/{$company->uuid}/employees");

        // Check current URL to see if we got redirected
        $currentUrl = $browser->driver->getCurrentURL();
        if (str_contains($currentUrl, 'login') || str_contains($currentUrl, '403') || str_contains($currentUrl, '401')) {
            // Authentication or authorization issue
            $this->fail("Access denied. Current URL: " . $currentUrl);
        }

        // If we get to the employees page, basic test passes
        $browser->assertPathIs("/portal/company/{$company->uuid}/employees");
    });
});

test('user can open create employee modal', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        
        $this->visitAndWait($browser, "/portal/company/{$company->uuid}/employees");
        $browser->click('#create-employee-btn')
            ->pause(1000)
            ->waitFor('#EmployeeModal', 5)
            ->assertSee('Create a new employee');
    });
});

test('user can create an employee', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        
        $this->visitAndWait($browser, "/portal/company/{$company->uuid}/employees");
        $browser->click('#create-employee-btn')
            ->pause(1000)
            ->waitFor('#EmployeeModal', 5)
            ->within('#EmployeeModal', function ($modal) use ($department) {
                $modal->type('#first_name', 'John')
                    ->type('#last_name', 'Doe')
                    ->type('#email', 'john@example.com')
                    ->type('#matricule', 'EMP001')
                    ->select('#department_id', (string)$department->id)
                    ->press('Add to');
            })
            ->pause(2000)
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
        
        $this->visitAndWait($browser, "/portal/company/{$company->uuid}/employees");
        $browser->click("#edit-employee-btn-{$employee->id}")
            ->pause(1000)
            ->waitFor('#EmployeeModal', 5)
            ->within('#EmployeeModal', function ($modal) {
                $modal->type('#first_name', 'Updated')
                    ->press('Update');
            })
            ->pause(2000)
            ->assertSee('Updated');
    });
});

test('user can delete an employee', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        
        $this->visitAndWait($browser, "/portal/company/{$company->uuid}/employees");
        $browser->click("#delete-employee-btn-{$employee->id}")
            ->pause(1000)
            ->waitFor('#DeleteModal', 5)
            ->within('#DeleteModal', function ($modal) {
                $modal->press('Delete');
            })
            ->pause(2000)
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
        
        $this->visitAndWait($browser, "/portal/company/{$company->uuid}/employees");
        $browser->click('#deleted-employees-tab')
            ->pause(2000)
            ->assertSee('Deleted');
    });
});

test('user can select all employees', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        User::factory()->count(3)->create(['department_id' => $department->id]);
        
        $this->visitAndWait($browser, "/portal/company/{$company->uuid}/employees");
        $browser->check('#select-all-employees')
            ->pause(1000)
            ->assertChecked('#select-all-employees');
    });
});

test('user can bulk delete employees', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employees = User::factory()->count(2)->create(['department_id' => $department->id]);
        
        $this->visitAndWait($browser, "/portal/company/{$company->uuid}/employees");
        $browser->check("#employee-checkbox-{$employees[0]->id}")
            ->check("#employee-checkbox-{$employees[1]->id}")
            ->pause(1000)
            ->click('#bulk-delete-employees-btn')
            ->pause(1000)
            ->waitFor('#BulkDeleteModal', 5)
            ->within('#BulkDeleteModal', function ($modal) {
                $modal->press('Delete');
            })
            ->pause(2000)
            ->assertSee('moved to trash');
    });
});

test('user can change order by field', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        
        $this->visitAndWait($browser, "/portal/company/{$company->uuid}/employees");
        $browser->select('#employee-order-by', 'last_name')
            ->pause(1000)
            ->assertSelected('#employee-order-by', 'last_name');
    });
});

test('user can change items per page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        User::factory()->count(10)->create(['department_id' => $department->id]);
        
        $this->visitAndWait($browser, "/portal/company/{$company->uuid}/employees");
        $browser->select('#employee-per-page', '5')
            ->pause(1000)
            ->assertSelected('#employee-per-page', '5');
    });
});

test('user sees validation errors when creating employee without required fields', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        
        $this->visitAndWait($browser, "/portal/company/{$company->uuid}/employees");
        $browser->click('#create-employee-btn')
            ->pause(1000)
            ->waitFor('#EmployeeModal', 5)
            ->within('#EmployeeModal', function ($modal) {
                $modal->press('Add to');
            })
            ->pause(1000)
            ->assertSee('required');
    });
});









