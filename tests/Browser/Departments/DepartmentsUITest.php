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
            ->click('button:contains("Create Department")')
            ->pause(500)
            ->waitFor('#DepartmentModal', 5)
            ->within('#DepartmentModal', function ($modal) {
                $modal->type('#name', 'New Department')
                    ->type('#description', 'Test description')
                    ->press('Save');
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
            ->click("button[wire\\:click='initData({$department->id})']")
            ->pause(500)
            ->waitFor('#DepartmentModal', 5)
            ->within('#DepartmentModal', function ($modal) {
                $modal->type('#name', 'Updated Department')
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
            ->click("button[wire\\:click='initData({$department->id})']")
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
        $department->delete();
        
        $browser->visit("/portal/company/{$company->uuid}/departments")
            ->click('button:contains("Deleted")')
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
            ->click('button:contains("Deleted")')
            ->pause(500)
            ->click("button[wire\\:click='restore({$department->id})']")
            ->pause(500)
            ->waitFor('#RestoreModal', 5)
            ->within('#RestoreModal', function ($modal) {
                $modal->press('Restore');
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
            ->check('input[type="checkbox"][wire\\:model="selectAll"]')
            ->pause(500)
            ->assertChecked('input[type="checkbox"][wire\\:model="selectAll"]');
    });
});

test('user can bulk delete departments', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $departments = Department::factory()->count(2)->create(['company_id' => $company->id]);
        
        $browser->visit("/portal/company/{$company->uuid}/departments")
            ->check("input[type='checkbox'][value='{$departments[0]->id}']")
            ->check("input[type='checkbox'][value='{$departments[1]->id}']")
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

