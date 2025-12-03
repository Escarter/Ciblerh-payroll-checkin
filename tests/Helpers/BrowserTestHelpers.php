<?php

namespace Tests\Helpers;

use App\Models\User;
use App\Models\Payslip;
use App\Models\SendPayslipProcess;
use Illuminate\Support\Facades\Storage;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Role;

trait BrowserTestHelpers
{
    /**
     * Create and login as a user with a specific role
     */
    protected function loginAs(Browser $browser, string $role = 'admin', array $attributes = []): User
    {
        // Ensure role exists
        $this->ensureRoleExists($role);
        
        $user = User::factory()->create(array_merge([
            'email' => "{$role}@example.com",
            'password' => bcrypt('password'),
            'status' => User::STATUS_ACTIVE, // Ensure user is active
            'email_verified_at' => now(), // Ensure email is verified
        ], $attributes));

        // Assign role if Spatie permissions is being used
        if (method_exists($user, 'assignRole')) {
            $user->assignRole($role);
        }

        // Use Laravel's loginAs method for browser tests (more reliable)
        $browser->loginAs($user);

        return $user;
    }

    /**
     * Ensure a role exists, create it if it doesn't
     */
    protected function ensureRoleExists(string $roleName): void
    {
        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
        } elseif (class_exists(\App\Models\Role::class)) {
            \App\Models\Role::firstOrCreate([
                'name' => $roleName,
            ]);
        }
    }

    /**
     * Create a payslip process with payslips
     */
    protected function createPayslipProcessWithPayslips(int $payslipCount = 3, array $payslipAttributes = []): SendPayslipProcess
    {
        $process = SendPayslipProcess::factory()->create();
        
        Payslip::factory()->count($payslipCount)->create(
            array_merge([
                'send_payslip_process_id' => $process->id,
            ], $payslipAttributes)
        );

        return $process;
    }

    /**
     * Create payslip files in storage
     */
    protected function createPayslipFiles(array $payslips): void
    {
        foreach ($payslips as $payslip) {
            if ($payslip->file) {
                Storage::disk('modified')->put($payslip->file, 'fake pdf content');
            }
        }
    }

    /**
     * Wait for Livewire to finish loading
     */
    protected function waitForLivewire(Browser $browser, int $timeout = 5): void
    {
        // Wait for Livewire to be ready
        try {
            $browser->waitFor('body', $timeout);
            // Wait a bit for Livewire to initialize
            $browser->pause(1000);
        } catch (\Exception $e) {
            // If body doesn't exist, page isn't loaded yet
            $browser->pause(2000);
        }
    }

    /**
     * Visit a page and wait for it to load with Livewire
     */
    protected function visitAndWait(Browser $browser, string $url, int $waitTime = 3000): void
    {
        $browser->visit($url)
            ->pause($waitTime) // Wait for Livewire to load
            ->assertPathIs($url);
    }

    /**
     * Wait for element to be present, with fallback
     */
    protected function waitForElement(Browser $browser, string $selector, int $timeout = 5, bool $required = false): bool
    {
        try {
            $browser->waitFor($selector, $timeout);
            return true;
        } catch (\Exception $e) {
            if ($required) {
                throw $e;
            }
            return false;
        }
    }

    /**
     * Assert flash message appears
     */
    protected function assertFlashMessage(Browser $browser, string $message = null): void
    {
        if ($message) {
            $browser->assertSee($message);
        } else {
            // Just check that alert element exists
            $browser->assertPresent('.alert');
        }
    }

    /**
     * Create a company with departments
     */
    protected function createCompanyWithDepartments(int $departmentCount = 2): \App\Models\Company
    {
        $company = \App\Models\Company::factory()->create();
        \App\Models\Department::factory()->count($departmentCount)->create([
            'company_id' => $company->id,
        ]);
        return $company;
    }

    /**
     * Create a department with employees
     */
    protected function createDepartmentWithEmployees(int $employeeCount = 3): \App\Models\Department
    {
        $company = \App\Models\Company::factory()->create();
        $department = \App\Models\Department::factory()->create([
            'company_id' => $company->id,
        ]);
        \App\Models\User::factory()->count($employeeCount)->create([
            'department_id' => $department->id,
        ]);
        return $department;
    }

    /**
     * Wait for modal to appear
     */
    protected function waitForModal(Browser $browser, string $modalId, int $timeout = 5): void
    {
        $browser->waitFor("#{$modalId}", $timeout);
    }

    /**
     * Fill and submit a form in a modal
     */
    protected function fillAndSubmitModal(Browser $browser, string $modalId, array $fields, string $submitButtonText = 'Save'): void
    {
        $browser->waitFor("#{$modalId}", 5)
            ->within("#{$modalId}", function ($modal) use ($fields, $submitButtonText) {
                foreach ($fields as $field => $value) {
                    if (str_contains($field, 'select') || str_contains($field, 'dropdown')) {
                        $modal->select($field, $value);
                    } else {
                        $modal->type($field, $value);
                    }
                }
                $modal->press($submitButtonText);
            })
            ->pause(500);
    }

    /**
     * Assert table has rows
     */
    protected function assertTableHasRows(Browser $browser, int $minRows = 1): void
    {
        $browser->assertPresent('table tbody tr');
        $rows = $browser->elements('table tbody tr');
        expect(count($rows))->toBeGreaterThanOrEqual($minRows);
    }

    /**
     * Click bulk action button
     */
    protected function clickBulkAction(Browser $browser, string $actionText): void
    {
        $browser->click("button:contains('{$actionText}')")
            ->pause(500);
    }

    /**
     * Select items in table by clicking checkboxes
     */
    protected function selectTableItems(Browser $browser, array $itemIds): void
    {
        foreach ($itemIds as $id) {
            $browser->check("input[type='checkbox'][value='{$id}']");
        }
        $browser->pause(300);
    }
}

