<?php

namespace Tests\Unit\Imports;

use App\Imports\EmployeeImport;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeImportTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_custom_validation_attributes()
    {
        $company = Company::factory()->create();
        $import = new EmployeeImport($company);

        $attributes = $import->customValidationAttributes();

        $this->assertIsArray($attributes);
        $this->assertArrayHasKey('0', $attributes);
        $this->assertArrayHasKey('1', $attributes);
        $this->assertArrayHasKey('2', $attributes);

        // Verify that the attributes contain translated strings
        $this->assertEquals(__('employees.first_name'), $attributes['0']);
        $this->assertEquals(__('employees.last_name'), $attributes['1']);
        $this->assertEquals(__('employees.email'), $attributes['2']);
    }

    /** @test */
    public function it_has_validation_rules()
    {
        $company = Company::factory()->create();
        $import = new EmployeeImport($company);

        $rules = $import->rules();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('0', $rules);
        $this->assertArrayHasKey('1', $rules);
        $this->assertArrayHasKey('2', $rules);
    }

    /** @test */
    public function validation_errors_use_custom_attribute_names()
    {
        $company = Company::factory()->create();

        // Create a user with an email that we'll try to duplicate
        $existingUser = \App\Models\User::factory()->create([
            'email' => 'existing@example.com',
            'company_id' => $company->id
        ]);

        $import = new EmployeeImport($company);

        // Test data that should fail validation
        $invalidRow = [
            '', // empty first_name (required)
            '', // empty last_name (required)
            'existing@example.com', // duplicate email
            'invalid-phone', // invalid phone format
            '', // empty matricule (required)
            '', // empty position (required)
            'not-numeric', // invalid net_salary (should be numeric)
            '', // empty salary_grade (required)
        ];

        $validator = validator($invalidRow, $import->rules(), [], $import->customValidationAttributes());

        $this->assertTrue($validator->fails());

        $errors = $validator->errors();

        // Check that error messages use custom attribute names instead of numeric indices
        $errorMessages = $errors->all();

        // Look for messages that contain the translated field names
        $containsTranslatedNames = false;
        foreach ($errorMessages as $message) {
            if (strpos($message, __('employees.first_name')) !== false ||
                strpos($message, __('employees.last_name')) !== false ||
                strpos($message, __('employees.email')) !== false) {
                $containsTranslatedNames = true;
                break;
            }
        }

        $this->assertTrue($containsTranslatedNames, 'Error messages should contain translated field names');
    }
}