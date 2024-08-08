<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class LeaveUpdateProcess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wima:leave-update-process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically increment employees leave days based on their monthly allocations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $all_active_users = User::with([
            'roles' => fn($query) => $query->whereIn('name', ['employee', 'supervisor'])
        ])->where('status',1)->get();

        foreach($all_active_users as $employee)
        {
            if(!empty($employee->monthly_leave_allocation)){
                $employee->increment('remaining_leave_days', $employee->monthly_leave_allocation);
            }
        }
    }
}
