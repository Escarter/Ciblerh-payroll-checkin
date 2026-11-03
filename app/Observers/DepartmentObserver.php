<?php

namespace App\Observers;

use App\Models\Department;

class DepartmentObserver
{
    /**
     * Handle the Department "created" event.
     *
     * @param  \App\Models\Department  $department
     * @return void
     */
    public function created(Department $department)
    {
        auditLog(
            auth()->user(),
            'department_created',
            'web',
            'created_entity',
            $department,
            [],
            [],
            [
                'translation_key' => 'created_entity',
                'translation_params' => ['entity' => 'department', 'name' => $department->name],
            ]
        );
    }

    /**
     * Handle the Department "updated" event.
     *
     * @param  \App\Models\Department  $department
     * @return void
     */
    public function updated(Department $department)
    {
        auditLog(
            auth()->user(),
            'department_updated',
            'web',
            'updated_entity',
            $department,
            [],
            [],
            [
                'translation_key' => 'updated_entity',
                'translation_params' => ['entity' => 'department', 'name' => $department->name],
            ]
        );
    }

    /**
     * Handle the Department "deleted" event.
     *
     * @param  \App\Models\Department  $department
     * @return void
     */
    public function deleted(Department $department)
    {
        auditLog(
            auth()->user(),
            'department_deleted',
            'web',
            'deleted_entity',
            $department,
            [],
            [],
            [
                'translation_key' => 'deleted_entity',
                'translation_params' => ['entity' => 'department', 'name' => $department->name],
            ]
        );
    }

    /**
     * Handle the Department "restored" event.
     *
     * @param  \App\Models\Department  $department
     * @return void
     */
    public function restored(Department $department)
    {
        //
    }

    /**
     * Handle the Department "force deleted" event.
     *
     * @param  \App\Models\Department  $department
     * @return void
     */
    public function forceDeleted(Department $department)
    {
        //
    }
}
