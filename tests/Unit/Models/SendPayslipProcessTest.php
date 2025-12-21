<?php

use App\Models\SendPayslipProcess;
use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use App\Models\Payslip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('send payslip process belongs to company', function () {
    $company = Company::factory()->create();
    $process = SendPayslipProcess::factory()->create(['company_id' => $company->id]);
    
    expect($process->company)->toBeInstanceOf(Company::class);
    expect($process->company->id)->toBe($company->id);
});

test('send payslip process belongs to department', function () {
    $department = Department::factory()->create();
    $process = SendPayslipProcess::factory()->create(['department_id' => $department->id]);
    
    expect($process->department)->toBeInstanceOf(Department::class);
    expect($process->department->id)->toBe($department->id);
});

test('send payslip process belongs to owner user', function () {
    $user = User::factory()->create();
    $process = SendPayslipProcess::factory()->create(['user_id' => $user->id]);
    
    expect($process->owner)->toBeInstanceOf(User::class);
    expect($process->owner->id)->toBe($user->id);
});

test('send payslip process has many payslips', function () {
    $process = SendPayslipProcess::factory()->create();
    Payslip::factory()->count(3)->create(['send_payslip_process_id' => $process->id]);
    
    expect($process->payslips)->toHaveCount(3);
    expect($process->payslips->first())->toBeInstanceOf(Payslip::class);
});

test('send payslip process can be soft deleted', function () {
    $process = SendPayslipProcess::factory()->create();
    $processId = $process->id;
    
    $process->delete();
    
    expect(SendPayslipProcess::find($processId))->toBeNull();
    expect(SendPayslipProcess::withTrashed()->find($processId))->not->toBeNull();
});

test('send payslip process has status field', function () {
    $process = SendPayslipProcess::factory()->create(['status' => 'successful']);
    
    expect($process->status)->toBe('successful');
});

test('send payslip process has percentage completion field', function () {
    $process = SendPayslipProcess::factory()->create(['percentage_completion' => 75]);
    
    expect($process->percentage_completion)->toBe(75);
});

test('send payslip process has destination directory', function () {
    $process = SendPayslipProcess::factory()->create([
        'destination_directory' => 'payslips/2024/january',
    ]);
    
    expect($process->destination_directory)->toBe('payslips/2024/january');
});

test('send payslip process has month and year', function () {
    $process = SendPayslipProcess::factory()->create([
        'month' => 'January',
        'year' => 2024,
    ]);
    
    expect($process->month)->toBe('January');
    expect($process->year)->toBe(2024);
});


















