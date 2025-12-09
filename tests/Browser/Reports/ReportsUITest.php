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

        $this->visitAndWait($browser, '/portal/reports/checklogs');
        $browser->assertSee('Employees Checkins Report')
            ->assertPathIs('/portal/reports/checklogs');
    });
});

test('user can filter checklog report by company', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();

        $this->visitAndWait($browser, '/portal/reports/checklogs');
        $browser->select('#selectedCompanyId', (string)$company->id)
            ->pause(1000)
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

test('user can filter checklog report by period', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/reports/checklogs')
            ->select('#period', now()->format('Y-m'))
            ->pause(500)
            ->assertSelected('#period', now()->format('Y-m'));
    });
});

test('user can generate checklog report', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');

        $this->visitAndWait($browser, '/portal/reports/checklogs');
        $browser->click('#generate-checklog-report-btn')
            ->pause(2000)
            ->assertSee('Report');
    });
});

test('user can view overtime report page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/reports/overtimes')
            ->assertSee('Employees Overtime Report')
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

test('user can filter overtime report by period', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/reports/overtimes')
            ->select('#period', now()->format('n'))
            ->pause(500)
            ->assertSelected('#period', now()->format('n'));
    });
});

test('user can generate overtime report', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/reports/overtimes')
            ->click('#generate-overtime-report-btn')
            ->pause(1000)
            ->assertSee('Report');
    });
});

test('user can view payslip report page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/reports/payslips')
            ->assertSee('Payslips Report')
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

test('user can generate payslip report', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/reports/payslips')
            ->click('#generate-payslip-report-btn')
            ->pause(1000)
            ->assertSee('Report');
    });
});









