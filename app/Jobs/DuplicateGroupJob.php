<?php

namespace App\Jobs;

use App\Models\Group;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DuplicateGroupJob implements ShouldQueue
{
    use  InteractsWithQueue, Queueable, SerializesModels;

    protected $new_group;
    protected $old_group;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Group $new_group, Group $old_group)
    {
        $this->new_group = $new_group;
        $this->old_group = $old_group;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->old_group->employees as $employee) {
            $new_employee = $employee->replicate();
            $new_employee->group_id = $this->new_group->id;
            $new_employee->save();
        }
    }
}
