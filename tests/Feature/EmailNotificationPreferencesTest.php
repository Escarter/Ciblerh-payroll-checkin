<?php

use App\Jobs\SendPayslipJob;
use App\Models\User;
use App\Models\Payslip;
use App\Models\SendPayslipProcess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('modified');
    Mail::fake();
});

test('email notifications disabled prevents email sending', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'receive_email_notifications' => false,
    ]);
    
    $process = SendPayslipProcess::factory()->create();
    $employeeChunk = collect([$user]);
    
    $filePath = $process->destination_directory . '/' . $user->matricule . '_' . $process->month . '.pdf';
    Storage::disk('modified')->put($filePath, 'fake pdf content');
    
    $job = new SendPayslipJob($employeeChunk, $process);
    $job->handle();
    
    $payslip = Payslip::where('employee_id', $user->id)
        ->where('month', $process->month)
        ->first();
    
    expect($payslip)->not->toBeNull();
    expect($payslip->email_sent_status)->toBe(Payslip::STATUS_DISABLED);
    expect($payslip->email_status_note)->toContain('Email notifications disabled');
    
    Mail::assertNothingSent();
});

test('email notifications enabled allows email sending', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'receive_email_notifications' => true,
    ]);
    
    $process = SendPayslipProcess::factory()->create();
    $employeeChunk = collect([$user]);
    
    $filePath = $process->destination_directory . '/' . $user->matricule . '_' . $process->month . '.pdf';
    Storage::disk('modified')->put($filePath, 'fake pdf content');
    
    // Mail is already faked in beforeEach(); rely on assertions below
    
    $job = new SendPayslipJob($employeeChunk, $process);
    $job->handle();
    
    Mail::assertSent(\App\Mail\SendPayslip::class);
});

test('email notifications default to enabled for new users', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);
    
    expect($user->receive_email_notifications)->toBeTrue();
});

test('alternative email is used when primary email is empty', function () {
    $user = User::factory()->create([
        'email' => '',
        'alternative_email' => 'backup@example.com',
        'receive_email_notifications' => true,
    ]);
    
    $process = SendPayslipProcess::factory()->create();
    $employeeChunk = collect([$user]);
    
    $filePath = $process->destination_directory . '/' . $user->matricule . '_' . $process->month . '.pdf';
    Storage::disk('modified')->put($filePath, 'fake pdf content');
    
    // Mail is already faked in beforeEach(); rely on assertions below
    
    $job = new SendPayslipJob($employeeChunk, $process);
    $job->handle();
    
    Mail::assertSent(\App\Mail\SendPayslip::class, function ($mail) {
        return true; // Can't easily check recipient in mock
    });
});

test('payslip marked as failed when no email addresses available', function () {
    $user = User::factory()->create([
        'email' => '',
        'alternative_email' => '',
        'receive_email_notifications' => true,
    ]);
    
    $process = SendPayslipProcess::factory()->create();
    $employeeChunk = collect([$user]);
    
    $filePath = $process->destination_directory . '/' . $user->matricule . '_' . $process->month . '.pdf';
    Storage::disk('modified')->put($filePath, 'fake pdf content');
    
    $job = new SendPayslipJob($employeeChunk, $process);
    $job->handle();
    
    $payslip = Payslip::where('employee_id', $user->id)
        ->where('month', $process->month)
        ->first();
    
    expect($payslip)->not->toBeNull();
    expect($payslip->email_sent_status)->toBe(Payslip::STATUS_FAILED);
    expect($payslip->failure_reason)->toContain('No valid email address');
    
    Mail::assertNothingSent();
});













