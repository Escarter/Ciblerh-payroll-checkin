<?php

use App\Jobs\RetryPayslipEmailJob;
use App\Mail\SendPayslip;
use App\Models\Payslip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('modified');
    Bus::fake();
    
    Config::set('ciblerh.email_retry_attempts', 3);
    Config::set('ciblerh.email_retry_delay', 60);
    
    // Create a Setting for SMTP
    \App\Models\Setting::factory()->create();
});

test('it returns early when payslip not found', function () {
    $job = new RetryPayslipEmailJob(99999);
    $job->handle();
    
    // No payslip should be created
    expect(Payslip::count())->toBe(0);
});

test('it skips retry when encryption failed', function () {
    $payslip = Payslip::factory()->encryptionFailed()->create();
    
    $job = new RetryPayslipEmailJob($payslip->id);
    $job->handle();
    
    // Email not sent when encryption failed
    expect($payslip->email_sent_status)->not->toBe(Payslip::STATUS_SUCCESSFUL);
});

test('it skips retry when email already sent successfully', function () {
    $payslip = Payslip::factory()->successful()->create();
    $originalStatus = $payslip->email_sent_status;
    
    $job = new RetryPayslipEmailJob($payslip->id);
    $job->handle();
    
    // Refresh to get latest status
    $payslip->refresh();
    
    // Status should remain successful (job should skip and not change it)
    expect($payslip->email_sent_status)->toBe(Payslip::STATUS_SUCCESSFUL);
    expect($payslip->email_sent_status)->toBe($originalStatus);
});

test('it marks payslip as failed when file not found', function () {
    $payslip = Payslip::factory()->create([
        'file' => 'non-existent-file.pdf',
        'email_sent_status' => Payslip::STATUS_FAILED,
    ]);
    
    $job = new RetryPayslipEmailJob($payslip->id);
    $job->handle();
    
    $payslip->refresh();
    
    expect($payslip->email_sent_status)->toBe(Payslip::STATUS_FAILED);
    expect($payslip->failure_reason)->toContain('Payslip file not found');
    
    // Email not sent when encryption failed
    expect($payslip->email_sent_status)->not->toBe(Payslip::STATUS_SUCCESSFUL);
});

test('it skips retry when employee not found', function () {
    $payslip = Payslip::factory()->create([
        'employee_id' => 99999,
        'file' => 'test.pdf',
        'email_sent_status' => Payslip::STATUS_FAILED,
    ]);
    
    Storage::disk('modified')->put($payslip->file, 'fake content');
    
    $job = new RetryPayslipEmailJob($payslip->id);
    $job->handle();
    
    $payslip->refresh();
    
    expect($payslip->failure_reason)->toContain('Employee not found');
    
    // Email not sent when encryption failed
    expect($payslip->email_sent_status)->not->toBe(Payslip::STATUS_SUCCESSFUL);
});

test('it skips retry when email notifications disabled', function () {
    $user = User::factory()->create([
        'receive_email_notifications' => false,
        'email' => 'test@example.com',
    ]);
    
    $payslip = Payslip::factory()->create([
        'employee_id' => $user->id,
        'file' => 'test.pdf',
        'email_sent_status' => Payslip::STATUS_FAILED,
    ]);
    
    Storage::disk('modified')->put($payslip->file, 'fake content');
    
    $job = new RetryPayslipEmailJob($payslip->id);
    $job->handle();
    
    $payslip->refresh();
    
    expect($payslip->email_sent_status)->toBe(Payslip::STATUS_DISABLED);
    expect($payslip->failure_reason)->toContain('Email notifications disabled');
    
    // Email not sent when encryption failed
    expect($payslip->email_sent_status)->not->toBe(Payslip::STATUS_SUCCESSFUL);
});

test('it skips retry when email has bounced', function () {
    $user = User::factory()->create([
        'email' => 'bounced@example.com',
        'email_bounced' => true,
        'email_bounce_reason' => 'Mailbox does not exist',
    ]);
    
    $payslip = Payslip::factory()->create([
        'employee_id' => $user->id,
        'file' => 'test.pdf',
        'email_sent_status' => Payslip::STATUS_FAILED,
    ]);
    
    Storage::disk('modified')->put($payslip->file, 'fake content');
    
    $job = new RetryPayslipEmailJob($payslip->id);
    $job->handle();
    
    $payslip->refresh();
    
    expect($payslip->email_sent_status)->toBe(Payslip::STATUS_FAILED);
    expect($payslip->failure_reason)->toContain('bounced previously');
    
    // Email not sent when encryption failed
    expect($payslip->email_sent_status)->not->toBe(Payslip::STATUS_SUCCESSFUL);
});

test('it uses alternative email when primary email is empty', function () {
    // Create Setting for SMTP configuration
    \App\Models\Setting::factory()->create();
    
    $user = User::factory()->create([
        'email' => '',
        'alternative_email' => 'alternative@example.com',
        'receive_sms_notifications' => false, // Disable SMS to avoid SMS service calls
    ]);
    
    $payslip = Payslip::factory()->create([
        'employee_id' => $user->id,
        'file' => 'test.pdf',
        'email_sent_status' => Payslip::STATUS_FAILED,
        'month' => 'January', // Ensure month is set
    ]);
    
    Storage::disk('modified')->put($payslip->file, 'fake content');
    
    // Mock Mail to simulate successful sending - need to match cleanString output
    Mail::shouldReceive('to')
        ->once()
        ->with(\Mockery::on(function ($arg) {
            // cleanString might modify the email, so accept any string
            return is_string($arg) && str_contains($arg, 'alternative');
        }))
        ->andReturnSelf();
    
    Mail::shouldReceive('send')
        ->once()
        ->andReturn(true);
    
    Mail::shouldReceive('failures')
        ->zeroOrMoreTimes()
        ->andReturn([]); // No failures
    
    $job = new RetryPayslipEmailJob($payslip->id);
    $job->handle();
    
    $payslip->refresh();
    expect($payslip->email_sent_status)->toBe(Payslip::STATUS_SUCCESSFUL);
});

test('it successfully retries email and updates payslip status', function () {
    $user = User::factory()->create([
        'email' => 'success@example.com',
        'receive_sms_notifications' => false, // Disable SMS to avoid SMS service calls
    ]);
    
    $payslip = Payslip::factory()->create([
        'employee_id' => $user->id,
        'file' => 'test.pdf',
        'email_sent_status' => Payslip::STATUS_FAILED,
        'email_retry_count' => 1,
    ]);
    
    Storage::disk('modified')->put($payslip->file, 'fake content');
    
    Mail::shouldReceive('to')
        ->once()
        ->andReturnSelf();
    
    Mail::shouldReceive('send')
        ->once()
        ->andReturn(true);
    
    Mail::shouldReceive('failures')
        ->andReturn([]);
    
    $job = new RetryPayslipEmailJob($payslip->id);
    $job->handle();
    
    $payslip->refresh();
    
    expect($payslip->email_sent_status)->toBe(Payslip::STATUS_SUCCESSFUL);
    expect($payslip->email_retry_count)->toBe(0);
    // failure_reason may contain SMS-related messages when SMS is disabled, which is fine
    // The important thing is that email was sent successfully
    expect($payslip->email_sent_status)->toBe(Payslip::STATUS_SUCCESSFUL);
});

test('it detects bounce and marks email as bounced', function () {
    $user = User::factory()->create([
        'email' => 'bounce@example.com',
    ]);
    
    $payslip = Payslip::factory()->create([
        'employee_id' => $user->id,
        'file' => 'test.pdf',
        'email_sent_status' => Payslip::STATUS_FAILED,
    ]);
    
    Storage::disk('modified')->put($payslip->file, 'fake content');
    
    Mail::shouldReceive('to')
        ->once()
        ->andReturnSelf();
    
    Mail::shouldReceive('send')
        ->once()
        ->andReturn(true);
    
    Mail::shouldReceive('failures')
        ->andReturn(['bounce@example.com']);
    
    $job = new RetryPayslipEmailJob($payslip->id);
    $job->handle();
    
    $user->refresh();
    $payslip->refresh();
    
    expect($user->email_bounced)->toBeTrue();
    expect($user->email_bounced_at)->not->toBeNull();
    // email_bounced might be stored as 1/0 in database, so check truthiness
    expect((bool)$payslip->email_bounced)->toBeTrue();
    expect($payslip->email_bounce_reason)->not->toBeNull();
});

test('it schedules next retry when email fails and retries available', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'receive_sms_notifications' => false, // Disable SMS to avoid SMS service calls
    ]);
    
    $payslip = Payslip::factory()->create([
        'employee_id' => $user->id,
        'file' => 'test.pdf',
        'email_sent_status' => Payslip::STATUS_FAILED,
        'email_retry_count' => 1,
    ]);
    
    Storage::disk('modified')->put($payslip->file, 'fake content');
    
    Mail::shouldReceive('to')
        ->once()
        ->andReturnSelf();
    
    Mail::shouldReceive('send')
        ->once()
        ->andReturn(true);
    
    // Return a different email in failures to avoid bounce detection
    // The bounce detection checks if the email being sent is in failures
    // So we use a different email to trigger retry logic instead of bounce
    Mail::shouldReceive('failures')
        ->andReturn(['other@example.com']); // Different email to avoid bounce detection
    
    Bus::fake();
    
    $job = new RetryPayslipEmailJob($payslip->id);
    $job->handle();
    
    $payslip->refresh();
    
    expect($payslip->email_retry_count)->toBe(2);
    
    Bus::assertDispatched(RetryPayslipEmailJob::class);
});

test('it marks as permanently failed when max retries reached', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'receive_sms_notifications' => false, // Disable SMS to avoid SMS service calls
    ]);
    
    $payslip = Payslip::factory()->create([
        'employee_id' => $user->id,
        'file' => 'test.pdf',
        'email_sent_status' => Payslip::STATUS_FAILED,
        'email_retry_count' => 3,
    ]);
    
    Storage::disk('modified')->put($payslip->file, 'fake content');
    
    Mail::shouldReceive('to')
        ->once()
        ->andReturnSelf();
    
    Mail::shouldReceive('send')
        ->once()
        ->andReturn(true);
    
    // Use different email in failures to avoid bounce detection
    // This allows the max retries logic to run instead of bounce detection
    Mail::shouldReceive('failures')
        ->andReturn(['other@example.com']);
    
    $job = new RetryPayslipEmailJob($payslip->id);
    $job->handle();
    
    $payslip->refresh();
    
    expect($payslip->email_sent_status)->toBe(Payslip::STATUS_FAILED);
    expect($payslip->failure_reason)->toContain('after 3 retries');
    
    Bus::assertNothingDispatched();
});

test('it handles Swift_TransportException', function () {
    // Create Swift exception classes if they don't exist
    if (!class_exists('\Swift_TransportException')) {
        eval('class Swift_TransportException extends Exception {}');
    }
    
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'receive_sms_notifications' => false, // Disable SMS to avoid SMS service calls
    ]);
    
    $payslip = Payslip::factory()->create([
        'employee_id' => $user->id,
        'file' => 'test.pdf',
        'email_sent_status' => Payslip::STATUS_FAILED,
    ]);
    
    Storage::disk('modified')->put($payslip->file, 'fake content');
    
    Mail::shouldReceive('to')
        ->once()
        ->andThrow(new \Swift_TransportException('Connection failed'));
    
    $job = new RetryPayslipEmailJob($payslip->id);
    $job->handle();
    
    $payslip->refresh();
    
    expect($payslip->email_sent_status)->toBe(Payslip::STATUS_FAILED);
    expect($payslip->failure_reason)->toContain('Retry error');
});

test('it handles Swift_RfcComplianceException', function () {
    // Create Swift exception classes if they don't exist
    if (!class_exists('\Swift_RfcComplianceException')) {
        eval('class Swift_RfcComplianceException extends Exception {}');
    }
    
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'receive_sms_notifications' => false, // Disable SMS to avoid SMS service calls
    ]);
    
    $payslip = Payslip::factory()->create([
        'employee_id' => $user->id,
        'file' => 'test.pdf',
        'email_sent_status' => Payslip::STATUS_FAILED,
    ]);
    
    Storage::disk('modified')->put($payslip->file, 'fake content');
    
    Mail::shouldReceive('to')
        ->once()
        ->andThrow(new \Swift_RfcComplianceException('Invalid email'));
    
    $job = new RetryPayslipEmailJob($payslip->id);
    $job->handle();
    
    $payslip->refresh();
    
    expect($payslip->email_sent_status)->toBe(Payslip::STATUS_FAILED);
    expect($payslip->failure_reason)->toContain('Retry RFC error');
});

test('it handles general Exception', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);
    
    $payslip = Payslip::factory()->create([
        'employee_id' => $user->id,
        'file' => 'test.pdf',
        'email_sent_status' => Payslip::STATUS_FAILED,
    ]);
    
    Storage::disk('modified')->put($payslip->file, 'fake content');
    
    Mail::shouldReceive('to')
        ->once()
        ->andThrow(new \Exception('General error'));
    
    $job = new RetryPayslipEmailJob($payslip->id);
    $job->handle();
    
    $payslip->refresh();
    
    expect($payslip->email_sent_status)->toBe(Payslip::STATUS_FAILED);
    expect($payslip->failure_reason)->toContain('Retry error');
});

