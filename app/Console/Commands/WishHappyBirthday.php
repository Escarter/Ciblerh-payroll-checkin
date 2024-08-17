<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class WishHappyBirthday extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wima:wish-happy-birthday';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Wish a happy birthday to an employee on their birthday!';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $all_employees_with_birthday_today = User::with([
            'roles' => fn($query) => $query->whereIn('name', ['employee', 'supervisor'])
        ])->where('status', 1)->whereMonth('date_of_birth', now()->month)->whereDay('date_of_birth', now()->day)->get();

        foreach ($all_employees_with_birthday_today as $employee) {
            sendSmsBirthday($employee);
        }
    }
}
