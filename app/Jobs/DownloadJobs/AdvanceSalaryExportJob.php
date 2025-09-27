<?php

namespace App\Jobs\DownloadJobs;

use App\Models\DownloadJob;
use App\Models\Company;
use App\Exports\AdvanceSalaryExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class AdvanceSalaryExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $downloadJob;

    public function __construct(DownloadJob $downloadJob)
    {
        $this->downloadJob = $downloadJob;
    }

    public function handle(): void
    {
        try {
            $this->downloadJob->update([
                'status' => DownloadJob::STATUS_PROCESSING,
                'started_at' => now(),
                'error_message' => null,
                'error_details' => null,
            ]);

            $filters = $this->downloadJob->filters;
            $company = null;
            if (!empty($filters['selectedCompanyId']) && $filters['selectedCompanyId'] !== 'all') {
                $company = Company::find($filters['selectedCompanyId']);
            }

            $export = new AdvanceSalaryExport($company, $filters['query_string'] ?? '');
            $totalRecords = $export->query()->count();
            $this->downloadJob->update(['total_records' => $totalRecords]);

            $filename = $this->generateFilename();
            $filePath = 'reports/' . $this->downloadJob->user_id . '/' . $filename;
            Excel::store($export, $filePath, 'public');

            $this->downloadJob->update([
                'status' => DownloadJob::STATUS_COMPLETED,
                'completed_at' => now(),
                'file_path' => $filePath,
                'file_name' => $filename,
                'file_size' => Storage::disk('public')->size($filePath),
                'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'processed_records' => $totalRecords,
            ]);

            \Log::info("Advance Salary export generated successfully", [
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

            \Log::error("Advance Salary export failed", [
                'job_id' => $this->downloadJob->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function generateFilename(): string
    {
        $timestamp = now()->format('Y_m_d_H_i_s');
        $random = Str::random(5);
        return "AdvanceSalaryExport_{$timestamp}_{$random}.xlsx";
    }
}
