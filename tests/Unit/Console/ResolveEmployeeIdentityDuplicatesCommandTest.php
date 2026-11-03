<?php

use App\Models\Company;
use App\Models\Payslip;
use App\Models\User;
use App\Services\EmployeeIdentityDuplicateReportService;
use App\Services\EmployeeIdentityDuplicateResolutionService;
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

function dropMatriculeUniqueForTest(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropUnique(['matricule']);
    });
}

test('pickCanonicalUser prefers account with more payslips', function () {
    $company = Company::factory()->create();
    $rich = User::factory()->create(['company_id' => $company->id, 'matricule' => 'CAN-1']);
    $poor = User::factory()->create(['company_id' => $company->id, 'matricule' => 'CAN-2']);

    Payslip::factory()->count(3)->create(['employee_id' => $rich->id]);
    Payslip::factory()->create(['employee_id' => $poor->id]);

    $canonical = app(EmployeeIdentityDuplicateResolutionService::class)
        ->pickCanonicalUser(collect([$poor, $rich]));

    expect($canonical->id)->toBe($rich->id);
});

test('resolve merges duplicate matricules and renames losers', function () {
    dropMatriculeUniqueForTest();

    $company = Company::factory()->create();

    $canonical = User::factory()->create([
        'company_id' => $company->id,
        'email' => 'keep@example.com',
        'matricule' => 'MERGE-1',
        'status' => User::STATUS_ACTIVE,
    ]);

    $duplicate = User::factory()->create([
        'company_id' => $company->id,
        'email' => 'drop@example.com',
        'matricule' => 'MERGE-1',
        'status' => User::STATUS_ACTIVE,
    ]);

    Payslip::factory()->count(2)->create([
        'employee_id' => $canonical->id,
        'company_id' => $company->id,
        'matricule' => 'MERGE-1',
    ]);

    Payslip::factory()->create([
        'employee_id' => $duplicate->id,
        'company_id' => $company->id,
        'matricule' => 'MERGE-1',
    ]);

    $result = app(EmployeeIdentityDuplicateResolutionService::class)->resolve();

    expect($result['groups_processed'])->toBe(1);
    expect($result['payslips_relinked'])->toBe(1);
    expect(Payslip::where('employee_id', $canonical->id)->count())->toBe(3);
    expect($duplicate->fresh()->matricule)->toBe('MERGE-1_DUP_' . $duplicate->id);
    expect((int) $duplicate->fresh()->status)->toBe(User::STATUS_BANNED);
    expect(app(EmployeeIdentityDuplicateReportService::class)->report()['migration_ready'])->toBeTrue();
});

test('resolve command supports dry run', function () {
    dropMatriculeUniqueForTest();

    $company = Company::factory()->create();
    User::factory()->create(['company_id' => $company->id, 'email' => 'a@example.com', 'matricule' => 'DRY-1']);
    User::factory()->create(['company_id' => $company->id, 'email' => 'b@example.com', 'matricule' => 'DRY-1']);

    $this->artisan('employees:resolve-identity-duplicates', ['--dry-run' => true])
        ->expectsOutputToContain('Dry run')
        ->assertExitCode(0);

    expect(app(EmployeeIdentityDuplicateReportService::class)->report()['migration_ready'])->toBeFalse();
});
