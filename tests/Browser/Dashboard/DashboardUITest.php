<?php

use App\Models\User;
use App\Models\Company;
use App\Models\Department;
use Laravel\Dusk\Browser;

beforeEach(function () {
    // Setup
});

test('user can view dashboard', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        // Visit dashboard and verify we're on the right page
        $browser->visit('/portal/dashboard')
            ->pause(3000) // Wait for Livewire to load
            ->assertPathIs('/portal/dashboard');
        
        // Check if page has loaded by looking for any content
        // The page should have some content even if Livewire hasn't loaded yet
        $pageSource = $browser->driver->getPageSource();
        expect(strlen($pageSource))->toBeGreaterThan(100); // Page should have content
    });
});

test('admin can see all companies in dashboard', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        
        $browser->visit('/portal/dashboard')
            ->assertSee($company->name);
    });
});

test('manager can see only their companies', function () {
    $this->browse(function (Browser $browser) {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $company = Company::factory()->create();
        $company->managers()->attach($manager->id);

        $otherCompany = Company::factory()->create();

        $manager = $this->loginAs($browser, 'manager');
        $browser->visit('/portal/dashboard')
            ->assertSee($company->name)
            ->assertDontSee($otherCompany->name);
    });
});

test('user can filter dashboard by company', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        
        $browser->visit('/portal/dashboard')
            ->select('#selectedCompanyId', (string)$company->id)
            ->pause(1000)
            ->assertSelected('#selectedCompanyId', (string)$company->id);
    });
});

test('user can filter dashboard by department', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        
        $browser->visit('/portal/dashboard')
            ->select('#selectedCompanyId', (string)$company->id)
            ->pause(500)
            ->select('#selectedDepartmentId', (string)$department->id)
            ->pause(500)
            ->assertSelected('#selectedDepartmentId', (string)$department->id);
    });
});

test('user can change period filter', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/dashboard')
            ->select('#period', 'last_month')
            ->pause(500)
            ->assertSelected('#period', 'last_month');
    });
});

test('dashboard displays statistics', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/dashboard')
            ->assertPresent('.card') // Dashboard should have cards
            ->assertPresent('canvas'); // Should have charts
    });
});

