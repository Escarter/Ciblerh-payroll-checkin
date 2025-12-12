<?php

namespace App\Jobs\DownloadJobs;

use App\Models\DownloadJob;
use App\Models\Payslip;
use App\Exports\PayslipReportExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class PayslipReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The queue connection name
     */
    public $queue = 'processing';

    protected $downloadJob;

    /**
     * Create a new job instance.
     */
    public function __construct(DownloadJob $downloadJob)
    {
        $this->downloadJob = $downloadJob;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Update job status to processing
            $this->downloadJob->update([
                'status' => DownloadJob::STATUS_PROCESSING,
                'started_at' => now()
            ]);

            // Get total records count
            $totalRecords = $this->getTotalRecords();
            $this->downloadJob->update(['total_records' => $totalRecords]);

            // Generate filename
            $filename = $this->generateFilename();
            $filePath = 'reports/' . $this->downloadJob->user_id . '/' . $filename;

            // Create export instance with filters
            $export = new PayslipReportExport(
                $this->downloadJob->filters['selectedCompanyId'] ?? null,
                $this->downloadJob->filters['selectedDepartmentId'] ?? null,
                $this->downloadJob->filters['employee_id'] ?? 'all',
                $this->downloadJob->filters['start_date'] ?? null,
                $this->downloadJob->filters['end_date'] ?? null,
                $this->downloadJob->filters['email_status'] ?? null,
                $this->downloadJob->filters['sms_status'] ?? null,
                $this->downloadJob->filters['query_string'] ?? '',
                $this->downloadJob->user
            );

            // Generate the Excel file
            Excel::store($export, $filePath, 'public');

            // Update job completion
            $this->downloadJob->update([
                'status' => DownloadJob::STATUS_COMPLETED,
                'completed_at' => now(),
                'file_path' => $filePath,
                'file_name' => $filename,
                'file_size' => Storage::disk('public')->size($filePath),
                'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'processed_records' => $totalRecords
            ]);

            // Log success
            \Log::info("Payslip report generated successfully", [
                'job_id' => $this->downloadJob->id,
                'filename' => $filename,
                'records' => $totalRecords
            ]);

        } catch (\Exception $e) {
            // Update job with error
            $this->downloadJob->update([
                'status' => DownloadJob::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'error_details' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ],
                'completed_at' => now()
            ]);

            \Log::error("Payslip report generation failed", [
                'job_id' => $this->downloadJob->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Get total records count for progress tracking
     */
    private function getTotalRecords(): int
    {
        $query = Payslip::query()
            ->when(!empty($this->downloadJob->filters['query_string']), function ($q) {
                $queryString = $this->downloadJob->filters['query_string'];
                return $q->where('first_name', 'like', '%' . $queryString . '%')
                    ->orWhere('last_name', 'like', '%' . $queryString . '%')
                    ->orWhere('email', 'like', '%' . $queryString . '%')
                    ->orWhere('matricule', 'like', '%' . $queryString . '%')
                    ->orWhere('phone', 'like', '%' . $queryString . '%')
                    ->orWhere('month', 'like', '%' . $queryString . '%')
                    ->orWhere('email_sent_status', 'like', '%' . $queryString . '%')
                    ->orWhere('sms_sent_status', 'like', '%' . $queryString . '%');
            })->when($this->downloadJob->filters['selectedCompanyId'] != "all", function ($query) {
                return $query->where('company_id', $this->downloadJob->filters['selectedCompanyId']);
            })->when($this->downloadJob->filters['selectedDepartmentId'] != "all", function ($query) {
                return $query->where('department_id', $this->downloadJob->filters['selectedDepartmentId']);
            })->when($this->downloadJob->filters['employee_id'] != "all", function ($query) {
                return $query->where('employee_id', $this->downloadJob->filters['employee_id']);
            })->when(!empty($this->downloadJob->filters['start_date']) || !empty($this->downloadJob->filters['end_date']), function ($query) {
                return $query->whereBetween('created_at', [
                    \Carbon\Carbon::parse($this->downloadJob->filters['start_date'])->startOfDay(),
                    \Carbon\Carbon::parse($this->downloadJob->filters['end_date'])->endOfDay()
                ]);
            });

        return $query->count();
    }

    /**
     * Generate filename for the report
     */
    private function generateFilename(): string
    {
        $timestamp = now()->format('Y_m_d_H_i_s');
        $random = Str::random(5);
        
        return "PayslipReport_{$timestamp}_{$random}.xlsx";
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        $this->downloadJob->update([
            'status' => DownloadJob::STATUS_FAILED,
            'error_message' => $exception->getMessage(),
            'error_details' => [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ],
            'completed_at' => now()
        ]);
    }
}