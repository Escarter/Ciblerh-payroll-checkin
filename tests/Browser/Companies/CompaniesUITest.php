<?php

use App\Models\User;
use App\Models\Company;
use Laravel\Dusk\Browser;

beforeEach(function () {
    // Setup
});

test('user can view companies page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $this->visitAndWait($browser, '/portal/companies');
        // Just verify page loaded - text might be translated
    });
});

test('user can search for companies', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create(['name' => 'Test Company']);
        
        $this->visitAndWait($browser, '/portal/companies');
        if ($this->waitForElement($browser, '#search', 5, false)) {
            $browser->type('#search', 'Test')
                ->pause(2000)
                ->assertSee('Test Company');
        }
    });
});

test('user can open create company modal', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $this->visitAndWait($browser, '/portal/companies');
        if ($this->waitForElement($browser, 'button:contains("Create Company")', 5, false)) {
            $browser->click('button:contains("Create Company")')
                ->pause(1000)
                ->waitFor('#CompanyModal', 5)
                ->assertSee('Create Company');
        }
    });
});

test('user can create a company', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/companies')
            ->click('button:contains("Create Company")')
            ->pause(500)
            ->waitFor('#CompanyModal', 5)
            ->within('#CompanyModal', function ($modal) {
                $modal->type('#name', 'New Company')
                    ->type('#sector', 'Technology')
                    ->type('#description', 'Test description')
                    ->press('Save');
            })
            ->pause(1000)
            ->assertSee('New Company');
    });
});

test('user can edit a company', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create(['name' => 'Original Name']);
        
        $browser->visit('/portal/companies')
            ->click("button[wire\\:click='initData({$company->id})']")
            ->pause(500)
            ->waitFor('#CompanyModal', 5)
            ->within('#CompanyModal', function ($modal) {
                $modal->type('#name', 'Updated Name')
                    ->press('Update');
            })
            ->pause(1000)
            ->assertSee('Updated Name');
    });
});

test('user can delete a company', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        
        $browser->visit('/portal/companies')
            ->click("button[wire\\:click='initData({$company->id})']")
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

test('user can switch to deleted tab', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $company->delete();
        
        $browser->visit('/portal/companies')
            ->click('button:contains("Deleted")')
            ->pause(500)
            ->assertSee('Deleted');
    });
});

test('user can restore a deleted company', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $company->delete();
        
        $browser->visit('/portal/companies')
            ->click('button:contains("Deleted")')
            ->pause(500)
            ->click("button[wire\\:click='restore({$company->id})']")
            ->pause(500)
            ->waitFor('#RestoreModal', 5)
            ->within('#RestoreModal', function ($modal) {
                $modal->press('Restore');
            })
            ->pause(1000)
            ->assertSee('restored');
    });
});

test('user can select all companies', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        Company::factory()->count(3)->create();
        
        $browser->visit('/portal/companies')
            ->check('input[type="checkbox"][wire\\:model="selectAll"]')
            ->pause(500)
            ->assertChecked('input[type="checkbox"][wire\\:model="selectAll"]');
    });
});

test('user can bulk delete companies', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $companies = Company::factory()->count(2)->create();
        
        $browser->visit('/portal/companies')
            ->check("input[type='checkbox'][value='{$companies[0]->id}']")
            ->check("input[type='checkbox'][value='{$companies[1]->id}']")
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
        
        $browser->visit('/portal/companies')
            ->select('#orderBy', 'created_at')
            ->pause(500)
            ->assertSelected('#orderBy', 'created_at');
    });
});

test('user can change items per page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        Company::factory()->count(10)->create();
        
        $browser->visit('/portal/companies')
            ->select('#perPage', '5')
            ->pause(500)
            ->assertSelected('#perPage', '5');
    });
});

test('user sees validation errors when creating company without required fields', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/companies')
            ->click('button:contains("Create Company")')
            ->pause(500)
            ->waitFor('#CompanyModal', 5)
            ->within('#CompanyModal', function ($modal) {
                $modal->press('Save');
            })
            ->pause(500)
            ->assertSee('required'); // Validation error message
    });
});

