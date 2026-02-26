<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class FeatureConfigurationService
{
    const CACHE_KEY = 'feature_configuration';
    const CACHE_TTL = 3600; // 1 hour

    /**
     * Get inactivity deactivation configuration
     */
    public static function getDeactivationConfig(): array
    {
        $config = self::getConfiguration();

        return [
            'enabled' => $config['inactivity_deactivation_enabled'] ?? false,
            'months_threshold' => $config['inactivity_months_threshold'] ?? 6,
            'check_time' => $config['deactivation_check_time'] ?? '02:00',
        ];
    }

    /**
     * Get SFTP configuration
     */
    public static function getSftpConfig(): array
    {
        $config = self::getConfiguration();

        return [
            'enabled' => $config['sftp_sync_enabled'] ?? false,
            'host' => $config['sftp_host'] ?? null,
            'port' => $config['sftp_port'] ?? 22,
            'username' => $config['sftp_username'] ?? null,
            'password' => $config['sftp_password'] ?? null,
            'private_key_path' => $config['sftp_private_key_path'] ?? null,
            'passphrase' => $config['sftp_passphrase'] ?? null,
            'root' => $config['sftp_root'] ?? '/payslips',
            'auth_type' => $config['sftp_auth_type'] ?? 'password',
            'sync_frequency' => $config['sftp_sync_frequency'] ?? 'daily',
            'matching_strategies' => $config['sftp_matching_strategies'] ?? [],
        ];
    }

    /**
     * Check if inactivity deactivation is enabled
     */
    public static function isDeactivationEnabled(): bool
    {
        return self::getDeactivationConfig()['enabled'];
    }

    /**
     * Check if SFTP sync is enabled
     */
    public static function isSftpSyncEnabled(): bool
    {
        return self::getSftpConfig()['enabled'];
    }

    /**
     * Update configuration
     */
    public static function updateConfiguration(array $data): void
    {
        $setting = Setting::firstOrCreate(['company_id' => 1]);
        $setting->update($data);

        // Invalidate cache
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Get all feature configurations
     */
    public static function getConfiguration(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $setting = Setting::first();

            return [
                'inactivity_deactivation_enabled' => $setting->inactivity_deactivation_enabled ?? false,
                'inactivity_months_threshold' => $setting->inactivity_months_threshold ?? 6,
                'deactivation_check_time' => $setting->deactivation_check_time ?? '02:00',
                'sftp_sync_enabled' => $setting->sftp_sync_enabled ?? false,
                'sftp_host' => $setting->sftp_host ?? null,
                'sftp_port' => $setting->sftp_port ?? 22,
                'sftp_username' => $setting->sftp_username ?? null,
                'sftp_password' => $setting->sftp_password ?? null,
                'sftp_private_key_path' => $setting->sftp_private_key_path ?? null,
                'sftp_passphrase' => $setting->sftp_passphrase ?? null,
                'sftp_root' => $setting->sftp_root ?? '/payslips',
                'sftp_auth_type' => $setting->sftp_auth_type ?? 'password',
                'sftp_sync_frequency' => $setting->sftp_sync_frequency ?? 'daily',
                'sftp_matching_strategies' => $setting->sftp_matching_strategies ?? [],
            ];
        });
    }

    /**
     * Validate SFTP configuration
     */
    public static function validateSftpConfiguration(): bool
    {
        $config = self::getSftpConfig();

        if (!$config['enabled']) {
            return false;
        }

        return !empty($config['host']) && !empty($config['username']);
    }

    /**
     * Clear configuration cache
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
