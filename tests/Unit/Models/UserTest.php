<?php

use App\Models\User;
use App\Models\Payslip;
use App\Models\Company;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

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















