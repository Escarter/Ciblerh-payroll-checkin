<?php

use App\Jobs\Plan\PayslipSendingPlan;
use App\Models\SendPayslipProcess;
use App\Models\Department;
use App\Models\User;
use App\Models\Payslip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('splitted');
    Storage::fake('modified');
    Bus::fake();
});

test('payslip sending process creates payslip records for all employees', function () {
    $department = Department::factory()->create();
    $user1 = User::factory()->create(['department_id' => $department->id, 'matricule' => 'EMP001']);
    $user2 = User::factory()->create(['department_id' => $department->id, 'matricule' => 'EMP002']);
    
    $process = SendPayslipProcess::factory()->create([
        'department_id' => $department->id,
        'month' => 'January',
        'year' => 2024,
    ]);
    
    // Simulate that user1 has a payslip, user2 doesn't
    Payslip::factory()->create([
        'employee_id' => $user1->id,
        'send_payslip_process_id' => $process->id,
        'month' => 'January',
        'year' => 2024,
    ]);
    
    // Reconcile unmatched employees
    $reflection = new \ReflectionClass(PayslipSendingPlan::class);
    $method = $reflection->getMethod('reconcileUnmatchedEmployees');
    $method->setAccessible(true);
    $method->invoke(null, $process);
    
    // Both employees should have payslip records
    $payslips = Payslip::where('send_payslip_process_id', $process->id)->get();
    
    expect($payslips)->toHaveCount(2);
    
    $user2Payslip = $payslips->where('employee_id', $user2->id)->first();
    expect($user2Payslip)->not->toBeNull();
    expect($user2Payslip->encryption_status)->toBe(Payslip::STATUS_FAILED);
});

test('payslip sending process handles multiple unmatched employees', function () {
    $department = Department::factory()->create();
    $users = User::factory()->count(5)->create(['department_id' => $department->id]);
    
    $process = SendPayslipProcess::factory()->create([
        'department_id' => $department->id,
        'month' => 'January',
        'year' => 2024,
    ]);
    
    // Only create payslip for first user
    Payslip::factory()->create([
        'employee_id' => $users->first()->id,
        'send_payslip_process_id' => $process->id,
        'month' => 'January',
        'year' => 2024,
    ]);
    
    $reflection = new \ReflectionClass(PayslipSendingPlan::class);
    $method = $reflection->getMethod('reconcileUnmatchedEmployees');
    $method->setAccessible(true);
    $method->invoke(null, $process);
    
    $payslips = Payslip::where('send_payslip_process_id', $process->id)->get();
    
    // Should have 5 payslips (1 existing + 4 unmatched)
    expect($payslips)->toHaveCount(5);
    
    $unmatchedCount = $payslips->where('encryption_status', Payslip::STATUS_FAILED)->count();
    expect($unmatchedCount)->toBe(4);
});

test('payslip sending process updates failure reason with unmatched count', function () {
    $department = Department::factory()->create();
    $users = User::factory()->count(3)->create(['department_id' => $department->id]);
    
    $process = SendPayslipProcess::factory()->create([
        'department_id' => $department->id,
        'month' => 'January',
        'year' => 2024,
    ]);
    
    $reflection = new \ReflectionClass(PayslipSendingPlan::class);
    $method = $reflection->getMethod('reconcileUnmatchedEmployees');
    $method->setAccessible(true);
    $method->invoke(null, $process);
    
    $process->refresh();
    
    expect($process->failure_reason)->toContain('3');
    expect($process->failure_reason)->toContain('could not be matched');
});
















