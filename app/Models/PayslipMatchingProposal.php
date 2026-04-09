<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Throwable;

class PayslipMatchingProposal extends Model
{
    use HasUuids, SoftDeletes;

    private static ?bool $supportsFingerprintCache = null;

    protected $table = 'payslip_matching_proposals';

    protected $fillable = [
        'file_path',
        'local_file_path',
        'file_name',
        'file_fingerprint',
        'file_size',
        'file_timestamp',
        'proposed_match',
        'matched_to_department_id',
        'matched_to_company_id',
        'matched_month',
        'matched_year',
        'matched_by_user_id',
        'matched_at',
        'processed_at',
        'download_status',
        'download_error',
        'status',
        'is_auto_matched',
        'rejection_reason',
    ];

    protected $casts = [
        'proposed_match' => 'array',
        'file_timestamp' => 'datetime',
        'matched_at' => 'datetime',
        'processed_at' => 'datetime',
        'is_auto_matched' => 'boolean',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_VALIDATED = 'validated';
    const STATUS_PROCESSED = 'processed';
    const STATUS_REJECTED = 'rejected';
    const STATUS_FAILED = 'failed';

    /**
     * Relationships
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'matched_to_department_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'matched_to_company_id');
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'matched_by_user_id');
    }

    /**
     * All SendPayslipProcess records triggered by this SFTP proposal.
     */
    public function sendPayslipProcesses()
    {
        return $this->hasMany(\App\Models\SendPayslipProcess::class, 'sftp_proposal_id');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeValidated($query)
    {
        return $query->where('status', self::STATUS_VALIDATED);
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', self::STATUS_PROCESSED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Get the best match candidate from proposed_match array
     */
    public function getBestCandidate()
    {
        if (empty($this->proposed_match['candidates'])) {
            return null;
        }

        return $this->proposed_match['candidates'][0] ?? null;
    }

    /**
     * Ensure proposed_match is UTF-8 clean before JSON casting.
     *
     * PDF extraction can produce invalid byte sequences that break
     * JSON encoding with "Malformed UTF-8 characters".
     */
    public function setProposedMatchAttribute($value): void
    {
        $sanitized = $this->sanitizeUtf8Recursive($value);

        try {
            $this->attributes['proposed_match'] = json_encode($sanitized, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            // Never block proposal creation due to malformed extracted text.
            $fallback = is_array($sanitized) ? $sanitized : ['value' => (string) $sanitized];
            $this->attributes['proposed_match'] = json_encode($fallback, JSON_UNESCAPED_UNICODE) ?: '{}';
        }
    }

    /**
     * Recursively sanitize strings to valid UTF-8 for JSON encoding.
     */
    private function sanitizeUtf8Recursive($value)
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->sanitizeUtf8Recursive($v);
            }
            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        // Fast path: already valid UTF-8
        if (function_exists('mb_check_encoding') && mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        // Attempt to repair invalid byte sequences
        if (function_exists('mb_convert_encoding')) {
            $converted = @mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            if (is_string($converted)) {
                return $converted;
            }
        }

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
            if (is_string($converted)) {
                return $converted;
            }
        }

        // Last resort: strip non-UTF-8 bytes via regex replacement
        $clean = @preg_replace('/[^\x09\x0A\x0D\x20-\x7E\xC2-\xF4][\x80-\xBF]*/', '', $value);
        return is_string($clean) ? $clean : '';
    }

    /**
     * Whether the current database schema supports the file_fingerprint column.
     *
     * This allows runtime compatibility when code is deployed before migrations.
     */
    public static function supportsFileFingerprint(): bool
    {
        if (self::$supportsFingerprintCache !== null) {
            return self::$supportsFingerprintCache;
        }

        try {
            self::$supportsFingerprintCache = Schema::hasColumn((new self())->getTable(), 'file_fingerprint');
        } catch (Throwable) {
            self::$supportsFingerprintCache = false;
        }

        return self::$supportsFingerprintCache;
    }

    /**
     * Resolve an existing absolute file path for this proposal.
     *
     * Tries current local/file paths first, then sibling lifecycle folders
     * (incoming/processed/failed) using the proposal file name.
     *
     * @param bool $persist When true, persist corrected file_path/local_file_path.
     */
    public function resolveExistingLocalFilePath(bool $persist = true): ?string
    {
        $pathCandidates = array_values(array_unique(array_filter([
            $this->local_file_path,
            $this->file_path,
        ])));

        foreach ($pathCandidates as $candidate) {
            if (is_string($candidate) && file_exists($candidate)) {
                return $this->syncResolvedPath($candidate, $persist);
            }
        }

        $fileName = $this->file_name ?: (!empty($this->local_file_path) ? basename((string) $this->local_file_path) : null);
        if (empty($fileName)) {
            return null;
        }

        $baseDirs = [];
        foreach ($pathCandidates as $knownPath) {
            if (!is_string($knownPath) || trim($knownPath) === '') {
                continue;
            }

            $dir = dirname($knownPath);
            $last = basename($dir);
            $baseDirs[] = in_array($last, ['incoming', 'processed', 'failed'], true)
                ? dirname($dir)
                : $dir;
        }

        $baseDirs = array_values(array_unique(array_filter($baseDirs)));

        foreach ($baseDirs as $baseDir) {
            foreach (['incoming', 'processed', 'failed'] as $folder) {
                $candidate = rtrim($baseDir, '/') . '/' . $folder . '/' . $fileName;
                if (file_exists($candidate)) {
                    return $this->syncResolvedPath($candidate, $persist);
                }
            }
        }

        return null;
    }

    private function syncResolvedPath(string $resolvedPath, bool $persist): string
    {
        if ($persist && ($this->local_file_path !== $resolvedPath || $this->file_path !== $resolvedPath)) {
            $this->update([
                'local_file_path' => $resolvedPath,
                'file_path' => $resolvedPath,
            ]);
        }

        return $resolvedPath;
    }
}
