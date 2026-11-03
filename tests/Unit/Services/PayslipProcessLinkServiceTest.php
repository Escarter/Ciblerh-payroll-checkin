<?php

use App\Models\Department;
use App\Models\Payslip;
use App\Models\SendPayslipProcess;
use App\Models\User;
use App\Services\PayslipProcessLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Spatie\Permission\Models\Role::findOrCreate('employee', 'web');
});

test('repair process relinks file rows and removes duplicate failed rows', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create([
        'department_id' => $department->id,
        'matricule' => '0111002',
    ]);

    $oldProcess = SendPayslipProcess::factory()->create([
        'department_id' => $department->id,
        'month' => 'May',
        'year' => 2026,
    ]);

    $newProcess = SendPayslipProcess::factory()->create([
        'department_id' => $department->id,
        'month' => 'May',
        'year' => 2026,
    ]);

    Payslip::factory()->create([
        'employee_id' => $user->id,
        'send_payslip_process_id' => $oldProcess->id,
        'month' => 'May',
        'year' => 2026,
        'file' => 'dir/temp_unenc_0111002_May.pdf',
        'encryption_status' => Payslip::STATUS_PENDING,
        'matricule' => '0111002',
    ]);

    Payslip::factory()->create([
        'employee_id' => $user->id,
        'send_payslip_process_id' => $newProcess->id,
        'month' => 'May',
        'year' => 2026,
        'file' => null,
        'encryption_status' => Payslip::STATUS_FAILED,
        'failure_reason' => 'Matricule 0111002 not found in any PDF file for month May',
        'matricule' => '0111002',
    ]);

    $service = app(PayslipProcessLinkService::class);
    $result = $service->repairProcess($newProcess);

    expect($result['relinked'])->toBe(1);
    expect($result['removed_duplicates'])->toBe(1);

    expect(Payslip::where('send_payslip_process_id', $newProcess->id)->count())->toBe(1);
    expect(Payslip::where('send_payslip_process_id', $newProcess->id)->first()->file)->not->toBeNull();
});
