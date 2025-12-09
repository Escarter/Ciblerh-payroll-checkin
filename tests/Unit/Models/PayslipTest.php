<?php

use App\Models\Payslip;
use App\Models\User;
use App\Models\Company;
use App\Models\Department;
use App\Models\SendPayslipProcess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('payslip has correct status constants', function () {
    expect(Payslip::STATUS_PENDING)->toBe(0);
    expect(Payslip::STATUS_SUCCESSFUL)->toBe(1);
    expect(Payslip::STATUS_FAILED)->toBe(2);
    expect(Payslip::STATUS_DISABLED)->toBe(3);
});

test('payslip belongs to employee', function () {
    $user = User::factory()->create();
    $payslip = Payslip::factory()->create(['employee_id' => $user->id]);
    
    expect($payslip->employee)->toBeInstanceOf(User::class);
    expect($payslip->employee->id)->toBe($user->id);
});

test('payslip belongs to send process', function () {
    $process = SendPayslipProcess::factory()->create();
    $payslip = Payslip::factory()->create(['send_payslip_process_id' => $process->id]);
    
    expect($payslip->sendProcess)->toBeInstanceOf(SendPayslipProcess::class);
    expect($payslip->sendProcess->id)->toBe($process->id);
});

test('payslip belongs to company', function () {
    $company = Company::factory()->create();
    $payslip = Payslip::factory()->create(['company_id' => $company->id]);
    
    expect($payslip->company)->toBeInstanceOf(Company::class);
    expect($payslip->company->id)->toBe($company->id);
});

test('payslip belongs to department', function () {
    $department = Department::factory()->create();
    $payslip = Payslip::factory()->create(['department_id' => $department->id]);
    
    expect($payslip->department)->toBeInstanceOf(Department::class);
    expect($payslip->department->id)->toBe($department->id);
});

test('payslip has name attribute', function () {
    $payslip = Payslip::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);
    
    expect($payslip->name)->toBe('John Doe');
});

test('payslip has initials attribute', function () {
    $payslip = Payslip::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);
    
    expect($payslip->initials)->toBe('JD');
});

test('payslip encryption status text attribute', function () {
    $payslip = Payslip::factory()->create(['encryption_status' => Payslip::STATUS_SUCCESSFUL]);
    expect($payslip->encryption_status_text)->toContain('Successful');
    
    $payslip = Payslip::factory()->create(['encryption_status' => Payslip::STATUS_FAILED]);
    expect($payslip->encryption_status_text)->toContain('Failed');
});

test('payslip email status text attribute', function () {
    $payslip = Payslip::factory()->create(['email_sent_status' => Payslip::STATUS_SUCCESSFUL]);
    expect($payslip->email_status_text)->toContain('Successful');
    
    $payslip = Payslip::factory()->create(['email_sent_status' => Payslip::STATUS_FAILED]);
    expect($payslip->email_status_text)->toContain('Failed');
    
    $payslip = Payslip::factory()->create(['email_sent_status' => Payslip::STATUS_PENDING]);
    expect($payslip->email_status_text)->toContain('Pending');
});

test('payslip sms status text attribute', function () {
    $payslip = Payslip::factory()->create(['sms_sent_status' => Payslip::STATUS_SUCCESSFUL]);
    expect($payslip->sms_status_text)->toContain('Successful');
    
    $payslip = Payslip::factory()->create(['sms_sent_status' => Payslip::STATUS_FAILED]);
    expect($payslip->sms_status_text)->toContain('Failed');
    
    $payslip = Payslip::factory()->create(['sms_sent_status' => Payslip::STATUS_DISABLED]);
    expect($payslip->sms_status_text)->toContain('Disabled');
});

test('payslip successful scope', function () {
    Payslip::factory()->successful()->create();
    Payslip::factory()->failed()->create();
    
    $successful = Payslip::successful()->get();
    
    expect($successful)->toHaveCount(1);
    expect($successful->first()->email_sent_status)->toBe(Payslip::STATUS_SUCCESSFUL);
    expect($successful->first()->sms_sent_status)->toBe(Payslip::STATUS_SUCCESSFUL);
});

test('payslip failed scope', function () {
    Payslip::factory()->successful()->create();
    Payslip::factory()->failed()->create();
    
    $failed = Payslip::failed()->get();
    
    expect($failed)->toHaveCount(1);
    expect($failed->first()->email_sent_status)->toBe(Payslip::STATUS_FAILED);
});

test('payslip search scope with query', function () {
    $payslip1 = Payslip::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'matricule' => 'EMP001',
    ]);
    
    $payslip2 = Payslip::factory()->create([
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'jane@example.com',
        'matricule' => 'EMP002',
    ]);
    
    // Mock auth user for search scope
    $user = User::factory()->create();
    $this->actingAs($user);
    
    $results = Payslip::search('John')->get();
    
    expect($results)->toHaveCount(1);
    expect($results->first()->first_name)->toBe('John');
});

test('payslip search scope without query', function () {
    Payslip::factory()->count(3)->create();
    
    $user = User::factory()->create();
    $this->actingAs($user);
    
    $results = Payslip::search('')->get();
    
    expect($results)->toHaveCount(3);
});













