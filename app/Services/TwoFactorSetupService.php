<?php

namespace App\Services;

use App\Mail\TwoFactorChangedMail;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

/**
 * Handles enrolling, switching, disabling, and admin-resetting a user's
 * two-step verification method, including recovery codes and security alerts.
 */
class TwoFactorSetupService
{
    public function __construct(
        private readonly TwoFactorService $twoFactor,
        private readonly MfaRecoveryCodeService $recoveryCodes,
        private readonly MfaSecurityEventService $events,
        private readonly TrustedDeviceService $trustedDevices
    ) {}

    /**
     * Begin Authenticator App setup: generate a fresh Base32 secret in the session
     * and return the QR SVG data URI + formatted manual key.
     *
     * @return array{method:string,secret:string,formatted_secret:string,uri:string,qr_svg:?string}
     */
    public function beginAuthenticator(User $user): array
    {
        $existing = Session::get(TwoFactorService::SESSION_PENDING_SETUP);
        if (
            is_array($existing)
            && ($existing['method'] ?? null) === TwoFactorService::METHOD_AUTHENTICATOR
            && !empty($existing['secret'])
        ) {
            $secret = (string) $existing['secret'];
        } else {
            $secret = Totp::generateSecret();
            Session::put(TwoFactorService::SESSION_PENDING_SETUP, [
                'method' => TwoFactorService::METHOD_AUTHENTICATOR,
                'secret' => $secret,
                'started_at' => now()->timestamp,
            ]);
        }

        $uri = $this->twoFactor->buildProvisioningUri($user, $secret);

        return [
            'method' => TwoFactorService::METHOD_AUTHENTICATOR,
            'secret' => $secret,
            'formatted_secret' => Totp::formatSecret($secret),
            'uri' => $uri,
            'qr_svg' => $this->twoFactor->buildQrSvgDataUri($uri),
        ];
    }

    /**
     * Begin Email OTP setup and send a 6-digit verification code to the user's email.
     *
     * @return array{ok:bool,method:string,destination:string,masked:string,error:?string}
     */
    public function beginEmail(User $user): array
    {
        $email = trim((string) $user->email);
        if ($email === '') {
            return [
                'ok' => false,
                'method' => TwoFactorService::METHOD_EMAIL,
                'destination' => '',
                'masked' => '',
                'error' => __('two_factor.error_missing_email'),
            ];
        }

        $send = $this->twoFactor->sendCode($user, TwoFactorService::METHOD_EMAIL, 'setup', $email);
        if (!$send['sent'] && $send['cooldown'] <= 0) {
            return [
                'ok' => false,
                'method' => TwoFactorService::METHOD_EMAIL,
                'destination' => $email,
                'masked' => $send['masked'],
                'error' => $send['error'],
            ];
        }

        Session::put(TwoFactorService::SESSION_PENDING_SETUP, [
            'method' => TwoFactorService::METHOD_EMAIL,
            'destination' => $email,
            'masked' => $send['masked'],
            'started_at' => now()->timestamp,
        ]);

        return [
            'ok' => true,
            'method' => TwoFactorService::METHOD_EMAIL,
            'destination' => $email,
            'masked' => $send['masked'],
            'error' => null,
        ];
    }

    /**
     * Begin SMS OTP setup and send a 6-digit verification code to the supplied phone number.
     *
     * @return array{ok:bool,method:string,destination:string,masked:string,error:?string}
     */
    public function beginSms(User $user, string $phone): array
    {
        $cleanPhone = preg_replace('/[^\d+]/', '', trim($phone)) ?? '';
        if (strlen($cleanPhone) < 7) {
            return [
                'ok' => false,
                'method' => TwoFactorService::METHOD_SMS,
                'destination' => $cleanPhone,
                'masked' => '',
                'error' => __('two_factor.Please enter a valid mobile phone number.'),
            ];
        }

        $send = $this->twoFactor->sendCode($user, TwoFactorService::METHOD_SMS, 'setup', $cleanPhone);
        if (!$send['sent'] && $send['cooldown'] <= 0) {
            return [
                'ok' => false,
                'method' => TwoFactorService::METHOD_SMS,
                'destination' => $cleanPhone,
                'masked' => $send['masked'],
                'error' => $send['error'],
            ];
        }

        Session::put(TwoFactorService::SESSION_PENDING_SETUP, [
            'method' => TwoFactorService::METHOD_SMS,
            'destination' => $cleanPhone,
            'masked' => $send['masked'],
            'started_at' => now()->timestamp,
        ]);

        return [
            'ok' => true,
            'method' => TwoFactorService::METHOD_SMS,
            'destination' => $cleanPhone,
            'masked' => $send['masked'],
            'error' => null,
        ];
    }

    /**
     * Resend the setup verification code for the currently pending Email or SMS enrollment.
     *
     * @return array{ok:bool,masked:string,error:?string}
     */
    public function resend(User $user): array
    {
        $pending = $this->pending();
        if (!$pending || !in_array($pending['method'] ?? '', [TwoFactorService::METHOD_EMAIL, TwoFactorService::METHOD_SMS], true)) {
            return [
                'ok' => false,
                'masked' => '',
                'error' => __('two_factor.Please select a verification method first.'),
            ];
        }

        $send = $this->twoFactor->sendCode(
            $user,
            (string) $pending['method'],
            'setup',
            (string) ($pending['destination'] ?? '')
        );

        return [
            'ok' => $send['sent'],
            'masked' => $send['masked'],
            'error' => $send['error'],
        ];
    }

    /**
     * Confirm the pending setup with the 6-digit code entered by the user.
     * Generates a fresh set of 10 single-use recovery codes when MFA is enabled or switched.
     *
     * @return array{ok:bool,method:?string,error:?string,recovery_codes?:array<int,string>}
     */
    public function confirm(User $user, string $code): array
    {
        $pending = $this->pending();
        if (!$pending || empty($pending['method'])) {
            return [
                'ok' => false,
                'method' => null,
                'error' => __('two_factor.Please choose a verification method to set up.'),
            ];
        }

        $clean = preg_replace('/\D+/', '', $code) ?? '';
        if (strlen($clean) !== 6) {
            return [
                'ok' => false,
                'method' => $pending['method'],
                'error' => __('two_factor.Please enter the 6-digit verification code.'),
            ];
        }

        $method = (string) $pending['method'];
        $wasEnabled = (bool) $user->mfa_enabled;

        if ($method === TwoFactorService::METHOD_AUTHENTICATOR) {
            $secret = (string) ($pending['secret'] ?? '');
            $matchedStep = $secret !== '' ? Totp::match($secret, $clean) : null;
            if ($matchedStep === null) {
                return [
                    'ok' => false,
                    'method' => $method,
                    'error' => __('two_factor.That code did not match. Make sure your phone time is automatic and try the latest 6-digit code.'),
                ];
            }

            $user->forceFill([
                'mfa_enabled' => true,
                'mfa_method' => TwoFactorService::METHOD_AUTHENTICATOR,
                'mfa_secret' => $secret,
                'mfa_totp_timestep' => $matchedStep,
                'mfa_confirmed_at' => now(),
                'mfa_last_used_at' => now(),
            ])->save();
        } elseif (in_array($method, [TwoFactorService::METHOD_EMAIL, TwoFactorService::METHOD_SMS], true)) {
            $destination = (string) ($pending['destination'] ?? '');
            if (!$this->twoFactor->consumeSentCode($user, 'setup', $clean, $destination)) {
                return [
                    'ok' => false,
                    'method' => $method,
                    'error' => __('two_factor.Invalid or expired verification code. Please check the latest message or request a new code.'),
                ];
            }

            $attributes = [
                'mfa_enabled' => true,
                'mfa_method' => $method,
                'mfa_secret' => null,
                'mfa_totp_timestep' => null,
                'mfa_confirmed_at' => now(),
                'mfa_last_used_at' => now(),
            ];

            if ($method === TwoFactorService::METHOD_SMS) {
                $attributes['mfa_phone'] = $destination;
            }

            $user->forceFill($attributes)->save();
        } else {
            return [
                'ok' => false,
                'method' => null,
                'error' => __('two_factor.Unsupported verification method.'),
            ];
        }

        // Generate 10 single-use backup recovery codes & flash them to the session for display
        $plainRecoveryCodes = $this->recoveryCodes->regenerate($user);
        Session::put(TwoFactorService::SESSION_FLASH_RECOVERY_CODES, $plainRecoveryCodes);

        // Switching MFA method invalidates previously trusted devices
        if ($wasEnabled) {
            $this->trustedDevices->forgetAll($user);
        }

        Session::forget(TwoFactorService::SESSION_PENDING_SETUP);
        $this->twoFactor->markVerified($user);

        $eventType = $wasEnabled
            ? MfaSecurityEventService::EVENT_MFA_METHOD_CHANGED
            : MfaSecurityEventService::EVENT_MFA_ENABLED;
        $this->events->log($user, $eventType, 'success', $method);

        auditLog(
            Auth::user() ?? $user,
            'user_mfa',
            'web',
            __('two_factor.audit_enabled', ['user' => $user->name, 'method' => $method])
        );

        $this->notifyUserChange(
            $user,
            $wasEnabled ? 'method_changed' : 'enabled',
            $this->twoFactor->methodLabel($method)
        );

        return [
            'ok' => true,
            'method' => $method,
            'error' => null,
            'recovery_codes' => $plainRecoveryCodes,
        ];
    }

    /**
     * Regenerate the 10 backup recovery codes for an already-enrolled user.
     *
     * @return array<int, string>
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        $codes = $this->recoveryCodes->regenerate($user);
        Session::put(TwoFactorService::SESSION_FLASH_RECOVERY_CODES, $codes);

        $this->events->log($user, MfaSecurityEventService::EVENT_RECOVERY_REGENERATED, 'info', $user->mfa_method);

        auditLog(
            Auth::user() ?? $user,
            'user_mfa',
            'web',
            __('two_factor.audit_recovery_regenerated', ['user' => $user->name])
        );

        $this->notifyUserChange($user, 'codes_regenerated', $this->twoFactor->methodLabel($user->mfa_method));

        return $codes;
    }

    /**
     * Return any pending setup payload stored in the session.
     */
    public function pending(): ?array
    {
        $pending = Session::get(TwoFactorService::SESSION_PENDING_SETUP);

        return is_array($pending) ? $pending : null;
    }

    /**
     * Rebuild the QR code & formatted key if the user is mid-way through Authenticator setup.
     */
    public function pendingAuthenticator(User $user): ?array
    {
        $pending = $this->pending();
        if (!$pending || ($pending['method'] ?? null) !== TwoFactorService::METHOD_AUTHENTICATOR) {
            return null;
        }

        return $this->beginAuthenticator($user);
    }

    public function cancel(): void
    {
        Session::forget(TwoFactorService::SESSION_PENDING_SETUP);
        Session::forget(TwoFactorService::SESSION_PENDING_TEST);
    }

    /**
     * Turn off MFA for a user (blocked if global or per-user policy requires MFA).
     *
     * @return array{ok:bool,error:?string}
     */
    public function disable(User $user): array
    {
        if ($this->twoFactor->isRequiredFor($user)) {
            return [
                'ok' => false,
                'error' => __('two_factor.Two-step verification is required for your account. You can switch to a different method instead.'),
            ];
        }

        $previousMethod = $user->mfa_method;

        $user->forceFill([
            'mfa_enabled' => false,
            'mfa_method' => null,
            'mfa_secret' => null,
            'mfa_totp_timestep' => null,
            'mfa_confirmed_at' => null,
        ])->save();

        $this->recoveryCodes->forget($user);
        $this->trustedDevices->forgetAll($user);
        $this->twoFactor->forgetVerification();

        $this->events->log($user, MfaSecurityEventService::EVENT_MFA_DISABLED, 'warning', $previousMethod);

        auditLog(
            Auth::user() ?? $user,
            'user_mfa',
            'web',
            __('two_factor.audit_disabled', ['user' => $user->name])
        );

        $this->notifyUserChange($user, 'disabled');

        return ['ok' => true, 'error' => null];
    }

    /**
     * Administrator reset: clears a user's MFA enrollment so they can set it up fresh on next sign-in.
     */
    public function resetFor(User $target, ?User $actor = null): void
    {
        $previousMethod = $target->mfa_method;

        $target->forceFill([
            'mfa_enabled' => false,
            'mfa_method' => null,
            'mfa_secret' => null,
            'mfa_totp_timestep' => null,
            'mfa_confirmed_at' => null,
        ])->save();

        $this->recoveryCodes->forget($target);
        $this->trustedDevices->forgetAll($target);

        $this->events->log(
            $target,
            MfaSecurityEventService::EVENT_MFA_RESET_BY_ADMIN,
            'warning',
            $previousMethod,
            null,
            [],
            $actor?->id
        );

        auditLog(
            $actor ?? Auth::user(),
            'user_mfa',
            'web',
            __('two_factor.audit_reset_by_admin', ['user' => $target->name])
        );

        $this->notifyUserChange($target, 'reset');
    }

    /**
     * Administrator toggle for requiring MFA on a specific user account.
     */
    public function setRequired(User $target, bool $required, ?User $actor = null): void
    {
        $target->forceFill(['mfa_required' => $required])->save();

        $this->events->log(
            $target,
            MfaSecurityEventService::EVENT_MFA_REQUIRED_CHANGED,
            'info',
            $target->mfa_method,
            $required ? 'MFA marked required' : 'MFA marked optional',
            ['required' => $required],
            $actor?->id
        );

        auditLog(
            $actor ?? Auth::user(),
            'user_mfa',
            'web',
            __('two_factor.audit_requirement_changed', [
                'user' => $target->name,
                'state' => $required ? 'required' : 'optional',
            ])
        );
    }

    /**
     * Send an email alert when a user's two-step verification settings change.
     */
    private function notifyUserChange(User $user, string $changeType, ?string $methodLabel = null): void
    {
        if (empty($user->email)) {
            return;
        }

        try {
            $request = request();
            Mail::to($user->email)->send(new TwoFactorChangedMail(
                $user,
                $changeType,
                $methodLabel,
                $request?->ip(),
                $request ? substr((string) $request->userAgent(), 0, 255) : null
            ));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
