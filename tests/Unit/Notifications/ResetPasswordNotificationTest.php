<?php

namespace Tests\Unit\Notifications;

use App\Models\User;
use App\Models\Setting;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResetPasswordNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Create the required roles for User factory
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    /** @test */
    public function it_applies_saved_smtp_settings_for_password_reset()
    {
        // Create SMTP settings in database
        Setting::factory()->create([
            'company_id' => 1,
            'smtp_provider' => 'smtp',
            'smtp_host' => 'smtp.mailtrap.io',
            'smtp_port' => 587,
            'smtp_username' => 'test@example.com',
            'smtp_password' => 'password123',
            'smtp_encryption' => 'tls',
            'from_email' => 'noreply@example.com',
            'from_name' => 'Test App',
        ]);

        $user = User::factory()->create(['email' => 'user@example.com']);
        $token = 'test-reset-token';

        $notification = new ResetPasswordNotification($token);
        // Should not throw exception when valid settings exist
        $mailMessage = $notification->toMail($user);

        // Verify the mail message was created without errors
        $this->assertNotNull($mailMessage);
    }

    /** @test */
    public function it_throws_error_when_no_smtp_settings_exist()
    {
        // Ensure no settings exist
        Setting::truncate();

        // Set empty environment mail host to trigger full error
        config(['mail.mailers.smtp.host' => null]);

        $user = User::factory()->create(['email' => 'user@example.com']);
        $token = 'test-reset-token';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Email configuration is not set up');

        $notification = new ResetPasswordNotification($token);
        $notification->toMail($user);
    }

    /** @test */
    public function it_throws_error_when_smtp_host_is_missing()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SMTP host and port are required');

        // Create incomplete SMTP settings
        Setting::factory()->create([
            'company_id' => 1,
            'smtp_provider' => 'smtp',
            'smtp_host' => '', // Empty host
            'smtp_port' => 587,
            'smtp_username' => 'test@example.com',
            'smtp_password' => 'password123',
        ]);

        // Set empty environment mail host to avoid fallback
        config(['mail.mailers.smtp.host' => null]);

        $user = User::factory()->create(['email' => 'user@example.com']);
        $token = 'test-reset-token';

        $notification = new ResetPasswordNotification($token);
        $notification->toMail($user);
    }

    /** @test */
    public function it_logs_configuration_errors()
    {
        \Illuminate\Support\Facades\Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Password reset failed due to mail configuration error'
                    && isset($context['error']);
            });

        config(['mail.mailers.smtp.host' => null]);
        Setting::truncate();

        $user = User::factory()->create(['email' => 'user@example.com']);
        $token = 'test-reset-token';

        $notification = new ResetPasswordNotification($token);

        try {
            $notification->toMail($user);
        } catch (\RuntimeException $e) {
            // Expected
        }
    }

    /** @test */
    public function it_falls_back_to_environment_config_when_no_saved_settings()
    {
        // Ensure no saved settings
        Setting::truncate();

        // Set environment mail config
        config([
            'mail.mailers.smtp.host' => 'smtp.mailgun.org',
            'mail.mailers.smtp.port' => 587,
            'mail.mailers.smtp.username' => 'env@example.com',
            'mail.mailers.smtp.password' => 'envpass',
        ]);

        $user = User::factory()->create(['email' => 'user@example.com']);
        $token = 'test-reset-token';

        $notification = new ResetPasswordNotification($token);
        $mailMessage = $notification->toMail($user);

        // Verify fallback is allowed (no exception thrown)
        $this->assertNotNull($mailMessage);
    }
}
