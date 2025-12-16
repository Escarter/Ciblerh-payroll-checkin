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

test('bounced email prevents future sends', function () {
    $user = User::factory()->create([
        'email' => 'bounced@example.com',
        'email_bounced' => true,
        'email_bounce_reason' => 'Mailbox does not exist',
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
    expect($payslip->email_bounced)->toBeTrue();
    
    Mail::assertNothingSent();
});


test('bounced email persists across multiple payslip attempts', function () {
    $user = User::factory()->create([
        'email' => 'bounced@example.com',
        'email_bounced' => true,
        'email_bounced_at' => now()->subDays(5),
    ]);
    
    $process1 = SendPayslipProcess::factory()->create(['month' => 'January']);
    $process2 = SendPayslipProcess::factory()->create(['month' => 'February']);
    
    $filePath1 = $process1->destination_directory . '/' . $user->matricule . '_January.pdf';
    $filePath2 = $process2->destination_directory . '/' . $user->matricule . '_February.pdf';
    
    Storage::disk('modified')->put($filePath1, 'content');
    Storage::disk('modified')->put($filePath2, 'content');
    
    $job1 = new SendPayslipJob(collect([$user]), $process1);
    $job1->handle();
    
    $job2 = new SendPayslipJob(collect([$user]), $process2);
    $job2->handle();
    
    $payslip1 = Payslip::where('employee_id', $user->id)
        ->where('month', 'January')
        ->first();
    
    $payslip2 = Payslip::where('employee_id', $user->id)
        ->where('month', 'February')
        ->first();
    
    expect($payslip1->email_bounced)->toBeTrue();
    expect($payslip2->email_bounced)->toBeTrue();
    
    Mail::assertNothingSent();
});

















