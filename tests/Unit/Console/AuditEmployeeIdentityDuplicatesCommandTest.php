<?php

use App\Models\Company;
use App\Models\Payslip;
use App\Models\User;
use App\Services\EmployeeIdentityDuplicateReportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate([
        'name' => 'employee',
        'guard_name' => 'web',
    ]);
});

function withoutMatriculeUniqueIndex(callable $callback): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropUnique(['matricule']);
    });

    $callback();
}

test('audit command reports clean state when no duplicates exist', function () {
    User::factory()->create([
        'email' => 'alice@company.com',
        'matricule' => 'MAT-001',
    ]);

    $this->artisan('employees:audit-identity-duplicates')
        ->expectsOutputToContain('No duplicate matricules found')
        ->assertExitCode(0);
});

test('audit command fails when duplicate matricules exist', function () {
    withoutMatriculeUniqueIndex(function () {
        $company = Company::factory()->create();

        User::factory()->create([
            'company_id' => $company->id,
            'email' => 'first@example.com',
            'matricule' => 'DUP-100',
        ]);

        User::factory()->create([
            'company_id' => $company->id,
            'email' => 'second@example.com',
            'matricule' => 'DUP-100',
        ]);

        $this->artisan('employees:audit-identity-duplicates')
            ->expectsOutputToContain('Duplicate matricules')
            ->expectsOutputToContain('DUP-100')
            ->assertExitCode(1);
    });
});

test('audit command reports duplicate email local parts', function () {
    User::factory()->create(['email' => 'john@gmail.com', 'matricule' => 'MAT-A']);
    User::factory()->create(['email' => 'john@mail.com', 'matricule' => 'MAT-B']);

    $this->artisan('employees:audit-identity-duplicates')
        ->expectsOutputToContain('Duplicate email local parts')
        ->expectsOutputToContain('john')
        ->assertExitCode(0);
});

test('duplicate report service includes payslip counts', function () {
    withoutMatriculeUniqueIndex(function () {
        $company = Company::factory()->create();
        $primary = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'owner@example.com',
            'matricule' => 'DUP-200',
        ]);
        User::factory()->create([
            'company_id' => $company->id,
            'email' => 'duplicate@example.com',
            'matricule' => 'DUP-200',
        ]);

        Payslip::factory()->count(2)->create(['employee_id' => $primary->id]);

        $report = app(EmployeeIdentityDuplicateReportService::class)->report();

        expect($report['migration_ready'])->toBeFalse();
        expect($report['duplicate_matricules'][0]['users'][0]['active_payslips'])->toBe(2);
    });
});

test('audit command supports json output', function () {
    User::factory()->create([
        'email' => 'solo@example.com',
        'matricule' => 'SOLO-1',
    ]);

    $this->artisan('employees:audit-identity-duplicates', ['--json' => true])
        ->expectsOutputToContain('"migration_ready": true')
        ->assertExitCode(0);
});
