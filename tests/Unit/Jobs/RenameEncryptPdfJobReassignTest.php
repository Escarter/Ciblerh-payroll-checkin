<?php

use App\Jobs\RenameEncryptPdfJob;
use App\Models\Department;
use App\Models\Payslip;
use App\Models\SendPayslipProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Spatie\Permission\Models\Role::findOrCreate('employee', 'web');
});

test('process reassign attributes attach payslip row to current process', function () {
    $department = Department::factory()->create();
    $newProcess = SendPayslipProcess::factory()->create([
        'department_id' => $department->id,
        'month' => 'December',
        'year' => 2025,
    ]);

    $job = new RenameEncryptPdfJob(collect([]), $newProcess->id);

    $reflection = new ReflectionClass($job);
    $method = $reflection->getMethod('processReassignAttributes');
    $method->setAccessible(true);

    $attrs = $method->invoke($job, [
        'file' => 'dir/temp.pdf',
        'encryption_status' => Payslip::STATUS_PENDING,
    ]);

    expect($attrs['send_payslip_process_id'])->toBe($newProcess->id);
    expect($attrs['user_id'])->toBe($newProcess->user_id);
    expect($attrs['failure_reason'])->toBeNull();
    expect($attrs['file'])->toBe('dir/temp.pdf');
});

test('process reassign clears stale failure when reusing payslip row', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create([
        'department_id' => $department->id,
        'matricule' => 'EMP100',
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

    $payslip = Payslip::factory()->create([
        'employee_id' => $user->id,
        'send_payslip_process_id' => $oldProcess->id,
        'month' => 'December',
        'year' => 2025,
        'file' => null,
        'encryption_status' => Payslip::STATUS_FAILED,
        'failure_reason' => 'Matricule EMP100 not found in any PDF file for month December',
        'matricule' => 'EMP100',
    ]);

    $job = new RenameEncryptPdfJob(collect([]), $newProcess->id);
    $reflection = new ReflectionClass($job);
    $method = $reflection->getMethod('processReassignAttributes');
    $method->setAccessible(true);

    $payslip->update($method->invoke($job, [
        'file' => $newProcess->destination_directory . '/temp_unenc_EMP100_December.pdf',
        'encryption_status' => Payslip::STATUS_PENDING,
    ]));

    $payslip->refresh();

    expect($payslip->send_payslip_process_id)->toBe($newProcess->id);
    expect($payslip->failure_reason)->toBeNull();
    expect($payslip->encryption_status)->toBe(Payslip::STATUS_PENDING);
});
