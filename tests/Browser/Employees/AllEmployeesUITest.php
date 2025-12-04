<?php

use App\Models\User;
use App\Models\Company;
use Laravel\Dusk\Browser;

beforeEach(function () {
    // Setup
});

test('user can view all employees page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/employees/all')
            ->assertSee('All Employees')
            ->assertPathIs('/portal/employees/all');
    });
});

test('user can search all employees', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $employee = User::factory()->create(['first_name' => 'John']);
        
        $browser->visit('/portal/employees/all')
            ->type('#search', 'John')
            ->pause(1000)
            ->assertSee('John');
    });
});

test('user can filter by role', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/employees/all')
            ->select('#role_name', 'employee')
            ->pause(500)
            ->assertSelected('#role_name', 'employee');
    });
});

test('user can filter by status', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/employees/all')
            ->select('#status', '1')
            ->pause(500)
            ->assertSelected('#status', '1');
    });
});

test('user can switch between active and deleted tabs', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $employee = User::factory()->create();
        $employee->delete();
        
        $browser->visit('/portal/employees/all')
            ->click('button:contains("Deleted")')
            ->pause(500)
            ->assertSee('Deleted');
    });
});

test('user can create employee from all employees page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/employees/all')
            ->click('button:contains("Create Employee")')
            ->pause(500)
            ->waitFor('#EmployeeModal', 5)
            ->assertSee('Create Employee');
    });
});

test('user can change order by field', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/employees/all')
            ->select('#orderBy', 'last_name')
            ->pause(500)
            ->assertSelected('#orderBy', 'last_name');
    });
});

test('user can change items per page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        User::factory()->count(10)->create();
        
        $browser->visit('/portal/employees/all')
            ->select('#perPage', '5')
            ->pause(500)
            ->assertSelected('#perPage', '5');
    });
});







