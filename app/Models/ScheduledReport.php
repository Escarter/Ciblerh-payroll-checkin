<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class ScheduledReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'name', 'job_type', 'report_format', 'filters', 'report_config',
        'recipients', 'frequency', 'day_of_month', 'time', 'timezone',
        'is_active', 'last_run_at', 'next_run_at', 'run_count', 'last_error'
    ];

    protected $casts = [
        'filters' => 'array',
        'report_config' => 'array',
        'recipients' => 'array',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    const FREQUENCY_MONTHLY = 'monthly';
    const FREQUENCY_WEEKLY = 'weekly';
    const FREQUENCY_DAILY = 'daily';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate the next run date based on frequency
     */
    public function calculateNextRun(): Carbon
    {
        $now = Carbon::now($this->timezone);
        
        switch ($this->frequency) {
            case self::FREQUENCY_MONTHLY:
                $timeParts = explode(':', $this->time);
                $hour = isset($timeParts[0]) ? (int)$timeParts[0] : 9;
                $minute = isset($timeParts[1]) ? (int)$timeParts[1] : 0;
                
                $nextRun = Carbon::create($now->year, $now->month, $this->day_of_month, 
                    $hour, 
                    $minute, 
                    0, 
                    $this->timezone);
                
                // If the day has passed this month, move to next month
                if ($nextRun->isPast()) {
                    $nextRun->addMonth();
                }
                break;
                
            case self::FREQUENCY_WEEKLY:
                $timeParts = explode(':', $this->time);
                $hour = isset($timeParts[0]) ? (int)$timeParts[0] : 9;
                $minute = isset($timeParts[1]) ? (int)$timeParts[1] : 0;
                
                // For weekly, run next week same day
                $nextRun = $now->copy()->addWeek()
                    ->setTime($hour, $minute, 0);
                break;
                
            case self::FREQUENCY_DAILY:
            default:
                $timeParts = explode(':', $this->time);
                $hour = isset($timeParts[0]) ? (int)$timeParts[0] : 9;
                $minute = isset($timeParts[1]) ? (int)$timeParts[1] : 0;
                
                $nextRun = Carbon::create($now->year, $now->month, $now->day, 
                    $hour, 
                    $minute, 
                    0, 
                    $this->timezone);
                
                // If time has passed today, move to tomorrow
                if ($nextRun->isPast()) {
                    $nextRun->addDay();
                }
                break;
        }
        
        return $nextRun;
    }

    /**
     * Check if the report should run now
     */
    public function shouldRunNow(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->next_run_at) {
            return false;
        }

        $now = Carbon::now($this->timezone);
        return $now->greaterThanOrEqualTo($this->next_run_at);
    }

    /**
     * Update next run date after execution
     */
    public function updateNextRun(): void
    {
        $this->update([
            'last_run_at' => now(),
            'next_run_at' => $this->calculateNextRun(),
            'run_count' => $this->run_count + 1,
        ]);
    }

    /**
     * Get job type display name
     */
    public function getJobTypeDisplayAttribute(): string
    {
        return match($this->job_type) {
            \App\Models\DownloadJob::TYPE_PAYSLIP_REPORT => __('reports.payslip_report'),
            \App\Models\DownloadJob::TYPE_OVERTIME_REPORT => __('reports.overtime_report'),
            \App\Models\DownloadJob::TYPE_CHECKLOG_REPORT => __('reports.checklog_report'),
            \App\Models\DownloadJob::TYPE_EMPLOYEE_EXPORT => __('reports.employee_export'),
            \App\Models\DownloadJob::TYPE_SERVICE_EXPORT => __('reports.service_export'),
            \App\Models\DownloadJob::TYPE_COMPANY_EXPORT => __('reports.company_export'),
            \App\Models\DownloadJob::TYPE_DEPARTMENT_EXPORT => __('reports.department_export'),
            \App\Models\DownloadJob::TYPE_ADVANCE_SALARY_EXPORT => __('reports.advance_salary_export'),
            \App\Models\DownloadJob::TYPE_ABSENCES_EXPORT => __('reports.absences_export'),
            \App\Models\DownloadJob::TYPE_BULK_PAYSLIP_DOWNLOAD => __('reports.bulk_payslip_download'),
            default => __('reports.unknown_report_type')
        };
    }

    /**
     * Scope for active scheduled reports
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for reports that should run
     */
    public function scopeDueToRun($query)
    {
        return $query->active()
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now());
    }
}
