<?php

namespace App\Jobs\Traits;

use Illuminate\Support\Facades\Storage;

trait SafeFileOperations
{
    /**
     * Safely retrieve file size with error handling
     * Returns 0 if file doesn't exist or metadata can't be retrieved
     * Prevents UnableToRetrieveMetadata exceptions from Flysystem
     */
    protected function safeGetFileSize(string $filePath): int
    {
        try {
            if (!Storage::disk('public')->exists($filePath)) {
                \Log::warning('File does not exist for size retrieval', [
                    'file_path' => $filePath,
                    'job_id' => $this->downloadJob->id ?? 'unknown'
                ]);
                return 0;
            }

            return (int) Storage::disk('public')->size($filePath);
        } catch (\Exception $e) {
            \Log::warning('Unable to retrieve file size', [
                'file_path' => $filePath,
                'error' => $e->getMessage(),
                'job_id' => $this->downloadJob->id ?? 'unknown',
                'exception_class' => get_class($e)
            ]);
            return 0;
        }
    }
}
