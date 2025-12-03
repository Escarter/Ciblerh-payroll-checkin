<?php

use App\Models\User;
use Laravel\Dusk\Browser;

beforeEach(function () {
    // Setup
});

test('user can view profile settings page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/profile-setting')
            ->assertSee('Profile')
            ->assertPathIs('/portal/profile-setting');
    });
});

test('user can update profile information', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/profile-setting')
            ->type('#first_name', 'Updated')
            ->type('#last_name', 'Name')
            ->type('#email', 'updated@example.com')
            ->type('#phone_number', '+1234567890')
            ->type('#position', 'Senior Developer')
            ->click('button:contains("Update Profile")')
            ->pause(1000)
            ->assertSee('updated successfully');
    });
});

test('user can update password', function () {
    $this->browse(function (Browser $browser) {
        $user = User::factory()->create([
            'password' => bcrypt('oldpassword'),
        ]);
        $user->assignRole('admin');
        
        $browser->visit('/login')
            ->type('#email', $user->email)
            ->type('#password', 'oldpassword')
            ->press('Login')
            ->pause(1000)
            ->visit('/portal/profile-setting')
            ->type('#current_password', 'oldpassword')
            ->type('#password', 'newpassword123')
            ->type('#password_confirmation', 'newpassword123')
            ->click('button:contains("Reset Password")')
            ->pause(1000)
            ->assertSee('reseted successfully');
    });
});

test('user sees validation error for incorrect current password', function () {
    $this->browse(function (Browser $browser) {
        $user = User::factory()->create([
            'password' => bcrypt('correctpassword'),
        ]);
        $user->assignRole('admin');
        
        $browser->visit('/login')
            ->type('#email', $user->email)
            ->type('#password', 'correctpassword')
            ->press('Login')
            ->pause(1000)
            ->visit('/portal/profile-setting')
            ->type('#current_password', 'wrongpassword')
            ->type('#password', 'newpassword123')
            ->type('#password_confirmation', 'newpassword123')
            ->click('button:contains("Reset Password")')
            ->pause(500)
            ->assertSee('incorrect');
    });
});

test('user sees validation error when passwords do not match', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/profile-setting')
            ->type('#current_password', 'password')
            ->type('#password', 'newpassword123')
            ->type('#password_confirmation', 'differentpassword')
            ->click('button:contains("Reset Password")')
            ->pause(500)
            ->assertSee('match');
    });
});

test('user can upload signature', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        // Note: File upload testing in Dusk requires actual file path
        $browser->visit('/portal/profile-setting')
            ->attach('#signature', __DIR__ . '/../../test-files/signature.png')
            ->click('button:contains("Save Signature")')
            ->pause(1000)
            ->assertSee('saved successfully');
    });
});

test('user can update preferred language', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/profile-setting')
            ->select('#preferred_language', 'fr')
            ->click('button:contains("Update Profile")')
            ->pause(1000)
            ->assertSee('updated successfully');
    });
});

test('user can update date of birth', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/profile-setting')
            ->type('#date_of_birth', '1990-01-01')
            ->click('button:contains("Update Profile")')
            ->pause(1000)
            ->assertSee('updated successfully');
    });
});

test('user sees validation errors for required fields', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/profile-setting')
            ->clear('#first_name')
            ->clear('#last_name')
            ->click('button:contains("Update Profile")')
            ->pause(500)
            ->assertSee('required');
    });
});

