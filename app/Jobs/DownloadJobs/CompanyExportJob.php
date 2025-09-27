<?php

namespace App\Jobs\DownloadJobs;

use App\Models\DownloadJob;
use App\Exports\CompanyExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class CompanyExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
                'started_at' => now(),
                'error_message' => null,
                'error_details' => null,
            ]);

            $filters = $this->downloadJob->filters;

            // Create export instance
            $export = new CompanyExport($filters['query_string'] ?? '');

            // Get total records count
            $totalRecords = $export->query()->count();
            $this->downloadJob->update(['total_records' => $totalRecords]);

            // Generate filename
            $filename = $this->generateFilename();
            $filePath = 'reports/' . $this->downloadJob->user_id . '/' . $filename;

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
                'processed_records' => $totalRecords,
            ]);

            // Log success
            \Log::info("Company export generated successfully", [
                'job_id' => $this->downloadJob->id,
                'filename' => $filename,
                'records' => $totalRecords
            ]);

        } catch (Throwable $e) {
            $this->downloadJob->update([
                'status' => DownloadJob::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'error_details' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ],
                'completed_at' => now(),
            ]);

            \Log::error("Company export failed", [
                'job_id' => $this->downloadJob->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Generate filename for the export
     */
    private function generateFilename(): string
    {
        $timestamp = now()->format('Y_m_d_H_i_s');
        $random = Str::random(5);
        
        return "CompanyExport_{$timestamp}_{$random}.xlsx";
    }
}
