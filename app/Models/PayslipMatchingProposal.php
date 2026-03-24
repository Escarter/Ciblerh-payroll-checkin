<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayslipMatchingProposal extends Model
{
    use HasUuids, SoftDeletes;

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
}
