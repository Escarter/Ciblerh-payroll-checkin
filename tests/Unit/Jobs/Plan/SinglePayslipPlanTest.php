<?php

use App\Jobs\Plan\SinglePayslipPlan;
use App\Jobs\Single\SplitPdfSingleEmployee;
use App\Jobs\Single\SinglePayslipProcessingJob;
use App\Models\Payslip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('splitted');
    Bus::fake();
});

test('it dispatches job chain for single payslip processing', function () {
    $user = User::factory()->create();
    $file = '/tmp/test.pdf';
    $month = 'January';
    $destination = 'test_dir';
    
    SinglePayslipPlan::start($user->id, $file, $month, $destination, $user->id);
    
    // Verify that SplitPdfSingleEmployee was dispatched
    Bus::assertDispatched(SplitPdfSingleEmployee::class);
});

test('it reconciles unmatched employee when matricule not found', function () {
    $user = User::factory()->create([
        'matricule' => 'EMP001',
    ]);
    
    $month = 'January';
    $destination = 'test_dir';
    
    // No payslip exists for this employee
    expect(Payslip::where('employee_id', $user->id)
        ->where('month', $month)
        ->exists())->toBeFalse();
    
    // Simulate reconciliation (normally called after batch completes)
    $reflection = new \ReflectionClass(SinglePayslipPlan::class);
    $method = $reflection->getMethod('reconcileSingleEmployee');
    $method->setAccessible(true);
    $method->invoke(null, $user->id, $month, $user->id);
    
    $payslip = Payslip::where('employee_id', $user->id)
        ->where('month', $month)
        ->first();
    
    expect($payslip)->not->toBeNull();
    expect($payslip->encryption_status)->toBe(Payslip::STATUS_FAILED);
    expect($payslip->failure_reason)->toContain('not found in any PDF file');
});

test('it creates failed payslip when employee matricule is empty', function () {
    $user = User::factory()->create([
        'matricule' => '',
    ]);
    
    $month = 'January';
    
    // Simulate reconciliation
    $reflection = new \ReflectionClass(SinglePayslipPlan::class);
    $method = $reflection->getMethod('reconcileSingleEmployee');
    $method->setAccessible(true);
    $method->invoke(null, $user->id, $month, $user->id);
    
    $payslip = Payslip::where('employee_id', $user->id)
        ->where('month', $month)
        ->first();
    
    expect($payslip)->not->toBeNull();
    expect($payslip->encryption_status)->toBe(Payslip::STATUS_FAILED);
    expect($payslip->failure_reason)->toContain('User matricule is empty');
});

test('it does not create duplicate payslip record if one already exists', function () {
    $user = User::factory()->create([
        'matricule' => 'EMP002',
    ]);
    
    $month = 'January';
    
    // Create existing payslip
    $existingPayslip = Payslip::create([
        'employee_id' => $user->id,
        'month' => $month,
        'year' => now()->year,
        'encryption_status' => Payslip::STATUS_SUCCESSFUL,
        'company_id' => $user->company_id,
        'department_id' => $user->department_id,
        'service_id' => $user->service_id,
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => $user->email,
        'phone' => $user->professional_phone_number ?? $user->personal_phone_number,
        'matricule' => $user->matricule,
    ]);
    
    $initialCount = Payslip::where('employee_id', $user->id)->count();
    
    // Simulate reconciliation
    $reflection = new \ReflectionClass(SinglePayslipPlan::class);
    $method = $reflection->getMethod('reconcileSingleEmployee');
    $method->setAccessible(true);
    $method->invoke(null, $user->id, $month, $user->id);
    
    // Count should not increase
    expect(Payslip::where('employee_id', $user->id)->count())->toBe($initialCount);
});

