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
            ->click('#create-service-btn')
            ->pause(500)
            ->waitFor('#ServiceModal', 5)
            ->within('#ServiceModal', function ($modal) {
                $modal->type('#service-name', 'New Service')
                    ->press('#save-service-btn');
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
            ->click("#edit-service-{$service->id}")
            ->pause(500)
            ->waitFor('#ServiceModal', 5)
            ->within('#ServiceModal', function ($modal) {
                $modal->type('#service-name', 'Updated Service')
                    ->select('#service-is-active', '1')
                    ->press('#save-service-btn');
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
            ->click("#delete-service-{$service->id}")
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
        $service = Service::factory()->create(['department_id' => $department->id]);
        $service->delete();
        
        $browser->visit("/portal/department/{$department->uuid}/services")
            ->click('#deleted-services-tab')
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
            ->click('#deleted-services-tab')
            ->pause(500)
            ->click("#restore-service-{$service->id}")
            ->pause(500)
            ->waitFor('#RestoreModal', 5)
            ->within('#RestoreModal', function ($modal) {
                $modal->press('#confirm-restore-btn');
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
            ->click('#select-all-services-checkbox')
            ->pause(500)
            ->assertPresent('#select-all-services-checkbox');
    });
});

test('user can bulk delete services', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $services = Service::factory()->count(2)->create(['department_id' => $department->id]);
        
        $browser->visit("/portal/department/{$department->uuid}/services")
            ->click('.card')
            ->pause(1000)
            ->click('#bulk-delete-services-btn')
            ->pause(500)
            ->waitFor('#BulkDeleteModal', 5)
            ->within('#BulkDeleteModal', function ($modal) {
                $modal->press('Delete');
            })
            ->pause(1000)
            ->assertSee('moved to trash');
    });
});









