<?php

use App\Models\User;
use App\Rules\UniqueEmailLocalPart;
use App\Support\EmployeeIdentityRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate([
        'name' => 'employee',
        'guard_name' => 'web',
    ]);
});

test('findConflictingEmailLocalPart detects same local part on different domain', function () {
    User::factory()->create(['email' => 'steevydjam@gmail.com']);

    $conflict = User::findConflictingEmailLocalPart('steevydjam@mail.com');

    expect($conflict)->not->toBeNull();
    expect($conflict->email)->toBe('steevydjam@gmail.com');
});

test('findConflictingEmailLocalPart ignores same full email on update', function () {
    $user = User::factory()->create(['email' => 'steevydjam@gmail.com']);

    expect(User::findConflictingEmailLocalPart('steevydjam@gmail.com', $user->id))->toBeNull();
});

test('findConflictingMatricule detects duplicate matricule with different email', function () {
    User::factory()->create([
        'email' => 'first@example.com',
        'matricule' => 'EMP-100',
    ]);

    $conflict = User::findConflictingMatricule('EMP-100', 'second@example.com');

    expect($conflict)->not->toBeNull();
    expect($conflict->email)->toBe('first@example.com');
});

test('employee identity email rule rejects duplicate local part', function () {
    User::factory()->create(['email' => 'john.doe@company.com']);

    $validator = Validator::make(
        ['email' => 'john.doe@personal.com'],
        ['email' => EmployeeIdentityRules::email()]
    );

    expect($validator->fails())->toBeTrue();
});

test('employee identity matricule rule rejects duplicate matricule', function () {
    User::factory()->create([
        'email' => 'first@example.com',
        'matricule' => 'EMP-200',
    ]);

    $validator = Validator::make(
        ['matricule' => 'emp-200'],
        ['matricule' => EmployeeIdentityRules::matricule()]
    );

    expect($validator->fails())->toBeTrue();
});

test('validateEmail rejects invalid local part characters', function () {
    $result = validateEmail('bad!user@example.com');

    expect($result['valid'])->toBeFalse();
});

test('unique email local part rule passes for distinct local parts', function () {
    User::factory()->create(['email' => 'alice@example.com']);

    $validator = Validator::make(
        ['email' => 'bob@example.com'],
        ['email' => [new UniqueEmailLocalPart()]]
    );

    expect($validator->passes())->toBeTrue();
});
