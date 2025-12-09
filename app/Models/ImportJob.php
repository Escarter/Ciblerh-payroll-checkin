<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ImportJob extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'user_id', 'import_type', 'company_id', 'department_id',
        'file_name', 'file_path', 'status', 'total_rows', 'processed_rows',
        'successful_imports', 'failed_imports', 'error_message', 'error_details',
        'import_config', 'started_at', 'completed_at'
    ];

    protected $casts = [
        'error_details' => 'array',
        'import_config' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    // Import Types Constants
    const TYPE_EMPLOYEES = 'employees';
    const TYPE_DEPARTMENTS = 'departments';
    const TYPE_COMPANIES = 'companies';
    const TYPE_SERVICES = 'services';
    const TYPE_LEAVE_TYPES = 'leave_types';

    // Status Constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_rows == 0) return 0;
        return round(($this->processed_rows / $this->total_rows) * 100);
    }

    public function getImportTypeDisplayAttribute(): string
    {
        return match($this->import_type) {
            self::TYPE_EMPLOYEES => __('common.employees'),
            self::TYPE_DEPARTMENTS => __('common.departments'),
            self::TYPE_COMPANIES => __('common.companies'),
            self::TYPE_SERVICES => __('common.services'),
            self::TYPE_LEAVE_TYPES => __('common.leave_types'),
            default => __('Unknown Import Type')
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_PROCESSING => 'info',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_CANCELLED => 'secondary',
            default => 'secondary'
        };
    }

    public function getStatusDisplayAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => __('common.pending'),
            self::STATUS_PROCESSING => __('Processing'),
            self::STATUS_COMPLETED => __('Completed'),
            self::STATUS_FAILED => __('common.failed'),
            self::STATUS_CANCELLED => __('Cancelled'),
            default => __('Unknown')
        };
    }

    public function getDurationAttribute(): ?string
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        $duration = $this->started_at->diffInSeconds($this->completed_at);

        if ($duration < 60) {
            return $duration . ' ' . __('seconds');
        } elseif ($duration < 3600) {
            return round($duration / 60) . ' ' . __('minutes');
        } else {
            return round($duration / 3600) . ' ' . __('hours');
        }
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByImportType($query, $importType)
    {
        return $query->where('import_type', $importType);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }
}
