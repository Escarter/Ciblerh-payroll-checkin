<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\UserDeactivationService;
use App\Models\Setting;

class DeactivateInactiveUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:deactivate-inactive {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deactivate user accounts that have not received payslips for a configured number of months';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== Inactivity Deactivation Process Started ===');

        // Check if feature is enabled
        $setting = Setting::first();
        if (!$setting || !$setting->inactivity_deactivation_enabled) {
            $this->warn('Inactivity deactivation feature is currently disabled.');
            return 0;
        }

        $monthsThreshold = $setting->inactivity_months_threshold ?? 6;
        $isDryRun = $this->option('dry-run');

        $this->info("Configuration:");
        $this->info("  - Months threshold: {$monthsThreshold}");
        $this->info("  - Dry run: " . ($isDryRun ? 'Yes' : 'No'));

        // Find inactive users
        $deactivationService = new UserDeactivationService();
        $inactiveUsers = $deactivationService->findInactiveUsers($monthsThreshold);

        $this->info("\nFound {$inactiveUsers->count()} inactive users");

        if ($inactiveUsers->isEmpty()) {
            $this->info('No inactive users found.');
            return 0;
        }

        // Display users to be deactivated
        $this->newLine();
        $this->table(
            ['ID', 'Name', 'Email', 'Last Payslip'],
            $inactiveUsers->map(fn($user) => [
                $user->id,
                $user->first_name . ' ' . $user->last_name,
                $user->email,
                $user->last_payslip_received_at?->format('Y-m-d') ?? 'Never',
            ])->toArray()
        );

        if ($isDryRun) {
            $this->info("\n✓ Dry run completed. No users were deactivated.");
            return 0;
        }

        // Confirm before deactivating
        if (!$this->confirm("\nAre you sure you want to deactivate these users?")) {
            $this->info('Operation cancelled.');
            return 0;
        }

        // Deactivate users
        $result = $deactivationService->deactivateUsers($inactiveUsers);

        // Display results
        $this->newLine();
        $this->info('=== Deactivation Results ===');
        $this->info("✓ Successfully deactivated: {$result['deactivated_count']}");
        if ($result['failed_count'] > 0) {
            $this->error("✗ Failed to deactivate: {$result['failed_count']}");
        }

        $this->info("\nExecuted at: {$result['executed_at']->format('Y-m-d H:i:s')}");

        return 0;
    }
}
