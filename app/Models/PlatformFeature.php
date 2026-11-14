<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PlatformFeature extends Model
{
    use HasFactory;

    protected $table = 'platform_features';

    protected $guarded = [];

    protected $casts = [
        'is_enabled' => 'boolean',
        'metadata' => 'json',
    ];

    public const CACHE_KEY = 'platform.features.all';

    protected static function booted()
    {
        static::saved(function () {
            Cache::forget(self::CACHE_KEY);
        });

        static::deleted(function () {
            Cache::forget(self::CACHE_KEY);
        });
    }

    /**
     * Get all features cached as key => boolean.
     */
    public static function featureMap(): array
    {
        return Cache::remember(self::CACHE_KEY, 3600, function () {
            try {
                return static::pluck('is_enabled', 'key')->toArray();
            } catch (\Throwable $e) {
                return [];
            }
        });
    }

    /**
     * Check if a feature is enabled.
     * Superadmins always bypass feature disabled checks.
     */
    public static function isEnabled(string $key, bool $allowSuperadminBypass = true): bool
    {
        if ($allowSuperadminBypass && auth()->check() && auth()->user()->hasRole('superadmin')) {
            return true;
        }

        $features = self::featureMap();

        // If the feature key is not yet recorded, default to true so it doesn't break unexpected calls.
        return array_key_exists($key, $features) ? (bool) $features[$key] : true;
    }

    /**
     * Check if a feature is strictly enabled globally (regardless of who is viewing).
     */
    public static function isGloballyEnabled(string $key): bool
    {
        return self::isEnabled($key, false);
    }
}
