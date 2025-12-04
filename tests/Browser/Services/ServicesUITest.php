<?php

use App\Models\User;
use App\Models\Company;
use App\Models\Department;
use App\Models\Service;
use Laravel\Dusk\Browser;

beforeEach(function () {
    // Setup
});

test('user can view services page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        
        $browser->visit("/portal/department/{$department->uuid}/services")
            ->assertSee('Services')
            ->assertPathIs("/portal/department/{$department->uuid}/services");
    });
});

test('user can search for services', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $service = Service::factory()->create([
            'department_id' => $department->id,
            'name' => 'IT Service',
        ]);
        
        $browser->visit("/portal/department/{$department->uuid}/services")
            ->type('#search', 'IT')
            ->pause(1000)
            ->assertSee('IT Service');
    });
});

test('user can create a service', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        
        $browser->visit("/portal/department/{$department->uuid}/services")
            ->click('button:contains("Create Service")')
            ->pause(500)
            ->waitFor('#ServiceModal', 5)
            ->within('#ServiceModal', function ($modal) {
                $modal->type('#name', 'New Service')
                    ->type('#description', 'Test description')
                    ->press('Save');
            })
            ->pause(1000)
            ->assertSee('New Service');
    });
});

test('user can edit a service', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $service = Service::factory()->create(['department_id' => $department->id]);
        
        $browser->visit("/portal/department/{$department->uuid}/services")
            ->click("button[wire\\:click='initData({$service->id})']")
            ->pause(500)
            ->waitFor('#ServiceModal', 5)
            ->within('#ServiceModal', function ($modal) {
                $modal->type('#name', 'Updated Service')
                    ->press('Update');
            })
            ->pause(1000)
            ->assertSee('Updated Service');
    });
});

test('user can delete a service', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $service = Service::factory()->create(['department_id' => $department->id]);
        
        $browser->visit("/portal/department/{$department->uuid}/services")
            ->click("button[wire\\:click='initData({$service->id})']")
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
        $service = Service::factory()->create(['department_id' => $department->id]);
        $service->delete();
        
        $browser->visit("/portal/department/{$department->uuid}/services")
            ->click('button:contains("Deleted")')
            ->pause(500)
            ->assertSee('Deleted');
    });
});

test('user can restore a deleted service', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $service = Service::factory()->create(['department_id' => $department->id]);
        $service->delete();
        
        $browser->visit("/portal/department/{$department->uuid}/services")
            ->click('button:contains("Deleted")')
            ->pause(500)
            ->click("button[wire\\:click='restore({$service->id})']")
            ->pause(500)
            ->waitFor('#RestoreModal', 5)
            ->within('#RestoreModal', function ($modal) {
                $modal->press('Restore');
            })
            ->pause(1000)
            ->assertSee('restored');
    });
});

test('user can select all services', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        Service::factory()->count(3)->create(['department_id' => $department->id]);
        
        $browser->visit("/portal/department/{$department->uuid}/services")
            ->check('input[type="checkbox"][wire\\:model="selectAll"]')
            ->pause(500)
            ->assertChecked('input[type="checkbox"][wire\\:model="selectAll"]');
    });
});

test('user can bulk delete services', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $services = Service::factory()->count(2)->create(['department_id' => $department->id]);
        
        $browser->visit("/portal/department/{$department->uuid}/services")
            ->check("input[type='checkbox'][value='{$services[0]->id}']")
            ->check("input[type='checkbox'][value='{$services[1]->id}']")
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




