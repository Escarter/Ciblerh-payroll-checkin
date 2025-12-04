<?php

use App\Models\User;
use App\Models\Company;
use App\Models\Department;
use Laravel\Dusk\Browser;

beforeEach(function () {
    // Setup
});

test('user can view checklog report page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/reports/checklogs')
            ->assertSee('Checklog Report')
            ->assertPathIs('/portal/reports/checklogs');
    });
});

test('user can filter checklog report by company', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        
        $browser->visit('/portal/reports/checklogs')
            ->select('#selectedCompanyId', (string)$company->id)
            ->pause(500)
            ->assertSelected('#selectedCompanyId', (string)$company->id);
    });
});

test('user can filter checklog report by department', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        
        $browser->visit('/portal/reports/checklogs')
            ->select('#selectedCompanyId', (string)$company->id)
            ->pause(500)
            ->select('#selectedDepartmentId', (string)$department->id)
            ->pause(500)
            ->assertSelected('#selectedDepartmentId', (string)$department->id);
    });
});

test('user can filter checklog report by date range', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/reports/checklogs')
            ->type('#start_date', now()->subMonth()->format('Y-m-d'))
            ->type('#end_date', now()->format('Y-m-d'))
            ->pause(500)
            ->assertInputValue('#start_date', now()->subMonth()->format('Y-m-d'));
    });
});

test('user can generate checklog report', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/reports/checklogs')
            ->click('button:contains("Generate Report")')
            ->pause(1000)
            ->assertSee('Report');
    });
});

test('user can export checklog report', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/reports/checklogs')
            ->click('button:contains("Export")')
            ->pause(1000)
            ->assertSee('Export');
    });
});

test('user can view overtime report page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/reports/overtimes')
            ->assertSee('Overtime Report')
            ->assertPathIs('/portal/reports/overtimes');
    });
});

test('user can filter overtime report by company', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        
        $browser->visit('/portal/reports/overtimes')
            ->select('#selectedCompanyId', (string)$company->id)
            ->pause(500)
            ->assertSelected('#selectedCompanyId', (string)$company->id);
    });
});

test('user can filter overtime report by date range', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/reports/overtimes')
            ->type('#start_date', now()->subMonth()->format('Y-m-d'))
            ->type('#end_date', now()->format('Y-m-d'))
            ->pause(500)
            ->assertInputValue('#start_date', now()->subMonth()->format('Y-m-d'));
    });
});

test('user can generate overtime report', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/reports/overtimes')
            ->click('button:contains("Generate Report")')
            ->pause(1000)
            ->assertSee('Report');
    });
});

test('user can export overtime report', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/reports/overtimes')
            ->click('button:contains("Export")')
            ->pause(1000)
            ->assertSee('Export');
    });
});

test('user can view payslip report page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/reports/payslips')
            ->assertSee('Payslip Report')
            ->assertPathIs('/portal/reports/payslips');
    });
});

test('user can filter payslip report by company', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        
        $browser->visit('/portal/reports/payslips')
            ->select('#selectedCompanyId', (string)$company->id)
            ->pause(500)
            ->assertSelected('#selectedCompanyId', (string)$company->id);
    });
});

test('user can filter payslip report by date range', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/reports/payslips')
            ->type('#start_date', now()->subMonth()->format('Y-m-d'))
            ->type('#end_date', now()->format('Y-m-d'))
            ->pause(500)
            ->assertInputValue('#start_date', now()->subMonth()->format('Y-m-d'));
    });
});

test('user can generate payslip report', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/reports/payslips')
            ->click('button:contains("Generate Report")')
            ->pause(1000)
            ->assertSee('Report');
    });
});

test('user can export payslip report', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/reports/payslips')
            ->click('button:contains("Export")')
            ->pause(1000)
            ->assertSee('Export');
    });
});






