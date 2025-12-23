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
            __('audit_logs.created_entity', ['entity' => 'role', 'name' => $role->name]),
            $role, // Pass model for enhanced tracking
            [], // No old values for creates
            $role->getAttributes(), // New values
            ['entity' => 'role', 'guard_name' => $role->guard_name] // Metadata
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
            __('audit_logs.updated_entity', ['entity' => 'role', 'name' => $role->name]),
            $role, // Pass model - changes will be auto-detected
            [], // Old values will be auto-detected from getOriginal()
            [], // New values will be auto-detected from getDirty()
            ['entity' => 'role', 'guard_name' => $role->guard_name] // Metadata
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
            __('audit_logs.deleted_entity', ['entity' => 'role', 'name' => $role->name]),
            $role, // Pass model for enhanced tracking
            $role->getAttributes(), // Capture values before deletion
            [], // No new values for deletes
            ['entity' => 'role', 'guard_name' => $role->guard_name] // Metadata
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
            __('audit_logs.force_deleted_entity', ['entity' => 'role', 'name' => $role->name]),
            $role, // Pass model for enhanced tracking
            $role->getAttributes(), // Capture values before deletion
            [], // No new values for force deletes
            ['entity' => 'role', 'guard_name' => $role->guard_name] // Metadata
        );
    }
}
