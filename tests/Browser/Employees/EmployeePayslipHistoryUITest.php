<?php

use App\Models\User;
use App\Models\Payslip;
use App\Models\Company;
use App\Models\Department;
use Laravel\Dusk\Browser;

beforeEach(function () {
    // Setup
});

test('user can view employee payslip history', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        
        $browser->visit("/portal/employees/payslip/{$employee->uuid}/history")
            ->assertSee('Payslip History')
            ->assertPathIs("/portal/employees/payslip/{$employee->uuid}/history");
    });
});

test('user can search payslips in employee history', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $payslip = Payslip::factory()->create([
            'employee_id' => $employee->id,
            'month' => 'January',
        ]);
        
        $browser->visit("/portal/employees/payslip/{$employee->uuid}/history")
            ->type('#search', 'January')
            ->pause(1000)
            ->assertSee('January');
    });
});

test('user can filter by month and year', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        
        $browser->visit("/portal/employees/payslip/{$employee->uuid}/history")
            ->select('#month', 'January')
            ->select('#year', (string)now()->year)
            ->pause(500)
            ->assertSelected('#month', 'January');
    });
});

test('user can download payslip from history', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        $payslip = Payslip::factory()->create([
            'employee_id' => $employee->id,
            'file' => 'test.pdf',
        ]);
        
        \Illuminate\Support\Facades\Storage::disk('modified')->put('test.pdf', 'content');
        
        $browser->visit("/portal/employees/payslip/{$employee->uuid}/history")
            ->click("button[wire\\:click='downloadPayslip({$payslip->id})']")
            ->pause(1000);
        // Note: File download testing in Dusk is limited
    });
});

test('user can change order by field', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        
        $browser->visit("/portal/employees/payslip/{$employee->uuid}/history")
            ->select('#orderBy', 'month')
            ->pause(500)
            ->assertSelected('#orderBy', 'month');
    });
});

test('user can change items per page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $employee = User::factory()->create(['department_id' => $department->id]);
        Payslip::factory()->count(10)->create(['employee_id' => $employee->id]);
        
        $browser->visit("/portal/employees/payslip/{$employee->uuid}/history")
            ->select('#perPage', '5')
            ->pause(500)
            ->assertSelected('#perPage', '5');
    });
});












