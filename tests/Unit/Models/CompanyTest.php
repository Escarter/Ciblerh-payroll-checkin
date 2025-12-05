<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('company has uuid', function () {
    $company = Company::factory()->create();
    
    expect($company->uuid)->not->toBeNull();
    expect(strlen($company->uuid))->toBeGreaterThan(0);
});

test('company has name', function () {
    $company = Company::factory()->create(['name' => 'Test Company']);
    
    expect($company->name)->toBe('Test Company');
});

test('company has code', function () {
    $company = Company::factory()->create(['code' => 'TST']);
    
    expect($company->code)->toBe('TST');
});

test('company has many departments', function () {
    $company = Company::factory()->create();
    Department::factory()->count(3)->create(['company_id' => $company->id]);
    
    expect($company->departments)->toHaveCount(3);
});

test('company has many employees', function () {
    $company = Company::factory()->create();
    User::factory()->count(5)->create(['company_id' => $company->id]);
    
    expect($company->employees)->toHaveCount(5);
});

test('company can be soft deleted', function () {
    $company = Company::factory()->create();
    $companyId = $company->id;
    
    $company->delete();
    
    expect(Company::find($companyId))->toBeNull();
    expect(Company::withTrashed()->find($companyId))->not->toBeNull();
});









