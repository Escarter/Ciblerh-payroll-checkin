<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use App\Models\Role;

class EnsureEmployeeRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:ensure-employee-role {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ensure all users have the employee role assigned';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        // Check if employee role exists
        $employeeRole = Role::where('name', 'employee')->first();
        if (!$employeeRole) {
            $this->error('Employee role does not exist. Please create it first.');
            return 1;
        }

        // Get all users without the employee role
        $usersWithoutEmployeeRole = User::whereDoesntHave('roles', function($query) {
            $query->where('name', 'employee');
        })->get();

        if ($usersWithoutEmployeeRole->isEmpty()) {
            $this->info('All users already have the employee role assigned.');
            return 0;
        }

        $this->info("Found {$usersWithoutEmployeeRole->count()} users without the employee role.");

        if ($dryRun) {
            $this->warn('DRY RUN - No changes will be made');
            $this->table(
                ['ID', 'Name', 'Email', 'Current Roles'],
                $usersWithoutEmployeeRole->map(function($user) {
                    return [
                        $user->id,
                        $user->name,
                        $user->email,
                        $user->roles->pluck('name')->join(', ') ?: 'None'
                    ];
                })
            );
            return 0;
        }

        $progress = $this->output->createProgressBar($usersWithoutEmployeeRole->count());
        $progress->start();

        $updatedCount = 0;
        foreach ($usersWithoutEmployeeRole as $user) {
            try {
                $user->assignRole('employee');
                $updatedCount++;
                $this->line("\n✓ Assigned employee role to: {$user->name} ({$user->email})");
            } catch (\Exception $e) {
                $this->line("\n✗ Failed to assign role to: {$user->name} ({$user->email}) - {$e->getMessage()}");
            }
            $progress->advance();
        }

        $progress->finish();
        $this->newLine(2);
        $this->info("Successfully assigned employee role to {$updatedCount} users.");

        return 0;
    }
}
