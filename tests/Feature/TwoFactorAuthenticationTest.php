<?php

namespace Tests\Feature;

use App\Mail\TwoFactorChangedMail;
use App\Models\MfaSecurityEvent;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\SecurityCodeNotification;
use App\Services\MfaRecoveryCodeService;
use App\Services\TrustedDeviceService;
use App\Services\TwoFactorService;
use App\Services\TwoFactorSetupService;
use App\Support\QrCodeSvg;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);

        Setting::updateOrCreate(
            ['company_id' => 1],
            [
                'company_id' => 1,
                'smtp_host' => 'smtp.example.test',
                'from_email' => 'noreply@example.test',
                'from_name' => 'CibleRH Test',
                'mfa_required_for' => 'none',
                'mfa_authenticator_enabled' => true,
                'mfa_email_enabled' => true,
                'mfa_sms_enabled' => false,
                'mfa_code_minutes' => 10,
                'mfa_max_attempts' => 5,
                'mfa_remember_days' => 30,
            ]
        );
    }

    public function test_totp_generates_and_matches_valid_code_and_blocks_replay(): void
    {
        $secret = Totp::generateSecret();
        $this->assertSame(32, strlen($secret));

        $step = Totp::timestep();
        $code = Totp::codeAt($secret, $step);

        $this->assertSame(6, strlen($code));
        $this->assertSame($step, Totp::match($secret, $code));
        $this->assertNull(Totp::match($secret, $code, $step));

        $user = User::factory()->create([
            'mfa_enabled' => true,
            'mfa_method' => TwoFactorService::METHOD_AUTHENTICATOR,
            'mfa_secret' => $secret,
        ]);

        $service = app(TwoFactorService::class);
        $this->assertTrue($service->checkTotp($user, $code));
        // Replaying the exact same timestep must fail
        $this->assertFalse($service->checkTotp($user->fresh(), $code));
    }

    public function test_qr_code_svg_generates_valid_local_data_uri(): void
    {
        $secret = Totp::generateSecret();
        $uri = Totp::provisioningUri('CibleRH', 'admin@example.test', $secret);
        $dataUri = QrCodeSvg::dataUri($uri);

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $dataUri);
        $svg = base64_decode(substr($dataUri, strlen('data:image/svg+xml;base64,')));
        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('<path', $svg);

        // Verify ISO/IEC 18004 Format Information placement (MSB at [8,0], LSB at [0,8])
        $matrix = QrCodeSvg::encodeMatrix($uri);
        $coords1 = [
            [8, 0], [8, 1], [8, 2], [8, 3], [8, 4], [8, 5], [8, 7], [8, 8],
            [7, 8], [5, 8], [4, 8], [3, 8], [2, 8], [1, 8], [0, 8],
        ];
        $formatBits = 0;
        foreach ($coords1 as [$r, $c]) {
            $formatBits = ($formatBits << 1) | $matrix[$r][$c];
        }

        $unmaskedFormat = $formatBits ^ 0x5412;
        $ecIndicator = ($unmaskedFormat >> 13) & 0b11;
        $this->assertSame(0b01, $ecIndicator, 'EC level must be L (01)');

        // BCH(15, 5) remainder with generator polynomial 0x537 must be 0
        $rem = $unmaskedFormat;
        for ($bit = 14; $bit >= 10; $bit--) {
            if (($rem >> $bit) & 1) {
                $rem ^= (0x537 << ($bit - 10));
            }
        }
        $this->assertSame(0, $rem, 'Format information BCH checksum must be valid');
    }

    public function test_authenticator_setup_confirms_and_generates_ten_recovery_codes(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        $setup = app(TwoFactorSetupService::class);
        $payload = $setup->beginAuthenticator($user);

        $this->assertSame(TwoFactorService::METHOD_AUTHENTICATOR, $payload['method']);
        $this->assertNotEmpty($payload['qr_svg']);

        $validCode = Totp::codeAt($payload['secret'], Totp::timestep());
        $result = $setup->confirm($user, $validCode);

        $this->assertTrue($result['ok']);
        $this->assertCount(10, $result['recovery_codes']);

        $user->refresh();
        $this->assertTrue($user->mfa_enabled);
        $this->assertSame(TwoFactorService::METHOD_AUTHENTICATOR, $user->mfa_method);
        $this->assertNotNull($user->mfa_confirmed_at);
        $this->assertSame(10, app(MfaRecoveryCodeService::class)->remaining($user));

        Mail::assertSent(TwoFactorChangedMail::class, fn (TwoFactorChangedMail $mail) => $mail->changeType === 'enabled');
    }

    public function test_email_setup_sends_otp_and_confirms_enrollment(): void
    {
        Notification::fake();
        Mail::fake();

        $user = User::factory()->create(['email' => 'staff@example.test']);
        $this->actingAs($user);

        $setup = app(TwoFactorSetupService::class);
        $begin = $setup->beginEmail($user);

        $this->assertTrue($begin['ok']);

        $sentCode = null;
        Notification::assertSentOnDemand(
            SecurityCodeNotification::class,
            function (SecurityCodeNotification $notification, array $channels, AnonymousNotifiable $notifiable) use (&$sentCode) {
                $sentCode = $notification->code;
                return $notifiable->routes['mail'] === 'staff@example.test' && $notification->purpose === 'setup';
            }
        );

        $this->assertNotNull($sentCode);

        $confirm = $setup->confirm($user, $sentCode);
        $this->assertTrue($confirm['ok']);
        $this->assertTrue($user->fresh()->mfa_enabled);
        $this->assertSame(TwoFactorService::METHOD_EMAIL, $user->fresh()->mfa_method);
    }

    public function test_middleware_redirects_enrolled_user_to_challenge_and_blocks_non_mfa_livewire(): void
    {
        $secret = Totp::generateSecret();
        $user = User::factory()->create([
            'mfa_enabled' => true,
            'mfa_method' => TwoFactorService::METHOD_AUTHENTICATOR,
            'mfa_secret' => $secret,
        ]);
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get(route('portal.profile-setting'))
            ->assertRedirect(route('two-factor.challenge'));

        // Non-MFA Livewire update requests must be blocked with 403 while 2FA is unverified
        $snapshot = json_encode(['memo' => ['name' => 'portal.profile-setting']]);
        $this->actingAs($user)
            ->withHeader('X-Livewire', 'true')
            ->postJson('/livewire/update', [
                'components' => [
                    ['snapshot' => $snapshot],
                ],
            ])
            ->assertStatus(403);

        // Completing the challenge marks session verified and allows access
        $code = Totp::codeAt($secret, Totp::timestep());
        Livewire::actingAs($user)
            ->test(\App\Livewire\Auth\TwoFactorChallenge::class)
            ->set('code', $code)
            ->call('verify')
            ->assertRedirect(route('portal.profile-setting'));

        $this->assertTrue(app(TwoFactorService::class)->isVerified($user));
    }

    public function test_middleware_redirects_unenrolled_user_to_setup_when_policy_requires_mfa(): void
    {
        Setting::first()->update(['mfa_required_for' => TwoFactorService::POLICY_ADMINS_ONLY]);

        $admin = User::factory()->create(['mfa_enabled' => false]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('portal.profile-setting'))
            ->assertRedirect(route('two-factor.setup'));
    }

    public function test_challenge_locks_out_after_max_failed_attempts(): void
    {
        Setting::first()->update(['mfa_max_attempts' => 3]);

        $user = User::factory()->create([
            'mfa_enabled' => true,
            'mfa_method' => TwoFactorService::METHOD_AUTHENTICATOR,
            'mfa_secret' => Totp::generateSecret(),
        ]);

        $service = app(TwoFactorService::class);

        $first = $service->verifyChallenge($user, '000000');
        $this->assertFalse($first['ok']);
        $this->assertFalse($first['locked']);

        $second = $service->verifyChallenge($user, '000000');
        $this->assertFalse($second['ok']);
        $this->assertFalse($second['locked']);

        $third = $service->verifyChallenge($user, '000000');
        $this->assertFalse($third['ok']);
        $this->assertTrue($third['locked']);
        $this->assertTrue($service->isLockedOut($user));

        $this->assertDatabaseHas('mfa_security_events', [
            'user_id' => $user->id,
            'event' => 'challenge_locked',
        ]);
    }

    public function test_recovery_code_is_single_use_and_grants_access(): void
    {
        $user = User::factory()->create([
            'mfa_enabled' => true,
            'mfa_method' => TwoFactorService::METHOD_AUTHENTICATOR,
            'mfa_secret' => Totp::generateSecret(),
        ]);

        $codes = app(MfaRecoveryCodeService::class)->regenerate($user);
        $firstCode = $codes[0];

        $service = app(TwoFactorService::class);
        $res1 = $service->verifyRecoveryCode($user, $firstCode);

        $this->assertTrue($res1['ok']);
        $this->assertSame(9, $res1['remaining_codes']);

        // Reusing the same recovery code must fail
        $service->forgetVerification();
        $res2 = $service->verifyRecoveryCode($user, $firstCode);
        $this->assertFalse($res2['ok']);
    }

    public function test_trusted_device_cookie_admits_user_and_revokes_on_reset(): void
    {
        $user = User::factory()->create([
            'mfa_enabled' => true,
            'mfa_method' => TwoFactorService::METHOD_AUTHENTICATOR,
            'mfa_secret' => Totp::generateSecret(),
        ]);

        $trustedService = app(TrustedDeviceService::class);
        $request = Request::create('/two-factor/challenge', 'POST', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X) Chrome/120.0',
        ]);

        $device = $trustedService->trust($user, $request);
        $this->assertNotNull($device);
        $this->assertSame('Chrome · macOS', $device->name);

        // Extract queued cookie value
        $queuedCookies = app('cookie')->getQueuedCookies();
        $cookieName = $trustedService->cookieName($user);
        $matchedCookie = collect($queuedCookies)->first(fn ($c) => $c->getName() === $cookieName);
        $this->assertNotNull($matchedCookie);

        $nextRequest = Request::create('/portal/dashboard', 'GET', [], [
            $cookieName => $matchedCookie->getValue(),
        ]);

        $this->assertTrue($trustedService->admit($user, $nextRequest));

        // Admin reset clears trusted devices and recovery codes
        Mail::fake();
        $admin = User::factory()->create();
        app(TwoFactorSetupService::class)->resetFor($user, $admin);

        $this->assertFalse($user->fresh()->mfa_enabled);
        $this->assertCount(0, $trustedService->listFor($user));
        $this->assertFalse($trustedService->admit($user, $nextRequest));
    }

    public function test_in_place_test_mfa_flow_verifies_code(): void
    {
        $secret = Totp::generateSecret();
        $user = User::factory()->create([
            'mfa_enabled' => true,
            'mfa_method' => TwoFactorService::METHOD_AUTHENTICATOR,
            'mfa_secret' => $secret,
        ]);

        $service = app(TwoFactorService::class);
        $start = $service->startTest($user);
        $this->assertTrue($start['ok']);

        $code = Totp::codeAt($secret, Totp::timestep());
        $verify = $service->verifyTest($user, $code);
        $this->assertTrue($verify['ok']);

        $this->assertTrue(
            MfaSecurityEvent::where('user_id', $user->id)
                ->where('event', 'mfa_test_passed')
                ->exists()
        );
    }

    public function test_custom_auth_branding_text_and_background_render_on_auth_pages(): void
    {
        Setting::first()->update([
            'brand_app_name' => 'Acme Payroll',
            'brand_auth_bg_path' => 'branding/custom-hero.jpg',
            'brand_auth_kicker' => 'ACME WORKSPACE',
            'brand_auth_title' => 'Welcome to Acme HR',
            'brand_auth_subtitle' => 'Access your payslips and attendance securely.',
            'brand_auth_feature_1' => 'Instant payslip downloads',
            'brand_auth_feature_2' => 'One-click time off requests',
            'brand_auth_feature_3' => 'Protected by two-step verification',
            'brand_auth_footer' => '© 2026 Acme Corp. All rights reserved.',
        ]);
        \App\Support\Branding::flush();

        $user = User::factory()->create([
            'mfa_required' => true,
            'mfa_enabled' => false,
        ]);

        $response = $this->actingAs($user)->get(route('two-factor.setup'));
        $response->assertStatus(200);
        $response->assertSee('ACME WORKSPACE');
        $response->assertSee('Welcome to Acme HR');
        $response->assertSee('Access your payslips and attendance securely.');
        $response->assertSee('Instant payslip downloads');
        $response->assertSee('One-click time off requests');
        $response->assertSee('Protected by two-step verification');
        $response->assertSee('© 2026 Acme Corp. All rights reserved.');
        $response->assertSee('branding/custom-hero.jpg');
    }
}
