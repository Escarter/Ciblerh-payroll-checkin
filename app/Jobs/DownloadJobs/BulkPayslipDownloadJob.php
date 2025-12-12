<?php

namespace App\Jobs\DownloadJobs;

use App\Models\DownloadJob;
use App\Models\Payslip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class BulkPayslipDownloadJob implements ShouldQueue
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

            // Get payslips based on filters
            $payslips = $this->getFilteredPayslips();
            $this->downloadJob->update(['total_records' => $payslips->count()]);

            if ($payslips->count() === 0) {
                $this->downloadJob->update([
                    'status' => DownloadJob::STATUS_FAILED,
                    'error_message' => __('No payslips found matching the specified criteria.'),
                    'completed_at' => now()
                ]);
                return;
            }

            // Create zip file
            $zipPath = $this->createZipFile($payslips);

            // Update job completion
            $this->downloadJob->update([
                'status' => DownloadJob::STATUS_COMPLETED,
                'completed_at' => now(),
                'file_path' => $zipPath,
                'file_name' => basename($zipPath),
                'file_size' => Storage::disk('public')->size($zipPath),
                'mime_type' => 'application/zip',
                'processed_records' => $this->downloadJob->processed_records,
                'failed_records' => $this->downloadJob->failed_records
            ]);

            \Log::info("Bulk payslip download completed successfully", [
                'job_id' => $this->downloadJob->id,
                'filename' => basename($zipPath),
                'processed' => $this->downloadJob->processed_records,
                'failed' => $this->downloadJob->failed_records
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

            \Log::error("Bulk payslip download failed", [
                'job_id' => $this->downloadJob->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get filtered payslips based on job filters
     */
    private function getFilteredPayslips()
    {
        $query = Payslip::query()
            ->when($this->downloadJob->filters['selectedCompanyId'] != "all", function ($q) {
                return $q->where('company_id', $this->downloadJob->filters['selectedCompanyId']);
            })->when($this->downloadJob->filters['selectedDepartmentId'] != "all", function ($q) {
                return $q->where('department_id', $this->downloadJob->filters['selectedDepartmentId']);
            })->when($this->downloadJob->filters['employee_id'] != "all", function ($q) {
                return $q->where('employee_id', $this->downloadJob->filters['employee_id']);
            })->when(!empty($this->downloadJob->filters['start_date']) && !empty($this->downloadJob->filters['end_date']), function ($q) {
                return $q->whereBetween('created_at', [
                    \Carbon\Carbon::parse($this->downloadJob->filters['start_date'])->startOfDay(),
                    \Carbon\Carbon::parse($this->downloadJob->filters['end_date'])->endOfDay()
                ]);
            });

        return $query->get();
    }

    /**
     * Create zip file with payslip PDFs
     */
    private function createZipFile($payslips): string
    {
        $zipFileName = 'payslips_' . Str::slug($this->getFilterDescription()) . '_' . now()->format('Y_m_d_H_i_s') . '.zip';
        $zipPath = 'downloads/' . $this->downloadJob->user_id . '/' . $zipFileName;

        // Ensure directory exists
        Storage::disk('public')->makeDirectory(dirname($zipPath));

        $zip = new ZipArchive();
        $fullZipPath = Storage::disk('public')->path($zipPath);
        
        if ($zip->open($fullZipPath, ZipArchive::CREATE) !== TRUE) {
            throw new \Exception(__('Cannot create zip file'));
        }

        $processed = 0;
        $failed = 0;

        foreach ($payslips as $payslip) {
            try {
                if (Storage::disk('modified')->exists($payslip->file)) {
                    $fileName = $payslip->matricule . '_' . $payslip->year . '_' . $payslip->month . '.pdf';
                    $zip->addFile(
                        Storage::disk('modified')->path($payslip->file),
                        $fileName
                    );
                    $processed++;
                } else {
                    $failed++;
                    \Log::warning("Payslip file not found", [
                        'payslip_id' => $payslip->id,
                        'file_path' => $payslip->file
                    ]);
                }
            } catch (\Exception $e) {
                $failed++;
                \Log::error("Error adding payslip to zip", [
                    'payslip_id' => $payslip->id,
                    'error' => $e->getMessage()
                ]);
            }

            // Update progress
            $this->downloadJob->update([
                'processed_records' => $processed,
                'failed_records' => $failed
            ]);
        }

        $zip->close();
        return $zipPath;
    }

    /**
     * Generate filter description for filename
     */
    private function getFilterDescription(): string
    {
        $parts = [];
        
        if (isset($this->downloadJob->filters['employee_name'])) {
            $parts[] = $this->downloadJob->filters['employee_name'];
        }
        
        if (isset($this->downloadJob->filters['company_name'])) {
            $parts[] = $this->downloadJob->filters['company_name'];
        }
        
        if (isset($this->downloadJob->filters['start_date']) && isset($this->downloadJob->filters['end_date'])) {
            $parts[] = $this->downloadJob->filters['start_date'] . '_to_' . $this->downloadJob->filters['end_date'];
        }

        return implode('_', $parts) ?: 'all_payslips';
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