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
            'push_scan_frequency' => $config['sftp_push_scan_frequency'] ?? 'everyFiveMinutes',
            'push_scan_days' => $config['sftp_push_scan_days'] ?? '',
            'push_scan_time' => $config['sftp_push_scan_time'] ?? '00:00',
            'push_archive_frequency' => $config['sftp_push_archive_frequency'] ?? null,
            'push_archive_days' => $config['sftp_push_archive_days'] ?? null,
            'push_archive_time' => $config['sftp_push_archive_time'] ?? null,
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
     * Get SFTP auto-match configuration
     */
    public static function getSftpAutoMatchConfig(): array
    {
        $config = self::getConfiguration();

        return [
            'enabled' => $config['sftp_auto_match_enabled'] ?? false,
            'threshold' => (int) ($config['sftp_auto_match_threshold'] ?? 80),
            'min_strategy' => $config['sftp_auto_match_min_strategy'] ?? 'reverse_partial_match',
            'notification_email' => $config['sftp_auto_match_notification_email'] ?? null,
        ];
    }

    /**
     * Strategy quality rank map (higher = better quality).
     */
    private const STRATEGY_RANK = [
        'partial_match'         => 3,
        'reverse_partial_match' => 2,
        'fuzzy'                 => 1,
    ];

    /**
     * Return true when the candidate's confidence and strategy both meet the
     * configured auto-match thresholds.
     *
     * @param array $candidate  A single entry from matchCompanyFuzzy() results
     * @param array $config     Result of getSftpAutoMatchConfig()
     */
    public static function canAutoMatch(array $candidate, array $config): bool
    {
        $confidencePct = ($candidate['confidence'] ?? 0) * 100;
        if ($confidencePct < $config['threshold']) {
            return false;
        }

        $candidateRank = self::STRATEGY_RANK[$candidate['strategy'] ?? ''] ?? 0;
        $minRank       = self::STRATEGY_RANK[$config['min_strategy'] ?? ''] ?? 0;

        return $candidateRank >= $minRank;
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
                'sftp_push_scan_frequency' => $setting->sftp_push_scan_frequency ?? 'everyFiveMinutes',
                'sftp_push_scan_days' => $setting->sftp_push_scan_days ?? '',
                'sftp_push_scan_time' => $setting->sftp_push_scan_time ?? '00:00',
                'sftp_push_archive_frequency' => $setting->sftp_push_archive_frequency ?? null,
                'sftp_push_archive_days' => $setting->sftp_push_archive_days ?? null,
                'sftp_push_archive_time' => $setting->sftp_push_archive_time ?? null,
                'sftp_matching_strategies' => $setting->sftp_matching_strategies ?? [],
                'sftp_auto_match_enabled' => $setting->sftp_auto_match_enabled ?? false,
                'sftp_auto_match_threshold' => $setting->sftp_auto_match_threshold ?? 80,
                'sftp_auto_match_min_strategy' => $setting->sftp_auto_match_min_strategy ?? 'reverse_partial_match',
                'sftp_auto_match_notification_email' => $setting->sftp_auto_match_notification_email ?? null,
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
