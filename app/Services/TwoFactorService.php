<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use App\Notifications\SecurityCodeNotification;
use App\Services\AwsSnsSMS;
use App\Services\Nexah;
use App\Services\OrangeCameroonSMS;
use App\Services\TwilioSMS;
use App\Support\Branding;
use App\Support\QrCodeSvg;
use App\Support\Totp;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;

/**
 * Coordinates two-step verification across the application:
 *  - Authenticator app (RFC 6238 TOTP, QR code + manual entry key)
 *  - Email one-time verification code
 *  - SMS one-time verification code (via the configured SMS provider)
 *
 * Designed around three calm user journeys:
 *  1. Setup   — pick a method, verify one code, done (plus 10 single-use recovery codes).
 *  2. Sign-in — enter 6 digits (or a recovery code) after password sign-in, optionally trusting the browser.
 *  3. Test    — send/check a code from Profile without logging out.
 */
class TwoFactorService
{
    public const METHOD_AUTHENTICATOR = 'authenticator';
    public const METHOD_EMAIL = 'email';
    public const METHOD_SMS = 'sms';

    public const POLICY_NONE = 'none';
    public const POLICY_ADMINS_ONLY = 'admins_only';
    public const POLICY_PORTAL_STAFF = 'portal_staff';
    public const POLICY_EVERYONE = 'everyone';

    public const SESSION_VERIFIED_FOR = 'mfa.verified_for';
    public const SESSION_VERIFIED_AT = 'mfa.verified_at';
    public const SESSION_PENDING_SETUP = 'mfa.pending_setup';
    public const SESSION_PENDING_TEST = 'mfa.pending_test';
    public const SESSION_CHALLENGE_SENT_AT = 'mfa.challenge_sent_at';
    public const SESSION_FLASH_RECOVERY_CODES = 'mfa.flash_recovery_codes';

    public const RESEND_COOLDOWN_SECONDS = 30;
    public const LOCKOUT_SECONDS = 300; // 5 minutes

    public function __construct(
        private readonly MfaRecoveryCodeService $recoveryCodes,
        private readonly MfaSecurityEventService $events,
        private readonly TrustedDeviceService $trustedDevices
    ) {}

    // ------------------------------------------------------------------
    // Policy & availability
    // ------------------------------------------------------------------

    public function setting(): ?Setting
    {
        try {
            return Setting::first();
        } catch (\Throwable) {
            return null;
        }
    }

    public function policy(): string
    {
        $policy = (string) ($this->setting()?->mfa_required_for ?: self::POLICY_NONE);

        return in_array($policy, [
            self::POLICY_NONE,
            self::POLICY_ADMINS_ONLY,
            self::POLICY_PORTAL_STAFF,
            self::POLICY_EVERYONE,
        ], true) ? $policy : self::POLICY_NONE;
    }

    /**
     * Methods the administrator has allowed users to choose from.
     *
     * @return array<string, array{key:string,label:string,description:string,ready:bool,hint:?string}>
     */
    public function allowedMethods(?User $user = null): array
    {
        $setting = $this->setting();
        $authenticatorAllowed = $setting ? (bool) ($setting->mfa_authenticator_enabled ?? true) : true;
        $emailAllowed = $setting ? (bool) ($setting->mfa_email_enabled ?? true) : true;
        $smsAllowed = $setting ? (bool) ($setting->mfa_sms_enabled ?? false) : false;

        $smsConfigured = $this->isSmsProviderConfigured($setting);
        $emailConfigured = $this->isEmailConfigured($setting);

        $methods = [];

        if ($authenticatorAllowed) {
            $methods[self::METHOD_AUTHENTICATOR] = [
                'key' => self::METHOD_AUTHENTICATOR,
                'label' => __('two_factor.method_authenticator'),
                'description' => __('two_factor.method_authenticator_help'),
                'ready' => true,
                'hint' => null,
            ];
        }

        if ($emailAllowed) {
            $userHasEmail = $user ? filled($user->email) : true;
            $methods[self::METHOD_EMAIL] = [
                'key' => self::METHOD_EMAIL,
                'label' => __('two_factor.method_email'),
                'description' => __('two_factor.method_email_help'),
                'ready' => $emailConfigured && $userHasEmail,
                'hint' => !$emailConfigured
                    ? __('two_factor.hint_configure_smtp')
                    : (!$userHasEmail ? __('two_factor.hint_user_needs_email') : null),
            ];
        }

        if ($smsAllowed) {
            $defaultPhone = $user ? $this->defaultPhoneForUser($user) : null;
            $userHasPhone = $user ? filled($defaultPhone) : true;
            $methods[self::METHOD_SMS] = [
                'key' => self::METHOD_SMS,
                'label' => __('two_factor.method_sms'),
                'description' => __('two_factor.method_sms_help'),
                'ready' => $smsConfigured,
                'hint' => !$smsConfigured
                    ? __('two_factor.hint_configure_sms')
                    : (!$userHasPhone ? __('two_factor.hint_enter_phone_during_setup') : null),
            ];
        }

        return $methods;
    }

    public function defaultPhoneForUser(User $user): ?string
    {
        return $user->mfa_phone
            ?: $user->professional_phone_number
            ?: $user->personal_phone_number
            ?: $user->phone_number
            ?: null;
    }

    public function isSmsProviderConfigured(?Setting $setting = null): bool
    {
        $setting = $setting ?? $this->setting();
        if (!$setting || empty($setting->sms_provider)) {
            return false;
        }

        return match ($setting->sms_provider) {
            'twilio' => filled($setting->twilio_account_sid) && filled($setting->twilio_auth_token) && filled($setting->twilio_From_number),
            'aws_sns' => filled($setting->aws_access_key_id) && filled($setting->aws_secret_access_key),
            'orange_cm' => filled($setting->orange_cm_client_id) && filled($setting->orange_cm_client_secret),
            'nexah' => filled($setting->sms_provider_username) && filled($setting->sms_provider_password) && filled($setting->sms_provider_senderid),
            default => false,
        };
    }

    public function isEmailConfigured(?Setting $setting = null): bool
    {
        $setting = $setting ?? $this->setting();
        if ($setting && filled($setting->smtp_host) && filled($setting->from_email)) {
            return true;
        }

        return filled(config('mail.mailers.smtp.host')) || in_array(config('mail.default'), ['log', 'array'], true);
    }

    /**
     * Whether MFA is required for a given user (either per-user or by global policy).
     */
    public function isRequiredFor(User $user): bool
    {
        if ((bool) $user->mfa_required) {
            return true;
        }

        return match ($this->policy()) {
            self::POLICY_EVERYONE => true,
            self::POLICY_PORTAL_STAFF => $user->isAdminUser(),
            self::POLICY_ADMINS_ONLY => $user->hasRole('admin'),
            default => false,
        };
    }

    public function isEnabledFor(User $user): bool
    {
        if (!(bool) $user->mfa_enabled || empty($user->mfa_method)) {
            return false;
        }

        return match ($user->mfa_method) {
            self::METHOD_AUTHENTICATOR => filled($user->mfa_secret),
            self::METHOD_EMAIL => filled($user->email),
            self::METHOD_SMS => filled($user->mfa_phone ?: $this->defaultPhoneForUser($user)),
            default => false,
        };
    }

    public function maxAttempts(): int
    {
        $raw = (int) ($this->setting()?->mfa_max_attempts ?: 5);

        return max(3, min(10, $raw));
    }

    public function codeMinutes(): int
    {
        $raw = (int) ($this->setting()?->mfa_code_minutes ?: 10);

        return max(3, min(30, $raw));
    }

    // ------------------------------------------------------------------
    // Session verification state
    // ------------------------------------------------------------------

    public function isVerified(User $user): bool
    {
        return Session::get(self::SESSION_VERIFIED_FOR) === (string) $user->getAuthIdentifier();
    }

    public function markVerified(User $user): void
    {
        Session::put(self::SESSION_VERIFIED_FOR, (string) $user->getAuthIdentifier());
        Session::put(self::SESSION_VERIFIED_AT, now()->timestamp);
        Session::forget(self::SESSION_CHALLENGE_SENT_AT);

        $user->forceFill(['mfa_last_used_at' => now()])->saveQuietly();
    }

    public function forgetVerification(): void
    {
        Session::forget([
            self::SESSION_VERIFIED_FOR,
            self::SESSION_VERIFIED_AT,
            self::SESSION_PENDING_SETUP,
            self::SESSION_PENDING_TEST,
            self::SESSION_CHALLENGE_SENT_AT,
        ]);
    }

    // ------------------------------------------------------------------
    // Authenticator QR & URI helpers
    // ------------------------------------------------------------------

    public function buildProvisioningUri(User $user, string $secret): string
    {
        $issuer = Branding::portalTitle();
        $label = $user->email ?: ($user->name ?: ('user-' . $user->id));

        return Totp::provisioningUri($issuer, $label, $secret);
    }

    public function buildQrSvgDataUri(string $provisioningUri): ?string
    {
        try {
            return QrCodeSvg::dataUri($provisioningUri, 220, 4);
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    // ------------------------------------------------------------------
    // One-time code delivery (Email & SMS)
    // ------------------------------------------------------------------

    /**
     * Generate and send a 6-digit code for the given purpose ('challenge', 'setup', 'test').
     *
     * @return array{sent:bool,destination:string,masked:string,cooldown:int,error:?string}
     */
    public function sendCode(
        User $user,
        string $channel,
        string $purpose = 'challenge',
        ?string $overrideDestination = null
    ): array {
        $destination = $overrideDestination ?: match ($channel) {
            self::METHOD_SMS => (string) ($user->mfa_phone ?: $this->defaultPhoneForUser($user) ?: ''),
            default => (string) ($user->email ?: ''),
        };

        $destination = trim($destination);
        if ($destination === '') {
            return [
                'sent' => false,
                'destination' => '',
                'masked' => '',
                'cooldown' => 0,
                'error' => $channel === self::METHOD_SMS
                    ? __('two_factor.error_missing_phone')
                    : __('two_factor.error_missing_email'),
            ];
        }

        $cooldownKey = $this->cooldownKey($user, $purpose);
        if (RateLimiter::tooManyAttempts($cooldownKey, 1)) {
            $wait = RateLimiter::availableIn($cooldownKey);
            return [
                'sent' => false,
                'destination' => $destination,
                'masked' => $this->mask($destination, $channel),
                'cooldown' => $wait,
                'error' => __('two_factor.Please wait :seconds seconds before requesting another code.', ['seconds' => $wait]),
            ];
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $minutes = $this->codeMinutes();

        Cache::put($this->codeCacheKey($user, $purpose), [
            'hash' => Hash::make($code),
            'channel' => $channel,
            'destination' => $destination,
            'expires_at' => now()->addMinutes($minutes)->timestamp,
        ], now()->addMinutes($minutes));

        $deliveryError = $this->deliverCode($user, $channel, $destination, $code, $minutes, $purpose);
        if ($deliveryError !== null) {
            Cache::forget($this->codeCacheKey($user, $purpose));
            return [
                'sent' => false,
                'destination' => $destination,
                'masked' => $this->mask($destination, $channel),
                'cooldown' => 0,
                'error' => $deliveryError,
            ];
        }

        RateLimiter::hit($cooldownKey, self::RESEND_COOLDOWN_SECONDS);

        if ($purpose === 'challenge') {
            Session::put(self::SESSION_CHALLENGE_SENT_AT, now()->timestamp);
        }

        return [
            'sent' => true,
            'destination' => $destination,
            'masked' => $this->mask($destination, $channel),
            'cooldown' => self::RESEND_COOLDOWN_SECONDS,
            'error' => null,
        ];
    }

    /**
     * Send the sign-in code automatically on first visit to the challenge screen.
     */
    public function ensureChallengeCodeSent(User $user): ?array
    {
        if (!in_array($user->mfa_method, [self::METHOD_EMAIL, self::METHOD_SMS], true)) {
            return null;
        }

        if ($this->hasPendingCode($user, 'challenge')) {
            $dest = $this->pendingDestination($user, 'challenge');
            return [
                'sent' => true,
                'destination' => $dest,
                'masked' => $this->mask($dest, $user->mfa_method),
                'cooldown' => $this->resendWaitSeconds($user, 'challenge'),
                'error' => null,
            ];
        }

        return $this->sendCode($user, $user->mfa_method, 'challenge');
    }

    public function hasPendingCode(User $user, string $purpose = 'challenge'): bool
    {
        $payload = Cache::get($this->codeCacheKey($user, $purpose));

        return is_array($payload) && ($payload['expires_at'] ?? 0) > now()->timestamp;
    }

    public function pendingDestination(User $user, string $purpose = 'challenge'): string
    {
        $payload = Cache::get($this->codeCacheKey($user, $purpose));
        if (is_array($payload) && !empty($payload['destination'])) {
            return (string) $payload['destination'];
        }

        return $user->mfa_method === self::METHOD_SMS
            ? (string) ($user->mfa_phone ?: $this->defaultPhoneForUser($user) ?: '')
            : (string) ($user->email ?: '');
    }

    public function resendWaitSeconds(User $user, string $purpose = 'challenge'): int
    {
        $key = $this->cooldownKey($user, $purpose);

        return RateLimiter::tooManyAttempts($key, 1)
            ? RateLimiter::availableIn($key)
            : 0;
    }

    // ------------------------------------------------------------------
    // Code verification & attempt limiting
    // ------------------------------------------------------------------

    public function isLockedOut(User $user): bool
    {
        return RateLimiter::tooManyAttempts($this->attemptKey($user), $this->maxAttempts());
    }

    public function lockoutSeconds(User $user): int
    {
        return $this->isLockedOut($user)
            ? RateLimiter::availableIn($this->attemptKey($user))
            : 0;
    }

    public function remainingAttempts(User $user): int
    {
        return max(0, RateLimiter::remaining($this->attemptKey($user), $this->maxAttempts()));
    }

    /**
     * Verify a 6-digit code during sign-in challenge.
     *
     * @return array{ok:bool,error:?string,locked:bool}
     */
    public function verifyChallenge(User $user, string $code): array
    {
        if ($this->isLockedOut($user)) {
            $this->events->log(
                $user,
                MfaSecurityEventService::EVENT_CHALLENGE_LOCKED,
                'warning',
                $user->mfa_method,
                'Attempt while locked out'
            );
            return [
                'ok' => false,
                'locked' => true,
                'error' => __('two_factor.Too many incorrect codes. Please wait :seconds seconds and try again.', [
                    'seconds' => $this->lockoutSeconds($user),
                ]),
            ];
        }

        $clean = preg_replace('/\D+/', '', $code) ?? '';
        if (strlen($clean) !== 6) {
            return [
                'ok' => false,
                'locked' => false,
                'error' => __('two_factor.Please enter the 6-digit verification code.'),
            ];
        }

        $valid = match ($user->mfa_method) {
            self::METHOD_AUTHENTICATOR => $this->checkTotp($user, $clean),
            self::METHOD_EMAIL, self::METHOD_SMS => $this->consumeSentCode($user, 'challenge', $clean),
            default => false,
        };

        if (!$valid) {
            RateLimiter::hit($this->attemptKey($user), self::LOCKOUT_SECONDS);

            if ($this->isLockedOut($user)) {
                $this->events->log(
                    $user,
                    MfaSecurityEventService::EVENT_CHALLENGE_LOCKED,
                    'danger',
                    $user->mfa_method,
                    'Max verification attempts reached'
                );
                return [
                    'ok' => false,
                    'locked' => true,
                    'error' => __('two_factor.Too many incorrect codes. Please wait :seconds seconds and try again.', [
                        'seconds' => $this->lockoutSeconds($user),
                    ]),
                ];
            }

            $left = $this->remainingAttempts($user);
            $this->events->log(
                $user,
                MfaSecurityEventService::EVENT_CHALLENGE_FAILED,
                'warning',
                $user->mfa_method,
                "Invalid code ({$left} attempts remaining)"
            );
            return [
                'ok' => false,
                'locked' => false,
                'error' => __('two_factor.Invalid verification code. :count attempts remaining.', ['count' => $left]),
            ];
        }

        RateLimiter::clear($this->attemptKey($user));
        $this->markVerified($user);

        $this->events->log(
            $user,
            MfaSecurityEventService::EVENT_CHALLENGE_PASSED,
            'success',
            $user->mfa_method
        );

        auditLog(
            Auth::user() ?? $user,
            'user_mfa',
            'web',
            __('two_factor.audit_challenge_passed', ['user' => $user->name, 'method' => $user->mfa_method])
        );

        return ['ok' => true, 'locked' => false, 'error' => null];
    }

    /**
     * Verify a single-use backup recovery code during sign-in challenge.
     *
     * @return array{ok:bool,error:?string,locked:bool,remaining_codes?:int}
     */
    public function verifyRecoveryCode(User $user, string $recoveryCode): array
    {
        if ($this->isLockedOut($user)) {
            return [
                'ok' => false,
                'locked' => true,
                'error' => __('two_factor.Too many incorrect codes. Please wait :seconds seconds and try again.', [
                    'seconds' => $this->lockoutSeconds($user),
                ]),
            ];
        }

        $normalized = $this->recoveryCodes->normalize($recoveryCode);
        if (strlen($normalized) < 8) {
            return [
                'ok' => false,
                'locked' => false,
                'error' => __('two_factor.Please enter a valid recovery code (for exampleXXXXX-XXXXX).'),
            ];
        }

        $consumed = $this->recoveryCodes->consume($user, $normalized);
        if (!$consumed) {
            RateLimiter::hit($this->attemptKey($user), self::LOCKOUT_SECONDS);
            $left = $this->remainingAttempts($user);

            $this->events->log(
                $user,
                MfaSecurityEventService::EVENT_RECOVERY_FAILED,
                'warning',
                'recovery_code',
                "Invalid or already used recovery code ({$left} attempts remaining)"
            );

            return [
                'ok' => false,
                'locked' => $this->isLockedOut($user),
                'error' => __('two_factor.Invalid or already used recovery code. :count attempts remaining.', ['count' => $left]),
            ];
        }

        RateLimiter::clear($this->attemptKey($user));
        $this->markVerified($user);

        $remaining = $this->recoveryCodes->remaining($user);

        $this->events->log(
            $user,
            MfaSecurityEventService::EVENT_RECOVERY_USED,
            'warning',
            'recovery_code',
            null,
            ['remaining_codes' => $remaining]
        );

        auditLog(
            Auth::user() ?? $user,
            'user_mfa',
            'web',
            __('two_factor.audit_recovery_used', ['user' => $user->name, 'remaining' => $remaining])
        );

        return [
            'ok' => true,
            'locked' => false,
            'error' => null,
            'remaining_codes' => $remaining,
        ];
    }

    // ------------------------------------------------------------------
    // In-place "Test My Two-Step Verification" flow (from Profile)
    // ------------------------------------------------------------------

    /**
     * Start a non-destructive verification test for an already-enrolled user.
     *
     * @return array{ok:bool,method:?string,masked:?string,error:?string}
     */
    public function startTest(User $user): array
    {
        if (!$this->isEnabledFor($user)) {
            return [
                'ok' => false,
                'method' => null,
                'masked' => null,
                'error' => __('two_factor.Turn on two-step verification before running a test.'),
            ];
        }

        if ($user->mfa_method === self::METHOD_AUTHENTICATOR) {
            Session::put(self::SESSION_PENDING_TEST, [
                'method' => self::METHOD_AUTHENTICATOR,
                'started_at' => now()->timestamp,
            ]);
            return [
                'ok' => true,
                'method' => self::METHOD_AUTHENTICATOR,
                'masked' => null,
                'error' => null,
            ];
        }

        $result = $this->sendCode($user, $user->mfa_method, 'test');
        if (!$result['sent'] && $result['cooldown'] <= 0) {
            return [
                'ok' => false,
                'method' => $user->mfa_method,
                'masked' => $result['masked'],
                'error' => $result['error'],
            ];
        }

        Session::put(self::SESSION_PENDING_TEST, [
            'method' => $user->mfa_method,
            'masked' => $result['masked'],
            'started_at' => now()->timestamp,
        ]);

        return [
            'ok' => true,
            'method' => $user->mfa_method,
            'masked' => $result['masked'],
            'error' => null,
        ];
    }

    /**
     * Verify a test code without altering the user's session or configuration.
     *
     * @return array{ok:bool,error:?string}
     */
    public function verifyTest(User $user, string $code): array
    {
        $clean = preg_replace('/\D+/', '', $code) ?? '';
        if (strlen($clean) !== 6) {
            return [
                'ok' => false,
                'error' => __('two_factor.Please enter the 6-digit verification code.'),
            ];
        }

        $valid = match ($user->mfa_method) {
            self::METHOD_AUTHENTICATOR => $this->checkTotp($user, $clean),
            self::METHOD_EMAIL, self::METHOD_SMS => $this->consumeSentCode($user, 'test', $clean),
            default => false,
        };

        if (!$valid) {
            $this->events->log($user, MfaSecurityEventService::EVENT_MFA_TEST_FAILED, 'warning', $user->mfa_method);
            return [
                'ok' => false,
                'error' => __('two_factor.That code did not match. Please check the latest code and try again.'),
            ];
        }

        Session::forget(self::SESSION_PENDING_TEST);
        $user->forceFill(['mfa_last_used_at' => now()])->saveQuietly();
        $this->events->log($user, MfaSecurityEventService::EVENT_MFA_TEST_PASSED, 'success', $user->mfa_method);

        return ['ok' => true, 'error' => null];
    }

    public function cancelTest(): void
    {
        Session::forget(self::SESSION_PENDING_TEST);
    }

    // ------------------------------------------------------------------
    // Plain-language status summary for UI cards
    // ------------------------------------------------------------------

    /**
     * Build a human-friendly status summary for the Settings page or a user's profile card.
     *
     * @return array{policy:string,policy_label:string,methods:array<int,string>,enrolled_count:int,staff_total:int,recovery_remaining:int,trusted_devices_count:int}
     */
    public function summary(?User $forUser = null): array
    {
        $policy = $this->policy();
        $policyLabel = match ($policy) {
            self::POLICY_EVERYONE => __('two_factor.policy_everyone'),
            self::POLICY_PORTAL_STAFF => __('two_factor.policy_portal_staff'),
            self::POLICY_ADMINS_ONLY => __('two_factor.policy_admins_only'),
            default => __('two_factor.policy_optional'),
        };

        $methods = collect($this->allowedMethods())
            ->pluck('label')
            ->values()
            ->all();

        $enrolledCount = 0;
        $staffTotal = 0;
        try {
            $staffTotal = User::count();
            $enrolledCount = User::where('mfa_enabled', true)->count();
        } catch (\Throwable) {
            // Ignore before migrations run
        }

        $recoveryRemaining = 0;
        $trustedCount = 0;
        if ($forUser && $this->isEnabledFor($forUser)) {
            try {
                $recoveryRemaining = $this->recoveryCodes->remaining($forUser);
                $trustedCount = $this->trustedDevices->listFor($forUser)->count();
            } catch (\Throwable) {
                // Ignore before migrations run
            }
        }

        return [
            'policy' => $policy,
            'policy_label' => $policyLabel,
            'methods' => $methods,
            'enrolled_count' => $enrolledCount,
            'staff_total' => $staffTotal,
            'recovery_remaining' => $recoveryRemaining,
            'trusted_devices_count' => $trustedCount,
        ];
    }

    // ------------------------------------------------------------------
    // Internal helpers
    // ------------------------------------------------------------------

    /**
     * Check a 6-digit TOTP code and atomically advance `mfa_totp_timestep` so the same
     * 30-second code cannot be replayed on a concurrent request.
     */
    public function checkTotp(User $user, string $code): bool
    {
        if (empty($user->mfa_secret)) {
            return false;
        }

        $lastStep = $user->mfa_totp_timestep !== null ? (int) $user->mfa_totp_timestep : null;
        $matchedStep = Totp::match((string) $user->mfa_secret, $code, $lastStep);
        if ($matchedStep === null) {
            return false;
        }

        $updated = User::where('id', $user->id)
            ->where(function ($query) use ($matchedStep) {
                $query->whereNull('mfa_totp_timestep')
                    ->orWhere('mfa_totp_timestep', '<', $matchedStep);
            })
            ->update([
                'mfa_totp_timestep' => $matchedStep,
                'mfa_last_used_at' => now(),
            ]);

        if ($updated === 0) {
            return false;
        }

        $user->mfa_totp_timestep = $matchedStep;
        $user->mfa_last_used_at = now();

        return true;
    }

    public function consumeSentCode(User $user, string $purpose, string $code, ?string $expectedDestination = null): bool
    {
        $cacheKey = $this->codeCacheKey($user, $purpose);
        $payload = Cache::get($cacheKey);
        if (!is_array($payload)) {
            return false;
        }

        if (($payload['expires_at'] ?? 0) < now()->timestamp) {
            Cache::forget($cacheKey);
            return false;
        }

        if ($expectedDestination !== null && ($payload['destination'] ?? '') !== trim($expectedDestination)) {
            return false;
        }

        if (!Hash::check($code, (string) ($payload['hash'] ?? ''))) {
            return false;
        }

        Cache::forget($cacheKey);

        return true;
    }

    private function deliverCode(
        User $user,
        string $channel,
        string $destination,
        string $code,
        int $minutes,
        string $purpose
    ): ?string {
        try {
            if ($channel === self::METHOD_SMS) {
                $setting = $this->setting();
                if (!$this->isSmsProviderConfigured($setting)) {
                    return __('two_factor.error_sms_not_configured');
                }

                $brand = Branding::portalTitle();
                $message = __('two_factor.sms_message', [
                    'app' => $brand,
                    'code' => $code,
                    'minutes' => $minutes,
                ]);

                $smsClient = match ($setting->sms_provider) {
                    'twilio' => new TwilioSMS($setting),
                    'aws_sns' => new AwsSnsSMS($setting),
                    'orange_cm' => new OrangeCameroonSMS($setting),
                    'nexah' => new Nexah($setting),
                    default => null,
                };

                if (!$smsClient) {
                    return __('two_factor.error_sms_not_configured');
                }

                $response = $smsClient->sendSMS([
                    'sms' => $message,
                    'mobiles' => $destination,
                ]);

                if ((int) ($response['responsecode'] ?? 0) !== 1) {
                    return (string) ($response['responsemessage'] ?? __('two_factor.error_delivery_failed'));
                }

                return null;
            }

            try {
                setSavedSmtpCredentials();
            } catch (\Throwable) {
                // Fall back to default mailer configuration
            }

            Notification::route('mail', $destination)
                ->notify(new SecurityCodeNotification($code, $minutes, $purpose, $user->name));

            return null;
        } catch (\Throwable $e) {
            report($e);
            return __('two_factor.error_delivery_failed');
        }
    }

    public function mask(string $value, string $channel): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if ($channel === self::METHOD_EMAIL && str_contains($value, '@')) {
            [$local, $domain] = explode('@', $value, 2);
            $visible = substr($local, 0, min(2, strlen($local)));
            return $visible . str_repeat('*', max(2, strlen($local) - strlen($visible))) . '@' . $domain;
        }

        $len = strlen($value);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        return substr($value, 0, 3) . str_repeat('*', max(2, $len - 5)) . substr($value, -2);
    }

    public function methodLabel(?string $method): string
    {
        return match ($method) {
            self::METHOD_AUTHENTICATOR => __('two_factor.method_authenticator'),
            self::METHOD_EMAIL => __('two_factor.method_email'),
            self::METHOD_SMS => __('two_factor.method_sms'),
            default => __('two_factor.status_disabled'),
        };
    }

    private function codeCacheKey(User $user, string $purpose): string
    {
        return "mfa:code:{$purpose}:{$user->getAuthIdentifier()}";
    }

    private function cooldownKey(User $user, string $purpose): string
    {
        return "mfa:cooldown:{$purpose}:{$user->getAuthIdentifier()}";
    }

    private function attemptKey(User $user): string
    {
        return "mfa:attempts:{$user->getAuthIdentifier()}";
    }
}
