<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DownloadJob extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'user_id', 'job_type', 'report_format', 'filters', 'report_config',
        'status', 'total_records', 'processed_records', 'failed_records',
        'file_path', 'file_name', 'file_size', 'mime_type',
        'error_message', 'error_details', 'started_at', 'completed_at', 'expires_at', 'metadata'
    ];

    protected $casts = [
        'filters' => 'array',
        'report_config' => 'array',
        'error_details' => 'array',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
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

    // Job Types Constants
    const TYPE_BULK_PAYSLIP_DOWNLOAD = 'bulk_payslip_download';
    const TYPE_PAYSLIP_REPORT = 'payslip_report';
    const TYPE_OVERTIME_REPORT = 'overtime_report';
    const TYPE_CHECKLOG_REPORT = 'checklog_report';
    const TYPE_EMPLOYEE_EXPORT = 'employee_export';
    const TYPE_SERVICE_EXPORT = 'service_export';
    const TYPE_COMPANY_EXPORT = 'company_export';
    const TYPE_DEPARTMENT_EXPORT = 'department_export';
    const TYPE_ADVANCE_SALARY_EXPORT = 'advance_salary_export';
    const TYPE_ABSENCES_EXPORT = 'absences_export';

    // Status Constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    // Report Format Constants
    const FORMAT_XLSX = 'xlsx';
    const FORMAT_PDF = 'pdf';
    const FORMAT_ZIP = 'zip';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_records == 0) return 0;
        return round(($this->processed_records / $this->total_records) * 100);
    }

    public function getFormattedFileSizeAttribute(): ?string
    {
        if (!$this->file_size) return null;
        return $this->formatBytes($this->file_size);
    }

    public function getJobTypeDisplayAttribute(): string
    {
        return match($this->job_type) {
            self::TYPE_BULK_PAYSLIP_DOWNLOAD => __('reports.bulk_payslip_download'),
            self::TYPE_PAYSLIP_REPORT => __('reports.payslip_report'),
            self::TYPE_OVERTIME_REPORT => __('reports.overtime_report'),
            self::TYPE_CHECKLOG_REPORT => __('reports.checklog_report'),
            self::TYPE_EMPLOYEE_EXPORT => __('reports.employee_export'),
            self::TYPE_SERVICE_EXPORT => __('reports.service_export'),
            self::TYPE_COMPANY_EXPORT => __('reports.company_export'),
            self::TYPE_DEPARTMENT_EXPORT => __('reports.department_export'),
            self::TYPE_ADVANCE_SALARY_EXPORT => __('reports.advance_salary_export'),
            self::TYPE_ABSENCES_EXPORT => __('reports.absences_export'),
            default => __('reports.unknown_report_type')
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
            self::STATUS_PROCESSING => __('common.processing'),
            self::STATUS_COMPLETED => __('common.completed'),
            self::STATUS_FAILED => __('common.failed'),
            self::STATUS_CANCELLED => __('common.cancelled'),
            default => __('common.unknown')
        };
    }

    public function getDurationAttribute(): ?string
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        $duration = $this->started_at->diffInSeconds($this->completed_at);
        
        if ($duration < 60) {
            return $duration . ' ' . __('common.seconds');
        } elseif ($duration < 3600) {
            return round($duration / 60) . ' ' . __('common.minutes');
        } else {
            return round($duration / 3600) . ' ' . __('common.hours');
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

    public function canBeDownloaded(): bool
    {
        return $this->isCompleted() && !empty($this->file_path);
    }

    public function canBeDeleted(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED, self::STATUS_CANCELLED]);
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, $precision) . ' ' . $units[$i];
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

    public function scopeByJobType($query, $jobType)
    {
        return $query->where('job_type', $jobType);
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

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }
}