<?php

use App\Models\Department;
use App\Models\Company;
use App\Models\User;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('department belongs to company', function () {
    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    
    expect($department->company)->toBeInstanceOf(Company::class);
    expect($department->company->id)->toBe($company->id);
});

test('department has many employees', function () {
    $department = Department::factory()->create();
    User::factory()->count(4)->create(['department_id' => $department->id]);
    
    expect($department->employees)->toHaveCount(4);
});

test('department has many services', function () {
    $department = Department::factory()->create();
    Service::factory()->count(3)->create(['department_id' => $department->id]);
    
    expect($department->services)->toHaveCount(3);
});

test('department has uuid', function () {
    $department = Department::factory()->create();
    
    expect($department->uuid)->not->toBeNull();
});

test('department has name', function () {
    $department = Department::factory()->create(['name' => 'IT Department']);
    
    expect($department->name)->toBe('IT Department');
});

test('department can be soft deleted', function () {
    $department = Department::factory()->create();
    $departmentId = $department->id;
    
    $department->delete();
    
    expect(Department::find($departmentId))->toBeNull();
    expect(Department::withTrashed()->find($departmentId))->not->toBeNull();
});


