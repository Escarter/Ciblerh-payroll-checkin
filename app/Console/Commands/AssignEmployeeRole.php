<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class AssignEmployeeRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'role:assign-employee {user_id? : The ID of the user to assign employee role to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign employee role to a user. If no user ID is provided, shows interactive selection.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        
        // Ensure employee role exists
        $employeeRole = Role::firstOrCreate(['name' => 'employee']);
        
        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User with ID {$userId} not found.");
                return 1;
            }
            
            if ($user->hasRole('employee')) {
                $this->info("User {$user->name} already has the employee role.");
                return 0;
            }
            
            $user->assignRole('employee');
            $this->info("Employee role assigned to {$user->name} successfully!");
            return 0;
        }
        
        // Interactive mode
        $users = User::whereDoesntHave('roles', function($query) {
            $query->where('name', 'employee');
        })->get();
        
        if ($users->isEmpty()) {
            $this->info('All users already have the employee role.');
            return 0;
        }
        
        $this->info('Users without employee role:');
        $this->table(
            ['ID', 'Name', 'Email', 'Current Roles'],
            $users->map(function($user) {
                return [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->getRoleNames()->implode(', ') ?: 'None'
                ];
            })
        );
        
        $choice = $this->choice(
            'Select an option:',
            [
                'assign_specific' => 'Assign to specific user by ID',
                'assign_all' => 'Assign to all users without employee role',
                'cancel' => 'Cancel'
            ],
            'cancel'
        );
        
        switch($choice) {
            case 'assign_specific':
                $selectedUserId = $this->ask('Enter user ID:');
                $user = $users->find($selectedUserId);
                if (!$user) {
                    $this->error('Invalid user ID.');
                    return 1;
                }
                $user->assignRole('employee');
                $this->info("Employee role assigned to {$user->name} successfully!");
                break;
                
            case 'assign_all':
                if ($this->confirm('Are you sure you want to assign employee role to all users without it?')) {
                    $count = 0;
                    foreach($users as $user) {
                        $user->assignRole('employee');
                        $count++;
                    }
                    $this->info("Employee role assigned to {$count} users successfully!");
                }
                break;
                
            case 'cancel':
                $this->info('Operation cancelled.');
                break;
        }
        
        return 0;
    }
}
