<?php

use App\Models\User;
use App\Models\Payslip;
use App\Models\Company;
use App\Models\Department;
use Laravel\Dusk\Browser;

beforeEach(function () {
    // Setup
});

test('user can view all payslips page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/payslips/history')
            ->assertSee('Payslips')
            ->assertPathIs('/portal/payslips/history');
    });
});

test('user can search all payslips', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $payslip = Payslip::factory()->create([
            'employee_id' => $employee->id,
            'matricule' => 'EMP001',
        ]);
        
        $browser->visit('/portal/payslips/history')
            ->type('#search', 'EMP001')
            ->pause(1000)
            ->assertSee('EMP001');
    });
});

test('user can filter by status', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/payslips/history')
            ->select('#statusFilter', 'successful')
            ->pause(500)
            ->assertSelected('#statusFilter', 'successful');
    });
});

test('user can filter by date range', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/payslips/history')
            ->type('#start_date', now()->subMonth()->format('Y-m-d'))
            ->type('#end_date', now()->format('Y-m-d'))
            ->pause(500)
            ->assertInputValue('#start_date', now()->subMonth()->format('Y-m-d'));
    });
});

test('user can switch between active and deleted tabs', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $payslip = Payslip::factory()->create(['employee_id' => $employee->id]);
        $payslip->delete();
        
        $browser->visit('/portal/payslips/history')
            ->click('button:contains("Deleted")')
            ->pause(500)
            ->assertSee('Deleted');
    });
});

test('user can change order by field', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/payslips/history')
            ->select('#orderBy', 'created_at')
            ->pause(500)
            ->assertSelected('#orderBy', 'created_at');
    });
});

test('user can change items per page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        Payslip::factory()->count(10)->create(['employee_id' => $employee->id]);
        
        $browser->visit('/portal/payslips/history')
            ->select('#perPage', '5')
            ->pause(500)
            ->assertSelected('#perPage', '5');
    });
});














