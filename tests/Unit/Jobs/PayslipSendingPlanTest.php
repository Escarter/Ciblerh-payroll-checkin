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
    \Spatie\Permission\Models\Role::findOrCreate('employee', 'web');
});

test('reconcile unmatched employees creates failed payslip records', function () {
    $department = Department::factory()->create();
    $user1 = User::factory()->create([
        'department_id' => $department->id,
        'matricule' => 'EMP001',
    ]);
    $user2 = User::factory()->create([
        'department_id' => $department->id,
        'matricule' => 'EMP002',
    ]);
    
    $process = SendPayslipProcess::factory()->create([
        'department_id' => $department->id,
        'month' => 1,  // January
        'year' => 2024,
    ]);
    
    // Create payslip for user1 only (user2 is unmatched)
    Payslip::factory()->create([
        'employee_id' => $user1->id,
        'send_payslip_process_id' => $process->id,
        'month' => 1,  // January
        'year' => 2024,
    ]);
    
    // Use reflection to call private method
    $reflection = new \ReflectionClass(PayslipSendingPlan::class);
    $method = $reflection->getMethod('reconcileUnmatchedEmployees');
    $method->setAccessible(true);
    $method->invoke(null, $process);
    
    // Check that unmatched employee has a failed payslip record
    $unmatchedPayslip = Payslip::where('employee_id', $user2->id)
        ->where('send_payslip_process_id', $process->id)
        ->first();
    
    expect($unmatchedPayslip)->not->toBeNull();
    expect($unmatchedPayslip->encryption_status)->toBe(Payslip::STATUS_FAILED);
    expect($unmatchedPayslip->email_sent_status)->toBe(Payslip::STATUS_FAILED);
    expect($unmatchedPayslip->sms_sent_status)->toBe(Payslip::STATUS_FAILED);
    expect($unmatchedPayslip->failure_reason)->toContain('not found');
});

test('reconcile unmatched employees handles empty matricule', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create([
        'department_id' => $department->id,
        'matricule' => '',
    ]);
    
    $process = SendPayslipProcess::factory()->create([
        'department_id' => $department->id,
        'month' => 1,  // January
        'year' => 2024,
    ]);
    
    $reflection = new \ReflectionClass(PayslipSendingPlan::class);
    $method = $reflection->getMethod('reconcileUnmatchedEmployees');
    $method->setAccessible(true);
    $method->invoke(null, $process);
    
    $payslip = Payslip::where('employee_id', $user->id)
        ->where('send_payslip_process_id', $process->id)
        ->first();
    
    expect($payslip)->not->toBeNull();
    expect($payslip->failure_reason)->toContain('User matricule is empty');
});

test('reconcile unmatched employees updates process failure reason', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create([
        'department_id' => $department->id,
        'matricule' => 'EMP001',
    ]);
    
    $process = SendPayslipProcess::factory()->create([
        'department_id' => $department->id,
        'month' => 1,  // January
        'year' => 2024,
    ]);
    
    $reflection = new \ReflectionClass(PayslipSendingPlan::class);
    $method = $reflection->getMethod('reconcileUnmatchedEmployees');
    $method->setAccessible(true);
    $method->invoke(null, $process);
    
    $process->refresh();
    
    expect($process->failure_reason)->toContain('could not be matched');
});

test('reconcile relinks payslips with files from a previous process', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create([
        'department_id' => $department->id,
        'matricule' => 'EMP001',
    ]);

    $oldProcess = SendPayslipProcess::factory()->create([
        'department_id' => $department->id,
        'month' => 'December',
        'year' => 2025,
    ]);

    $newProcess = SendPayslipProcess::factory()->create([
        'department_id' => $department->id,
        'month' => 'December',
        'year' => 2025,
    ]);

    Payslip::factory()->create([
        'employee_id' => $user->id,
        'send_payslip_process_id' => $oldProcess->id,
        'month' => 'December',
        'year' => 2025,
        'file' => $oldProcess->destination_directory . '/EMP001_December.pdf',
        'encryption_status' => Payslip::STATUS_SUCCESSFUL,
        'matricule' => 'EMP001',
    ]);

    $reflection = new \ReflectionClass(PayslipSendingPlan::class);
    $method = $reflection->getMethod('reconcileUnmatchedEmployees');
    $method->setAccessible(true);
    $method->invoke(null, $newProcess);

    $payslips = Payslip::where('employee_id', $user->id)
        ->where('month', 'December')
        ->where('year', 2025)
        ->get();

    expect($payslips)->toHaveCount(1);
    expect($payslips->first()->send_payslip_process_id)->toBe($newProcess->id);
});

test('reconcile unmatched employees does not create duplicate records', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create([
        'department_id' => $department->id,
        'matricule' => 'EMP001',
    ]);
    
    $process = SendPayslipProcess::factory()->create([
        'department_id' => $department->id,
        'month' => 1,  // January
        'year' => 2024,
    ]);
    
    // Create existing payslip
    Payslip::factory()->create([
        'employee_id' => $user->id,
        'send_payslip_process_id' => $process->id,
        'month' => 1,  // January
        'year' => 2024,
    ]);
    
    $reflection = new \ReflectionClass(PayslipSendingPlan::class);
    $method = $reflection->getMethod('reconcileUnmatchedEmployees');
    $method->setAccessible(true);
    $method->invoke(null, $process);
    
    // Should not create duplicate
    $payslips = Payslip::where('employee_id', $user->id)
        ->where('send_payslip_process_id', $process->id)
        ->count();
    
    expect($payslips)->toBe(1);
});

test('reconcile unmatched employees handles department with no employees', function () {
    $department = Department::factory()->create();
    
    $process = SendPayslipProcess::factory()->create([
        'department_id' => $department->id,
        'month' => 1,  // January
        'year' => 2024,
    ]);
    
    $reflection = new \ReflectionClass(PayslipSendingPlan::class);
    $method = $reflection->getMethod('reconcileUnmatchedEmployees');
    $method->setAccessible(true);
    $method->invoke(null, $process);
    
    // Should not throw error and process should remain unchanged
    expect($process->fresh())->not->toBeNull();
});


















