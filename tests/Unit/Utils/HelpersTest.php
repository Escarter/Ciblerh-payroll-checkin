<?php

use App\Models\User;
use App\Models\Payslip;
use App\Models\SendPayslipProcess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('validateEmail returns valid for correct email', function () {
    $result = validateEmail('test@example.com');
    
    expect($result['valid'])->toBeTrue();
    expect($result['error'])->toBeNull();
});

test('validateEmail returns invalid for empty email', function () {
    $result = validateEmail('');
    
    expect($result['valid'])->toBeFalse();
    expect($result['error'])->toContain('cannot be empty');
});

test('validateEmail returns invalid for malformed email', function () {
    $result = validateEmail('not-an-email');
    
    expect($result['valid'])->toBeFalse();
    expect($result['error'])->toContain('Invalid email address format');
});

test('validateEmail returns invalid for email too long', function () {
    $longEmail = str_repeat('a', 300) . '@example.com';
    
    $result = validateEmail($longEmail);
    
    expect($result['valid'])->toBeFalse();
    expect($result['error'])->toContain('too long');
});

test('validateEmail returns invalid for email without TLD', function () {
    $result = validateEmail('test@example');
    
    expect($result['valid'])->toBeFalse();
    expect($result['error'])->toContain('top-level domain');
});

test('validateEmail returns invalid for consecutive dots', function () {
    $result = validateEmail('test..user@example.com');
    
    expect($result['valid'])->toBeFalse();
    expect($result['error'])->toContain('consecutive dots');
});

test('createPayslipRecord creates payslip with correct data', function () {
    $user = User::factory()->create();
    $process = SendPayslipProcess::factory()->create();
    $month = 'January';
    $file = 'payslips/test.pdf';
    
    $payslip = createPayslipRecord($user, $month, $process->id, $user->id, $file);
    
    expect($payslip)->toBeInstanceOf(Payslip::class);
    expect($payslip->employee_id)->toBe($user->id);
    expect($payslip->send_payslip_process_id)->toBe($process->id);
    expect($payslip->month)->toBe($month);
    expect($payslip->file)->toBe($file);
    expect($payslip->encryption_status)->toBe(Payslip::STATUS_SUCCESSFUL);
});

test('createPayslipRecord uses professional phone if available', function () {
    $user = User::factory()->create([
        'professional_phone_number' => '+1234567890',
        'personal_phone_number' => '+0987654321',
    ]);
    $process = SendPayslipProcess::factory()->create();
    
    $payslip = createPayslipRecord($user, 'January', $process->id, $user->id);
    
    expect($payslip->phone)->toBe('+1234567890');
});

test('createPayslipRecord uses personal phone if professional not available', function () {
    $user = User::factory()->create([
        'professional_phone_number' => null,
        'personal_phone_number' => '+0987654321',
    ]);
    $process = SendPayslipProcess::factory()->create();
    
    $payslip = createPayslipRecord($user, 'January', $process->id, $user->id);
    
    expect($payslip->phone)->toBe('+0987654321');
});








