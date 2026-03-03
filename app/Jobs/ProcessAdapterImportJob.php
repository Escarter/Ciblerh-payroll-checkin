<?php

namespace App\Jobs;

use App\Imports\Services\ImportService;
use App\Models\ImportJob;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessAdapterImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1200; // 20 minutes
    public $tries = 2;

    protected string $entitySlug;
    protected string $filePath;
    protected int $userId;
    protected array $fieldMapping;
    protected array $context;
    protected string $importMode;
    protected bool $autoCreateEntities;
    protected bool $sendWelcomeEmails;
    protected ?int $importJobId;

    public function __construct(
        string $entitySlug,
        string $filePath,
        int $userId,
        array $fieldMapping,
        array $context = [],
        string $importMode = 'create_only',
        bool $autoCreateEntities = false,
        bool $sendWelcomeEmails = false,
        ?int $importJobId = null
    ) {
        $this->entitySlug = $entitySlug;
        $this->filePath = $filePath;
        $this->userId = $userId;
        $this->fieldMapping = $fieldMapping;
        $this->context = $context;
        $this->importMode = $importMode;
        $this->autoCreateEntities = $autoCreateEntities;
        $this->sendWelcomeEmails = $sendWelcomeEmails;
        $this->importJobId = $importJobId;
        $this->queue = 'processing';
    }

    public function handle(): void
    {
        Log::info('ProcessAdapterImportJob started', [
            'entity' => $this->entitySlug,
            'file' => $this->filePath,
            'user_id' => $this->userId,
        ]);

        $importJob = null;
        if ($this->importJobId) {
            $importJob = ImportJob::find($this->importJobId);
        }

        try {
            $user = User::findOrFail($this->userId);
            $importService = app(ImportService::class);

            // Parse file
            $parsed = $importService->parseFile($this->filePath);

            if (empty($parsed['rows'])) {
                if ($importJob) {
                    $importJob->update([
                        'status' => ImportJob::STATUS_COMPLETED,
                        'total_rows' => 0,
                        'processed_rows' => 0,
                        'completed_at' => now(),
                        'error_message' => 'No data rows found in file.',
                    ]);
                }
                return;
            }

            // Execute import via the service
            $result = $importService->execute(
                $parsed['rows'],
                $this->fieldMapping,
                $this->entitySlug,
                $this->context,
                $this->importMode,
                $this->autoCreateEntities,
                $user,
                $importJob,
                function ($processed, $total) use ($importJob) {
                    // Progress callback — update every 25 rows
                    if ($importJob && $processed % 25 === 0) {
                        $importJob->update(['processed_rows' => $processed]);
                    }
                }
            );

            Log::info('ProcessAdapterImportJob completed', [
                'entity' => $this->entitySlug,
                'stats' => $result['stats'],
                'error_count' => count($result['errors']),
            ]);

        } catch (\Exception $e) {
            Log::error('ProcessAdapterImportJob failed', [
                'entity' => $this->entitySlug,
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 1000),
            ]);

            if ($importJob) {
                $importJob->update([
                    'status' => ImportJob::STATUS_FAILED,
                    'error_message' => $e->getMessage(),
                    'completed_at' => now(),
                ]);
            }

            throw $e;
        } finally {
            // Clean up uploaded file
            try {
                if (Storage::disk('local')->exists($this->filePath)) {
                    Storage::disk('local')->delete($this->filePath);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to clean up import file', ['path' => $this->filePath]);
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessAdapterImportJob permanently failed', [
            'entity' => $this->entitySlug,
            'error' => $exception->getMessage(),
        ]);

        if ($this->importJobId) {
            $importJob = ImportJob::find($this->importJobId);
            if ($importJob) {
                $importJob->update([
                    'status' => ImportJob::STATUS_FAILED,
                    'error_message' => 'Import job failed after all retries: ' . $exception->getMessage(),
                    'completed_at' => now(),
                ]);
            }
        }
    }
}
