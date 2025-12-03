<?php

use App\Jobs\Plan\ResendFailedPayslipPlan;
use App\Jobs\Single\SplitPdfSingleEmployee;
use App\Jobs\Single\ResendFailedPayslipJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('splitted');
    Bus::fake();
});

test('it dispatches job chain for resending failed payslip', function () {
    $employeeId = 1;
    $file = '/tmp/test.pdf';
    $record = 1; // payslip record ID
    $month = 'January';
    $destination = 'test_dir';
    $userId = 1;
    
    ResendFailedPayslipPlan::start($employeeId, $file, $record, $month, $destination, $userId);
    
    // Verify that SplitPdfSingleEmployee was dispatched
    Bus::assertDispatched(SplitPdfSingleEmployee::class);
});

test('it creates batch jobs for resending when files exist', function () {
    $user = \App\Models\User::factory()->create();
    $payslip = \App\Models\Payslip::factory()->create([
        'employee_id' => $user->id,
    ]);
    
    $employeeId = $user->id;
    $record = $payslip->id;
    $month = 'January';
    $destination = 'test_dir';
    $userId = $user->id;
    
    // Create some files in the destination
    Storage::disk('splitted')->put($destination . '/page_1.pdf', 'fake content');
    Storage::disk('splitted')->put($destination . '/page_2.pdf', 'fake content');
    
    // Simulate step2 (normally called after SplitPdfSingleEmployee)
    $reflection = new \ReflectionClass(ResendFailedPayslipPlan::class);
    $method = $reflection->getMethod('step2');
    $method->setAccessible(true);
    $method->invoke(null, $employeeId, $month, $record, $destination, $userId);
    
    // Batch should be dispatched
    Bus::assertBatched(function ($batch) {
        return $batch->name === 'Rename, Encrypt and resend Payslips for single user' &&
               count($batch->jobs) > 0;
    });
});

test('it does not create batch when no files exist', function () {
    $employeeId = 1;
    $record = 1;
    $month = 'January';
    $destination = 'empty_dir';
    $userId = 1;
    
    // No files in destination
    Storage::disk('splitted')->makeDirectory($destination);
    
    // Simulate step2
    $reflection = new \ReflectionClass(ResendFailedPayslipPlan::class);
    $method = $reflection->getMethod('step2');
    $method->setAccessible(true);
    $method->invoke(null, $employeeId, $month, $record, $destination, $userId);
    
    // No batch should be dispatched
    Bus::assertNothingBatched();
});

