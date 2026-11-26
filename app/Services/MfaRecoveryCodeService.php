<?php

namespace App\Services;

use App\Models\MfaRecoveryCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Generates, stores (HMAC-SHA256 hashed), verifies, and consumes single-use MFA recovery codes.
 *
 * Codes are formatted as XXXXX-XXXXX (10 uppercase alphanumeric characters without ambiguous
 * glyphs 0/O/1/I/L) and are only returned in plaintext at generation time.
 */
class MfaRecoveryCodeService
{
    public const COUNT = 10;
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /**
     * Replace all existing recovery codes for the user and return the new plaintext codes.
     *
     * @return array<int, string>
     */
    public function regenerate(User $user, int $count = self::COUNT): array
    {
        $plainCodes = [];
        for ($i = 0; $i < $count; $i++) {
            $plainCodes[] = $this->generatePlainCode();
        }

        $now = now();
        $rows = array_map(fn(string $code) => [
            'user_id' => $user->id,
            'code_hash' => $this->hash($code),
            'used_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $plainCodes);

        DB::transaction(function () use ($user, $rows) {
            MfaRecoveryCode::where('user_id', $user->id)->delete();
            MfaRecoveryCode::insert($rows);
        });

        return $plainCodes;
    }

    /**
     * Atomically verify and mark a recovery code as used.
     */
    public function consume(User $user, string $inputCode): bool
    {
        $normalized = $this->normalize($inputCode);
        if (strlen($normalized) < 8) {
            return false;
        }

        $hash = $this->hash($normalized);

        $affected = MfaRecoveryCode::where('user_id', $user->id)
            ->where('code_hash', $hash)
            ->whereNull('used_at')
            ->update([
                'used_at' => now(),
                'updated_at' => now(),
            ]);

        return $affected > 0;
    }

    /**
     * Count unused recovery codes remaining for the user.
     */
    public function remaining(User $user): int
    {
        return MfaRecoveryCode::where('user_id', $user->id)
            ->whereNull('used_at')
            ->count();
    }

    /**
     * Delete all recovery codes for the user.
     */
    public function forget(User $user): void
    {
        MfaRecoveryCode::where('user_id', $user->id)->delete();
    }

    public function hash(string $code): string
    {
        $normalized = $this->normalize($code);
        $key = (string) config('app.key', 'ciblerh-mfa-recovery');

        return hash_hmac('sha256', $normalized, $key);
    }

    public function normalize(string $code): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code) ?? '');
    }

    private function generatePlainCode(): string
    {
        $alphabetLength = strlen(self::ALPHABET);
        $chars = '';
        for ($i = 0; $i < 10; $i++) {
            $chars .= self::ALPHABET[random_int(0, $alphabetLength - 1)];
        }

        return substr($chars, 0, 5) . '-' . substr($chars, 5, 5);
    }
}
