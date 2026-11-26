<?php

use App\Models\Company;
use App\Models\Payslip;
use App\Models\User;
use App\Services\PayslipEmployeeDiagnosticService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate([
        'name' => 'employee',
        'guard_name' => 'web',
    ]);
});

test('diagnose detects payslips on sibling duplicate accounts', function () {
    $company = Company::factory()->create();
    $primary = User::factory()->create(['company_id' => $company->id, 'matricule' => 'DUP-001']);
    $duplicate = User::factory()->create(['company_id' => $company->id, 'matricule' => 'DUP-001']);

    Payslip::factory()->create([
        'employee_id' => $primary->id,
        'company_id' => $company->id,
        'matricule' => 'DUP-001',
        'month' => 'January',
        'year' => 2026,
    ]);

    $report = app(PayslipEmployeeDiagnosticService::class)->diagnose($duplicate);

    expect($report['summary']['on_sibling_account_count'])->toBe(1);
    expect($report['can_relink'])->toBeTrue();
});

test('relink payslips moves sibling account payslips to selected employee', function () {
    $company = Company::factory()->create();
    $primary = User::factory()->create(['company_id' => $company->id, 'matricule' => 'DUP-002']);
    $duplicate = User::factory()->create(['company_id' => $company->id, 'matricule' => 'DUP-002']);

    $payslip = Payslip::factory()->create([
        'employee_id' => $primary->id,
        'company_id' => $company->id,
        'matricule' => 'DUP-002',
        'month' => 'February',
        'year' => 2026,
    ]);

    $result = app(PayslipEmployeeDiagnosticService::class)->relinkPayslipsToEmployee($duplicate);

    expect($result['relinked'])->toBe(1);
    expect($payslip->fresh()->employee_id)->toBe($duplicate->id);
});

test('restore deleted payslips restores records for employee matricule', function () {
    $company = Company::factory()->create();
    $employee = User::factory()->create(['company_id' => $company->id, 'matricule' => 'REST-001']);

    $payslip = Payslip::factory()->create([
        'employee_id' => $employee->id,
        'company_id' => $company->id,
        'matricule' => 'REST-001',
    ]);
    $payslip->delete();

    $restored = app(PayslipEmployeeDiagnosticService::class)->restoreDeletedPayslips($employee);

    expect($restored)->toBe(1);
    expect(Payslip::where('employee_id', $employee->id)->count())->toBe(1);
});

test('diagnose reports healthy when linked and visible counts align', function () {
    Storage::fake('modified');
    $company = Company::factory()->create();
    $employee = User::factory()->create(['company_id' => $company->id, 'matricule' => 'OK-001']);

    foreach (['January', 'February'] as $month) {
        $file = "payslips/ok-001-{$month}.pdf";
        Storage::disk('modified')->put($file, '%PDF-1.4 test');
        Payslip::factory()->create([
            'employee_id' => $employee->id,
            'company_id' => $company->id,
            'matricule' => 'OK-001',
            'month' => $month,
            'year' => 2026,
            'file' => $file,
            'encryption_status' => Payslip::STATUS_SUCCESSFUL,
        ]);
    }

    $report = app(PayslipEmployeeDiagnosticService::class)->diagnose($employee);

    expect($report['summary']['linked_count'])->toBe(2);
    expect($report['summary']['visible_count'])->toBe(2);
    expect(collect($report['issues'])->pluck('type'))->toContain('healthy');
});
