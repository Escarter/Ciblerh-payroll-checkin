<?php

namespace App\Observers;

use App\Models\Role;

class RoleObserver
{
    /**
     * Handle the Role "created" event.
     *
     * @param  \App\Models\Role  $role
     * @return void
     */
    public function created(Role $role)
    {
        auditLog(
            auth()->user(),
            'role_created',
            'web',
            'created_entity',
            $role, // Pass model for enhanced tracking
            [], // No old values for creates
            $role->getAttributes(), // New values
            [
                'translation_key' => 'created_entity',
                'translation_params' => ['entity' => 'role', 'name' => $role->name],
                'entity' => 'role',
                'guard_name' => $role->guard_name
            ]
        );
    }

    /**
     * Handle the Role "updated" event.
     *
     * @param  \App\Models\Role  $role
     * @return void
     */
    public function updated(Role $role)
    {
        auditLog(
            auth()->user(),
            'role_updated',
            'web',
            'updated_entity',
            $role, // Pass model - changes will be auto-detected
            [], // Old values will be auto-detected from getOriginal()
            [], // New values will be auto-detected from getDirty()
            [
                'translation_key' => 'updated_entity',
                'translation_params' => ['entity' => 'role', 'name' => $role->name],
                'entity' => 'role',
                'guard_name' => $role->guard_name
            ]
        );
    }

    /**
     * Handle the Role "deleted" event.
     *
     * @param  \App\Models\Role  $role
     * @return void
     */
    public function deleted(Role $role)
    {
        auditLog(
            auth()->user(),
            'role_deleted',
            'web',
            'deleted_entity',
            $role, // Pass model for enhanced tracking
            $role->getAttributes(), // Capture values before deletion
            [], // No new values for deletes
            [
                'translation_key' => 'deleted_entity',
                'translation_params' => ['entity' => 'role', 'name' => $role->name],
                'entity' => 'role',
                'guard_name' => $role->guard_name
            ]
        );
    }

    /**
     * Handle the Role "restored" event.
     *
     * @param  \App\Models\Role  $role
     * @return void
     */
    public function restored(Role $role)
    {
        //
    }

    /**
     * Handle the Role "force deleted" event.
     *
     * @param  \App\Models\Role  $role
     * @return void
     */
    public function forceDeleted(Role $role)
    {
        auditLog(
            auth()->user(),
            'role_force_deleted',
            'web',
            'force_deleted_entity',
            $role, // Pass model for enhanced tracking
            $role->getAttributes(), // Capture values before deletion
            [], // No new values for force deletes
            [
                'translation_key' => 'force_deleted_entity',
                'translation_params' => ['entity' => 'role', 'name' => $role->name],
                'entity' => 'role',
                'guard_name' => $role->guard_name
            ]
        );
    }
}
