<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SetupSftpPush extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:sftp-push {--force : Force re-creation of credentials} {--path= : Custom push path}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Set up SFTP push infrastructure: create directory, set permissions, generate credentials';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Setting up SFTP Push Infrastructure...');
        $this->newLine();

        // Get or create settings
        $setting = Setting::firstOrCreate(['company_id' => 1]);

        // Determine push path
        $customPath = $this->option('path');
        $pushPath = $customPath ? base_path($customPath) : base_path($setting->sftp_push_path ?? 'storage/app/sftp-push');
        
        $this->info("📁 Push Path: {$pushPath}");

        // Step 1: Create directory structure
        $this->line('Step 1: Creating directory structure...');
        try {
            if (!file_exists($pushPath)) {
                @mkdir($pushPath, 0755, true);
                $this->info("  ✅ Created main directory: {$pushPath}");
            } else {
                $this->info("  ℹ️  Main directory already exists");
            }

            // Create 'processed' subdirectory for archived files
            $processedDir = $pushPath . '/processed';
            if (!file_exists($processedDir)) {
                @mkdir($processedDir, 0755, true);
                $this->info("  ✅ Created processed directory: {$processedDir}");
            } else {
                $this->info("  ℹ️  Processed directory already exists");
            }

            // Create 'failed' subdirectory for failed files
            $failedDir = $pushPath . '/failed';
            if (!file_exists($failedDir)) {
                @mkdir($failedDir, 0755, true);
                $this->info("  ✅ Created failed directory: {$failedDir}");
            } else {
                $this->info("  ℹ️  Failed directory already exists");
            }
        } catch (\Exception $e) {
            $this->error("  ❌ Failed to create directories: {$e->getMessage()}");
            return self::FAILURE;
        }

        // Step 2: Verify permissions
        $this->line('Step 2: Verifying permissions...');
        if (!is_readable($pushPath)) {
            $this->error("  ❌ Push path is not readable");
            return self::FAILURE;
        }
        $this->info("  ✅ Push path is readable");

        if (!is_writable($pushPath)) {
            $this->error("  ❌ Push path is not writable");
            $this->warn("    Run: chmod -R 755 {$pushPath}");
            $this->warn("    Or:  chmod -R u+w {$pushPath}");
            return self::FAILURE;
        }
        $this->info("  ✅ Push path is writable");

        // Step 3: Generate or update credentials
        $this->line('Step 3: Managing credentials...');
        
        $force = $this->option('force');
        $hasExistingCredentials = !empty($setting->sftp_push_username) && !empty($setting->sftp_push_password);

        if ($hasExistingCredentials && !$force) {
            $this->info("  ℹ️  Credentials already exist");
            $this->newLine();
            $this->table(['Property', 'Value'], [
                ['Username', $setting->sftp_push_username],
                ['Generated At', $setting->sftp_credentials_generated_at?->format('Y-m-d H:i:s') ?? 'N/A'],
                ['API Endpoint', route('sftp.upload')],
            ]);
        } else {
            $username = 'push_' . Str::lower(Str::random(8));
            $password = Str::random(24);

            $setting->update([
                'sftp_push_username' => $username,
                'sftp_push_password' => $password,
                'sftp_push_path' => $this->normalizePath($pushPath),
                'sftp_credentials_generated_at' => now(),
            ]);

            $this->info("  ✅ Credentials generated/updated");
            $this->newLine();
            $this->warn('⚠️  Save these credentials - they are shown only once:');
            $this->newLine();
            $this->table(['Property', 'Value'], [
                ['Username', $username],
                ['Password', $password],
                ['API Endpoint', route('sftp.upload')],
            ]);
            $this->newLine();

            $curlExample = "curl -F \"file=@payslip.pdf\" -u {$username}:{$password} " . route('sftp.upload');
            $this->info('Example cURL command:');
            $this->line($curlExample);
        }

        // Step 4: Display configuration summary
        $this->newLine();
        $this->info('✅ SFTP Push setup complete!');
        $this->newLine();
        $this->info('Integration Instructions:');
        $this->line('  1. Use the generated credentials in your external system');
            $this->line('  2. POST files to: ' . route('sftp.upload'));
        $this->line('  3. Use HTTP Basic Authentication with the credentials');
        $this->line('  4. Files are automatically processed by the scheduled job');
        $this->line('  5. Processed files are moved to the "processed" directory');

        return self::SUCCESS;
    }

    /**
     * Normalize path to be relative to base_path() for storage in database
     */
    private function normalizePath($fullPath): string
    {
        $basePath = base_path();
        if (strpos($fullPath, $basePath) === 0) {
            return substr($fullPath, strlen($basePath) + 1);
        }
        return $fullPath;
    }
}
