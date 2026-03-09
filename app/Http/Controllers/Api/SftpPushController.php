<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessSftpPushFileJob;
use App\Models\Setting;
use App\Models\SftpPushLog;
use Illuminate\Http\Request;

class SftpPushController extends Controller
{
    /**
     * Upload payslip file via SFTP-like push interface
     * 
     * Expects: HTTP Basic Auth with sftp_push_username and sftp_push_password
     * Method: POST /api/sftp-push/upload
     * Body: multipart/form-data with file field
     */
    public function upload(Request $request)
    {
        // Validate request
        if (!$request->hasFile('file')) {
            \Log::warning('SFTP push upload attempt without file', [
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'No file provided',
            ], 400);
        }

        // Get settings
        $setting = Setting::first();
        
        if (!$setting || !$setting->sftp_sync_enabled) {
            \Log::warning('SFTP push upload attempt when sync disabled', [
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'SFTP push is not enabled',
            ], 403);
        }

        // Verify credentials via HTTP Basic Auth
        $providedUsername = $request->getUser();
        $providedPassword = $request->getPassword();

        if (!$providedUsername || !$providedPassword) {
            \Log::warning('SFTP push upload attempt without credentials', [
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Authentication required',
            ], 401)->header('WWW-Authenticate', 'Basic realm="SFTP Push Upload"');
        }

        // Verify credentials match
        if ($providedUsername !== $setting->sftp_push_username || 
            $providedPassword !== $setting->sftp_push_password) {
            
            \Log::warning('SFTP push upload attempt with invalid credentials', [
                'ip' => $request->ip(),
                'username' => $providedUsername,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Invalid credentials',
            ], 401)->header('WWW-Authenticate', 'Basic realm="SFTP Push Upload"');
        }

        // Validate file
        $file = $request->file('file');
        
        if (!$file->isValid()) {
            \Log::warning('SFTP push upload with invalid file', [
                'ip' => $request->ip(),
                'filename' => $file->getClientOriginalName(),
                'error' => $file->getErrorMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Invalid file upload',
            ], 400);
        }

        // Only allow PDF files
        if ($file->getClientOriginalExtension() !== 'pdf') {
            \Log::warning('SFTP push upload with non-PDF file', [
                'ip' => $request->ip(),
                'filename' => $file->getClientOriginalName(),
                'extension' => $file->getClientOriginalExtension(),
            ]);

            // Log rejection
            SftpPushLog::create([
                'username' => $providedUsername,
                'filename' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'remote_ip' => $request->ip(),
                'status' => 'rejected',
                'rejection_reason' => 'Only PDF files are accepted. Received: ' . $file->getClientOriginalExtension(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Only PDF files are accepted',
            ], 415);
        }

        // Get push path
        $pushPath = base_path($setting->sftp_push_path ?? 'storage/app/sftp-push');

        // Create directory if it doesn't exist
        try {
            if (!file_exists($pushPath)) {
                mkdir($pushPath, 0755, true);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to create push directory', [
                'path' => $pushPath,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to prepare upload directory',
            ], 500);
        }

        // Store file with timestamp to avoid conflicts
        try {
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $filename = $originalName . '_' . time() . '.pdf';
            $storagePath = $pushPath . '/' . $filename;

            if (!$file->move($pushPath, $filename)) {
                throw new \Exception('Failed to move uploaded file');
            }

            // Log successful upload
            \Log::info('SFTP push upload successful', [
                'username' => $providedUsername,
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'ip' => $request->ip(),
                'timestamp' => now(),
            ]);

            // Store log entry
            SftpPushLog::create([
                'username' => $providedUsername,
                'filename' => $file->getClientOriginalName(),
                'stored_filename' => $filename,
                'file_size' => $file->getSize(),
                'remote_ip' => $request->ip(),
                'status' => 'success',
            ]);

            // Dispatch job to extract metadata and create a matching proposal
            ProcessSftpPushFileJob::dispatch($storagePath, $file->getClientOriginalName());

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'stored_at' => now(),
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Failed to store uploaded file', [
                'filename' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            // Log failure
            SftpPushLog::create([
                'username' => $providedUsername,
                'filename' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'remote_ip' => $request->ip(),
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to store file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List pending files in push directory (for debugging/monitoring)
     * Requires authentication
     */
    public function listPending(Request $request)
    {
        // Verify credentials
        $setting = Setting::first();
        $providedUsername = $request->getUser();
        $providedPassword = $request->getPassword();

        if (!$providedUsername || !$providedPassword ||
            $providedUsername !== $setting->sftp_push_username || 
            $providedPassword !== $setting->sftp_push_password) {
            
            return response()->json([
                'success' => false,
                'error' => 'Invalid credentials',
            ], 401);
        }

        try {
            $pushPath = base_path($setting->sftp_push_path ?? 'storage/app/sftp-push');

            if (!file_exists($pushPath)) {
                return response()->json([
                    'success' => true,
                    'files' => [],
                ]);
            }

            $files = array_filter(
                scandir($pushPath),
                fn($f) => is_file($pushPath . '/' . $f) && strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'pdf'
            );

            $fileDetails = [];
            foreach ($files as $file) {
                $filePath = $pushPath . '/' . $file;
                $fileDetails[] = [
                    'name' => $file,
                    'size' => filesize($filePath),
                    'uploaded_at' => date('Y-m-d H:i:s', filemtime($filePath)),
                ];
            }

            return response()->json([
                'success' => true,
                'count' => count($fileDetails),
                'files' => $fileDetails,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test endpoint to verify credentials and path
     */
    public function test(Request $request)
    {
        // Verify credentials
        $setting = Setting::first();
        $providedUsername = $request->getUser();
        $providedPassword = $request->getPassword();

        if (!$providedUsername || !$providedPassword ||
            $providedUsername !== $setting->sftp_push_username || 
            $providedPassword !== $setting->sftp_push_password) {
            
            return response()->json([
                'success' => false,
                'error' => 'Invalid credentials',
            ], 401);
        }

        try {
            $pushPath = base_path($setting->sftp_push_path ?? 'storage/app/sftp-push');

            $accessible = file_exists($pushPath) && is_readable($pushPath);
            $writable = is_writable($pushPath);

            return response()->json([
                'success' => $accessible && $writable,
                'path' => $pushPath,
                'accessible' => $accessible,
                'writable' => $writable,
                'enabled' => $setting->sftp_sync_enabled,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
