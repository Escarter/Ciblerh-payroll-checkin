<?php

use App\Models\User;
use App\Models\Company;
use App\Models\Department;
use Laravel\Dusk\Browser;

beforeEach(function () {
    // Setup
});

test('user can view departments page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        
        $browser->visit("/portal/company/{$company->uuid}/departments")
            ->assertSee('Departments')
            ->assertPathIs("/portal/company/{$company->uuid}/departments");
    });
});

test('user can search for departments', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create([
            'company_id' => $company->id,
            'name' => 'IT Department',
        ]);
        
        $browser->visit("/portal/company/{$company->uuid}/departments")
            ->type('#search', 'IT')
            ->pause(1000)
            ->assertSee('IT Department');
    });
});

test('user can create a department', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        
        $browser->visit("/portal/company/{$company->uuid}/departments")
            ->click('#create-department-btn')
            ->pause(500)
            ->waitFor('#DepartmentModal', 5)
            ->within('#DepartmentModal', function ($modal) {
                $modal->type('#department-name', 'New Department')
                    ->press('Create');
            })
            ->pause(1000)
            ->assertSee('New Department');
    });
});

test('user can edit a department', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        
        $browser->visit("/portal/company/{$company->uuid}/departments")
            ->click("#edit-department-{$department->id}")
            ->pause(500)
            ->waitFor('#DepartmentModal', 5)
            ->within('#DepartmentModal', function ($modal) {
                $modal->type('#department-name', 'Updated Department')
                    ->press('Update');
            })
            ->pause(1000)
            ->assertSee('Updated Department');
    });
});

test('user can delete a department', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        
        $browser->visit("/portal/company/{$company->uuid}/departments")
            ->click("#delete-department-{$department->id}")
            ->pause(500)
            ->waitFor('#DeleteModal', 5)
            ->within('#DeleteModal', function ($modal) {
                $modal->press('Confirm');
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
        $department->delete();
        
        $browser->visit("/portal/company/{$company->uuid}/departments")
            ->click('#deleted-departments-tab')
            ->pause(500)
            ->assertSee('Deleted');
    });
});

test('user can restore a deleted department', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $department->delete();
        
        $browser->visit("/portal/company/{$company->uuid}/departments")
            ->click('#deleted-departments-tab')
            ->pause(500)
            ->click("#restore-department-{$department->id}")
            ->pause(500)
            ->waitFor('#RestoreModal', 5)
            ->within('#RestoreModal', function ($modal) {
                $modal->press('Confirm Restore');
            })
            ->pause(1000)
            ->assertSee('restored');
    });
});

test('user can select all departments', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        Department::factory()->count(3)->create(['company_id' => $company->id]);
        
        $browser->visit("/portal/company/{$company->uuid}/departments")
            ->click('#select-all-departments-btn')
            ->pause(500)
            ->assertSee('departments');
    });
});

test('user can bulk delete departments', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $departments = Department::factory()->count(2)->create(['company_id' => $company->id]);
        
        $browser->visit("/portal/company/{$company->uuid}/departments")
            ->click("#department-card-{$departments[0]->id}")
            ->click("#department-card-{$departments[1]->id}")
            ->pause(500)
            ->click('#bulk-delete-departments-btn')
            ->pause(500)
            ->waitFor('#BulkDeleteModal', 5)
            ->within('#BulkDeleteModal', function ($modal) {
                $modal->press('Move to Trash');
            })
            ->pause(1000)
            ->assertSee('moved to trash');
    });
});








