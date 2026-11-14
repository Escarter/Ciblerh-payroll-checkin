<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * RFC 6238 / RFC 4226 TOTP helper (SHA-1, 6 digits, 30s period).
 * Compatible with Google Authenticator, Microsoft Authenticator, Authy, 1Password, and Bitwarden.
 */
final class Totp
{
    public const PERIOD = 30;
    public const DIGITS = 6;
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a random 160-bit Base32 secret (32 characters).
     */
    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    /**
     * Current 30-second timestep for the given UNIX timestamp.
     */
    public static function timestep(?int $timestamp = null): int
    {
        return intdiv($timestamp ?? time(), self::PERIOD);
    }

    /**
     * Compute the 6-digit code for a specific timestep.
     */
    public static function codeAt(string $secret, int $timestep): string
    {
        $key = self::base32Decode($secret);
        $counter = pack('N*', 0) . pack('N*', $timestep);
        $hash = hash_hmac('sha1', $counter, $key, true);

        $offset = ord($hash[19]) & 0x0F;
        $binary = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        $otp = $binary % (10 ** self::DIGITS);

        return str_pad((string) $otp, self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Verify a user-supplied code against the secret within +/- $window timesteps.
     * Returns the matched timestep so the caller can record it and reject replays,
     * or null when no timestep matched.
     */
    public static function match(
        string $secret,
        string $code,
        ?int $lastUsedTimestep = null,
        int $window = 1,
        ?int $timestamp = null
    ): ?int {
        $normalized = preg_replace('/\D+/', '', $code) ?? '';
        if (strlen($normalized) !== self::DIGITS) {
            return null;
        }

        $current = self::timestep($timestamp);

        for ($offset = -$window; $offset <= $window; $offset++) {
            $step = $current + $offset;
            if ($lastUsedTimestep !== null && $step <= $lastUsedTimestep) {
                continue;
            }
            if (hash_equals(self::codeAt($secret, $step), $normalized)) {
                return $step;
            }
        }

        return null;
    }

    /**
     * Build an otpauth:// URI suitable for QR encoding.
     */
    public static function provisioningUri(string $issuer, string $accountLabel, string $secret): string
    {
        $cleanIssuer = str_replace(':', '-', trim($issuer)) ?: 'CibleRH';
        $cleanAccount = str_replace(':', '-', trim($accountLabel)) ?: 'user';
        $label = rawurlencode($cleanIssuer) . ':' . rawurlencode($cleanAccount);

        $query = http_build_query([
            'secret' => strtoupper(preg_replace('/\s+/', '', $secret) ?? ''),
            'issuer' => $cleanIssuer,
            'algorithm' => 'SHA1',
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ], '', '&', PHP_QUERY_RFC3986);

        return "otpauth://totp/{$label}?{$query}";
    }

    /**
     * Format a Base32 secret in 4-character groups for manual entry.
     */
    public static function formatSecret(string $secret): string
    {
        $clean = strtoupper(preg_replace('/[^A-Z2-7]/i', '', $secret) ?? '');

        return trim(chunk_split($clean, 4, ' '));
    }

    public static function base32Encode(string $bytes): string
    {
        if ($bytes === '') {
            return '';
        }

        $bits = '';
        $length = strlen($bytes);
        for ($i = 0; $i < $length; $i++) {
            $bits .= str_pad(decbin(ord($bytes[$i])), 8, '0', STR_PAD_LEFT);
        }

        $output = '';
        $chunks = str_split($bits, 5);
        foreach ($chunks as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            }
            $output .= self::ALPHABET[bindec($chunk)];
        }

        return $output;
    }

    public static function base32Decode(string $encoded): string
    {
        $clean = strtoupper(preg_replace('/[^A-Z2-7]/i', '', $encoded) ?? '');
        if ($clean === '') {
            throw new InvalidArgumentException('Empty Base32 secret.');
        }

        $bits = '';
        $length = strlen($clean);
        for ($i = 0; $i < $length; $i++) {
            $pos = strpos(self::ALPHABET, $clean[$i]);
            if ($pos === false) {
                throw new InvalidArgumentException('Invalid Base32 character.');
            }
            $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $output = '';
        $bytes = str_split($bits, 8);
        foreach ($bytes as $byte) {
            if (strlen($byte) < 8) {
                continue;
            }
            $output .= chr(bindec($byte));
        }

        return $output;
    }
}
