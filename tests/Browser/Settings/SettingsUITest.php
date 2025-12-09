<?php

use App\Models\User;
use App\Models\Setting;
use Laravel\Dusk\Browser;

beforeEach(function () {
    // Setup
});

test('user can view settings page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/settings')
            ->assertSee('Settings')
            ->assertPathIs('/portal/settings');
    });
});

test('user can update SMTP settings', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        Setting::factory()->create();

        $this->visitAndWait($browser, '/portal/settings');
        $browser->assertPresent('#smtp_host')
            ->type('#smtp_host', 'smtp.example.com')
            ->type('#smtp_port', '587')
            ->type('#smtp_username', 'user@example.com')
            ->type('#smtp_password', 'password123')
            ->select('#smtp_encryption', 'tls')
            ->type('#from_email', 'noreply@example.com')
            ->type('#from_name', 'Test Company')
            ->click('#save-mail-config-btn')
            ->pause(3000)
            ->assertSee('Setting for SMTP successfully added!');
    });
});

test('user can update SMS provider settings', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        Setting::factory()->create();
        
        $browser->visit('/portal/settings')
            ->select('#sms_provider', 'twilio')
            ->type('#sms_provider_username', 'test_username')
            ->type('#sms_provider_password', 'test_password')
            ->type('#sms_provider_senderid', 'TEST')
            ->click('#save-sms-config-btn')
            ->pause(2000)
            ->assertSee('Setting for SMS successfully added!');
    });
});

test('user can test email configuration', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        Setting::factory()->create();
        
        $browser->visit('/portal/settings')
            ->type('#test_email_address', 'test@example.com')
            ->type('#test_email_message', 'Test message')
            ->click('#send-test-email-btn')
            ->pause(2000)
            ->assertSee('Test Email sent successfully!');
    });
});

test('user can test SMS configuration', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        Setting::factory()->create();
        
        $browser->visit('/portal/settings')
            ->type('#test_phone_number', '+1234567890')
            ->type('#test_sms_message', 'Test SMS')
            ->click('#send-test-sms-btn')
            ->pause(1000)
            ->assertSee('sent');
    });
});

test('user can update email templates', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        Setting::factory()->create();
        
        $browser->visit('/portal/settings')
            ->type('#email_subject_en', 'Your Payslip')
            ->type('#email_content_en', 'Dear :name:, your payslip is attached.')
            ->click('#save-mail-config-btn')
            ->pause(1000)
            ->assertSee('saved');
    });
});

test('user can update SMS templates', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        Setting::factory()->create();
        
        $browser->visit('/portal/settings')
            ->type('#sms_content_en', 'Your payslip for :month: has been sent.')
            ->click('#save-sms-config-btn')
            ->pause(1000)
            ->assertSee('saved');
    });
});

test('user can update welcome email templates', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        Setting::factory()->create();
        
        $browser->visit('/portal/settings')
            ->type('#welcome_email_subject_en', 'Welcome')
            ->type('#welcome_email_content_en', 'Welcome to our system')
            ->click('#save-welcome-email-config-btn')
            ->pause(1000)
            ->assertSee('saved');
    });
});

test('user can update birthday SMS templates', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        Setting::factory()->create();

        $this->visitAndWait($browser, '/portal/settings');
        $browser->type('#birthday_sms_message_en', 'Happy Birthday :name:!')
            ->click('#save-sms-config-btn')
            ->pause(2000)
            ->assertPresent('.alert-success, .text-success');
    });
});

test('user sees validation errors for required SMTP fields', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        Setting::factory()->create();
        
        $browser->visit('/portal/settings')
            ->clear('#smtp_host')
            ->click('#save-mail-config-btn')
            ->pause(2000)
            ->assertPresent('.invalid-feedback, .text-danger, .alert-danger');
    });
});

test('user can check SMS balance', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        Setting::factory()->create(['sms_balance' => 100]);
        
        $browser->visit('/portal/settings')
            ->assertSee('100');
    });
});








