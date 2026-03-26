<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CredentialToken extends Model
{
    protected $table = 'credential_tokens';
    
    public $timestamps = false;
    
    protected $fillable = [
        'user_id',
        'token',
        'password',
        'created_at',
        'expires_at',
        'used',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'expires_at' => 'datetime',
        'used' => 'boolean',
    ];

    /**
     * Relationship to user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a new credential token for a user
     */
    public static function createForUser(User $user, string $plainPassword, int $expiresInHours = 24): self
    {
        // Delete any existing unexpired tokens for this user
        self::where('user_id', $user->id)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->delete();

        return self::create([
            'user_id' => $user->id,
            'token' => Str::random(64),
            'password' => $plainPassword,
            'created_at' => now(),
            'expires_at' => now()->addHours($expiresInHours),
            'used' => false,
        ]);
    }

    /**
     * Find token and check if valid
     */
    public static function getValid(string $token): ?self
    {
        return self::where('token', $token)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Mark token as used and return password before cleanup
     */
    public function getPasswordAndMarkUsed(): ?string
    {
        $password = $this->password;
        $this->update(['used' => true]);
        return $password;
    }

    /**
     * Cleanup expired tokens (run via scheduler)
     */
    public static function cleanupExpired(): void
    {
        self::where('expires_at', '<', now())->delete();
    }
}
