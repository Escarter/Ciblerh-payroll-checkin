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
        
        $browser->visit('/portal/settings')
            ->type('#smtp_host', 'smtp.example.com')
            ->type('#smtp_port', '587')
            ->type('#smtp_username', 'user@example.com')
            ->type('#smtp_password', 'password123')
            ->select('#smtp_encryption', 'tls')
            ->type('#from_email', 'noreply@example.com')
            ->type('#from_name', 'Test Company')
            ->click('button:contains("Save SMTP Config")')
            ->pause(1000)
            ->assertSee('saved');
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
            ->click('button:contains("Save SMS Config")')
            ->pause(1000)
            ->assertSee('saved');
    });
});

test('user can test email configuration', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        Setting::factory()->create();
        
        $browser->visit('/portal/settings')
            ->type('#test_email_address', 'test@example.com')
            ->type('#test_email_message', 'Test message')
            ->click('button:contains("Send Test Email")')
            ->pause(1000)
            ->assertSee('sent');
    });
});

test('user can test SMS configuration', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        Setting::factory()->create();
        
        $browser->visit('/portal/settings')
            ->type('#test_phone_number', '+1234567890')
            ->type('#test_sms_message', 'Test SMS')
            ->click('button:contains("Send Test SMS")')
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
            ->click('button:contains("Save Email Templates")')
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
            ->click('button:contains("Save SMS Templates")')
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
            ->click('button:contains("Save Welcome Email Templates")')
            ->pause(1000)
            ->assertSee('saved');
    });
});

test('user can update birthday SMS templates', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        Setting::factory()->create();
        
        $browser->visit('/portal/settings')
            ->type('#birthday_sms_message_en', 'Happy Birthday :name:!')
            ->click('button:contains("Save Birthday SMS Templates")')
            ->pause(1000)
            ->assertSee('saved');
    });
});

test('user sees validation errors for required SMTP fields', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        Setting::factory()->create();
        
        $browser->visit('/portal/settings')
            ->clear('#smtp_host')
            ->click('button:contains("Save SMTP Config")')
            ->pause(500)
            ->assertSee('required');
    });
});

test('user can check SMS balance', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        Setting::factory()->create(['sms_balance' => 100]);
        
        $browser->visit('/portal/settings')
            ->click('button:contains("Check Balance")')
            ->pause(1000)
            ->assertSee('100');
    });
});

