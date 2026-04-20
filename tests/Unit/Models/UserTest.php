<?php

use App\Models\Setting;
use App\Models\User;
use App\Models\Payslip;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate([
        'name' => 'employee',
        'guard_name' => 'web',
    ]);
});

test('user has name attribute', function () {
    $user = User::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);
    
    expect($user->name)->toBe('John Doe');
});

test('user has initials attribute', function () {
    $user = User::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);
    
    expect($user->initials)->toBe('JD');
});

test('user matricule is uppercase', function () {
    $user = User::factory()->create(['matricule' => 'emp001']);
    
    expect($user->matricule)->toBe('EMP001');
});

test('user matricule trims whitespace on save', function () {
    $user = User::factory()->create(['matricule' => "  emp001\t "]);

    expect($user->matricule)->toBe('EMP001');
    $user->refresh();
    expect($user->getRawOriginal('matricule'))->toBe('EMP001');
});

test('matricule pdf token match ignores letter casing', function () {
    expect(User::matriculeTokenExistsInPdfText('Bulletin pour emp001 validé', 'EMP001'))->toBeTrue();
    expect(User::matriculeTokenExistsInPdfText('Bulletin pour EMP001 validé', 'emp001'))->toBeTrue();
});

test('user matricule normalizes unicode letters to uppercase', function () {
    $user = User::factory()->create(['matricule' => 'café-01']);

    expect($user->matricule)->toBe('CAFÉ-01');
});

test('user has payslips relationship', function () {
    $user = User::factory()->create();
    Payslip::factory()->count(3)->create(['employee_id' => $user->id]);
    
    expect($user->payslips)->toHaveCount(3);
    expect($user->payslips->first())->toBeInstanceOf(Payslip::class);
});

test('user email notifications default to true', function () {
    $user = User::factory()->create();
    
    expect($user->receive_email_notifications)->toBeTrue();
});

test('user email bounced defaults to false', function () {
    $user = User::factory()->create();
    
    expect($user->email_bounced)->toBeFalse();
});

test('user can have alternative email', function () {
    $user = User::factory()->create([
        'email' => 'primary@example.com',
        'alternative_email' => 'alternative@example.com',
    ]);
    
    expect($user->email)->toBe('primary@example.com');
    expect($user->alternative_email)->toBe('alternative@example.com');
});

test('user status text attribute', function () {
    $user = User::factory()->create(['status' => true]);
    expect($user->status_text)->toContain('Active');
    
    $user = User::factory()->create(['status' => false]);
    expect($user->status_text)->toContain('Banned');
});

test('user status style attribute', function () {
    $user = User::factory()->create(['status' => true]);
    expect($user->status_style)->toBe('success');
    
    $user = User::factory()->create(['status' => false]);
    expect($user->status_style)->toBe('danger');
});

test('user preferred locale returns preferred language', function () {
    $user = User::factory()->create(['preferred_language' => 'fr']);
    
    expect($user->preferredLocale())->toBe('fr');
});

test('user sends custom password reset notification', function () {
    Notification::fake();

    $user = User::factory()->create();

    $user->sendPasswordResetNotification('reset-token');

    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

test('password reset notification applies saved smtp credentials', function () {
    Config::set('mail.default', 'smtp');
    Config::set('mail.mailers.smtp.host', null);
    Config::set('mail.mailers.smtp.port', null);
    Config::set('mail.mailers.smtp.username', null);
    Config::set('mail.mailers.smtp.password', null);
    Config::set('mail.mailers.smtp.encryption', null);

    Setting::factory()->create([
        'smtp_host' => 'smtp.saved-settings.test',
        'smtp_port' => 2525,
        'smtp_username' => 'saved-user',
        'smtp_password' => 'saved-pass',
        'smtp_encryption' => 'ssl',
        'from_email' => 'support@ciblerh.test',
        'from_name' => 'CibleRH Support',
    ]);

    $user = User::factory()->create();

    $notification = new ResetPasswordNotification('reset-token');
    $mailMessage = $notification->toMail($user);

    expect(Config::get('mail.default'))->toBe('smtp')
        ->and(Config::get('mail.mailers.smtp.host'))->toBe('smtp.saved-settings.test')
        ->and(Config::get('mail.mailers.smtp.port'))->toBe(2525)
        ->and(Config::get('mail.mailers.smtp.username'))->toBe('saved-user')
        ->and(Config::get('mail.mailers.smtp.password'))->toBe('saved-pass')
        ->and(Config::get('mail.mailers.smtp.encryption'))->toBe('ssl')
        ->and(Config::get('mail.from.address'))->toBe('support@ciblerh.test')
        ->and(Config::get('mail.from.name'))->toBe('CibleRH Support')
        ->and($mailMessage)->toBeInstanceOf(\Illuminate\Notifications\Messages\MailMessage::class);
});

test('user create applies default work times when missing', function () {
    $user = User::create([
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.com',
        'matricule' => 'EMP-ADA',
        'professional_phone_number' => '+237600000001',
        'password' => bcrypt('secret123'),
        'company_id' => \App\Models\Company::factory()->create()->id,
        'department_id' => \App\Models\Department::factory()->create()->id,
        'service_id' => \App\Models\Service::factory()->create()->id,
    ]);

    expect($user->fresh()->work_start_time)->toBe('08:00:00')
        ->and($user->fresh()->work_end_time)->toBe('17:30:00');
});

test('user create replaces null work times with defaults', function () {
    $user = User::create([
        'first_name' => 'Grace',
        'last_name' => 'Hopper',
        'email' => 'grace@example.com',
        'matricule' => 'EMP-GRACE',
        'professional_phone_number' => '+237600000002',
        'work_start_time' => null,
        'work_end_time' => null,
        'password' => bcrypt('secret123'),
        'company_id' => \App\Models\Company::factory()->create()->id,
        'department_id' => \App\Models\Department::factory()->create()->id,
        'service_id' => \App\Models\Service::factory()->create()->id,
    ]);

    expect($user->fresh()->work_start_time)->toBe('08:00:00')
        ->and($user->fresh()->work_end_time)->toBe('17:30:00');
});


















