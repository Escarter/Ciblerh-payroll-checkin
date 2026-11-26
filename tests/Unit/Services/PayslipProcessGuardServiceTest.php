<?php

use App\Models\Department;
use App\Models\Payslip;
use App\Models\SendPayslipProcess;
use App\Models\User;
use App\Services\PayslipProcessGuardService;
use App\Services\PayslipProcessStartResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Spatie\Permission\Models\Role::findOrCreate('employee', 'web');
});

test('guard allows new process when no prior run exists for period', function () {
    $department = Department::factory()->create();

    $evaluation = app(PayslipProcessGuardService::class)->evaluateStart(
        $department->id,
        $department->company_id,
        'May',
        2026,
    );

    expect($evaluation->action)->toBe(PayslipProcessStartResult::ACTION_ALLOW_NEW);
});

test('guard blocks when process is already running for period', function () {
    $department = Department::factory()->create();
    $process = SendPayslipProcess::factory()->create([
        'department_id' => $department->id,
        'company_id' => $department->company_id,
        'month' => 'May',
        'year' => 2026,
        'status' => 'processing',
    ]);

    $evaluation = app(PayslipProcessGuardService::class)->evaluateStart(
        $department->id,
        $department->company_id,
        'May',
        2026,
    );

    expect($evaluation->action)->toBe(PayslipProcessStartResult::ACTION_BLOCK)
        ->and($evaluation->process->id)->toBe($process->id);
});

test('guard blocks duplicate when prior process completed successfully', function () {
    $department = Department::factory()->create();
    SendPayslipProcess::factory()->create([
        'department_id' => $department->id,
        'company_id' => $department->company_id,
        'month' => 'May',
        'year' => 2026,
        'status' => 'successful',
    ]);

    $evaluation = app(PayslipProcessGuardService::class)->evaluateStart(
        $department->id,
        $department->company_id,
        'May',
        2026,
    );

    expect($evaluation->action)->toBe(PayslipProcessStartResult::ACTION_BLOCK);
});

test('guard resumes when prior successful process still has incomplete payslips', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);
    $process = SendPayslipProcess::factory()->create([
        'department_id' => $department->id,
        'company_id' => $department->company_id,
        'month' => 'May',
        'year' => 2026,
        'status' => 'successful',
    ]);

    Payslip::factory()->create([
        'employee_id' => $user->id,
        'send_payslip_process_id' => $process->id,
        'month' => 'May',
        'year' => 2026,
        'encryption_status' => Payslip::STATUS_PENDING,
    ]);

    $evaluation = app(PayslipProcessGuardService::class)->evaluateStart(
        $department->id,
        $department->company_id,
        'May',
        2026,
    );

    expect($evaluation->action)->toBe(PayslipProcessStartResult::ACTION_RESUME_EXISTING)
        ->and($evaluation->messageKey)->toBe('payslips.period_process_resuming_incomplete');
});

test('guard resumes failed process instead of creating a duplicate', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create();
    $process = SendPayslipProcess::factory()->create([
        'department_id' => $department->id,
        'company_id' => $department->company_id,
        'month' => 'May',
        'year' => 2026,
        'status' => 'failed',
        'raw_file' => '/old/path.pdf',
        'destination_directory' => 'old_dir',
    ]);

    $evaluation = app(PayslipProcessGuardService::class)->evaluateStart(
        $department->id,
        $department->company_id,
        'May',
        2026,
    );

    expect($evaluation->action)->toBe(PayslipProcessStartResult::ACTION_RESUME_EXISTING);

    $resumed = app(PayslipProcessGuardService::class)->prepareForResume(
        $process,
        '/new/path.pdf',
        'new_dir',
        $user->id,
    );

    expect($resumed->id)->toBe($process->id)
        ->and($resumed->status)->toBe('processing')
        ->and($resumed->raw_file)->toBe('/new/path.pdf')
        ->and($resumed->destination_directory)->toBe('new_dir')
        ->and($resumed->user_id)->toBe($user->id);
});

test('guard accepts a uuid sftp proposal id when resuming', function () {
    $department = Department::factory()->create();
    $process = SendPayslipProcess::factory()->create([
        'department_id' => $department->id,
        'company_id' => $department->company_id,
        'month' => 'May',
        'year' => 2026,
        'status' => 'failed',
    ]);

    $proposalId = (string) Str::uuid();

    $resumed = app(PayslipProcessGuardService::class)->prepareForResume(
        $process,
        '/new/path.pdf',
        'new_dir',
        null,
        $proposalId,
    );

    expect($resumed->sftp_proposal_id)->toBe($proposalId);
});

test('guard uses latest process when multiple exist for same period', function () {
    $department = Department::factory()->create();
    SendPayslipProcess::factory()->create([
        'department_id' => $department->id,
        'company_id' => $department->company_id,
        'month' => 'May',
        'year' => 2026,
        'status' => 'successful',
    ]);
    $latest = SendPayslipProcess::factory()->create([
        'department_id' => $department->id,
        'company_id' => $department->company_id,
        'month' => 'May',
        'year' => 2026,
        'status' => 'processing',
    ]);

    $evaluation = app(PayslipProcessGuardService::class)->evaluateStart(
        $department->id,
        $department->company_id,
        'May',
        2026,
    );

    expect($evaluation->process->id)->toBe($latest->id);
});

test('guard resumes older incomplete process when latest appears completed', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);

    $older = SendPayslipProcess::factory()->create([
        'department_id' => $department->id,
        'company_id' => $department->company_id,
        'month' => 'May',
        'year' => 2026,
        'status' => 'successful',
    ]);

    Payslip::factory()->create([
        'employee_id' => $user->id,
        'send_payslip_process_id' => $older->id,
        'month' => 'May',
        'year' => 2026,
        'encryption_status' => Payslip::STATUS_FAILED,
    ]);

    SendPayslipProcess::factory()->create([
        'department_id' => $department->id,
        'company_id' => $department->company_id,
        'month' => 'May',
        'year' => 2026,
        'status' => 'successful',
    ]);

    $evaluation = app(PayslipProcessGuardService::class)->evaluateStart(
        $department->id,
        $department->company_id,
        'May',
        2026,
    );

    expect($evaluation->action)->toBe(PayslipProcessStartResult::ACTION_RESUME_EXISTING)
        ->and($evaluation->process->id)->toBe($older->id);
});
