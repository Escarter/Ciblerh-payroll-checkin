<?php

namespace App\Observers;

use App\Models\LeaveType;

class LeaveTypeObserver
{
    /**
     * Handle the LeaveType "created" event.
     *
     * @param  \App\Models\LeaveType  $leaveType
     * @return void
     */
    public function created(LeaveType $leaveType)
    {
        auditLog(
            auth()->user(),
            'leave_type_created',
            'web',
            __('audit_logs.created_entity', ['entity' => 'leave_type', 'name' => $leaveType->name]),
            $leaveType, // Pass model for enhanced tracking
            [], // No old values for creates
            $leaveType->getAttributes(), // New values
            ['entity' => 'leave_type'] // Metadata
        );
    }

    /**
     * Handle the LeaveType "updated" event.
     *
     * @param  \App\Models\LeaveType  $leaveType
     * @return void
     */
    public function updated(LeaveType $leaveType)
    {
        auditLog(
            auth()->user(),
            'leave_type_updated',
            'web',
            __('audit_logs.updated_entity', ['entity' => 'leave_type', 'name' => $leaveType->name]),
            $leaveType, // Pass model - changes will be auto-detected
            [], // Old values will be auto-detected from getOriginal()
            [], // New values will be auto-detected from getDirty()
            ['entity' => 'leave_type'] // Metadata
        );
    }

    /**
     * Handle the LeaveType "deleted" event.
     *
     * @param  \App\Models\LeaveType  $leaveType
     * @return void
     */
    public function deleted(LeaveType $leaveType)
    {
        auditLog(
            auth()->user(),
            'leave_type_deleted',
            'web',
            __('audit_logs.deleted_entity', ['entity' => 'leave_type', 'name' => $leaveType->name]),
            $leaveType, // Pass model for enhanced tracking
            $leaveType->getAttributes(), // Capture values before deletion
            [], // No new values for deletes
            ['entity' => 'leave_type'] // Metadata
        );
    }

    /**
     * Handle the LeaveType "restored" event.
     *
     * @param  \App\Models\LeaveType  $leaveType
     * @return void
     */
    public function restored(LeaveType $leaveType)
    {
        //
    }

    /**
     * Handle the LeaveType "force deleted" event.
     *
     * @param  \App\Models\LeaveType  $leaveType
     * @return void
     */
    public function forceDeleted(LeaveType $leaveType)
    {
        auditLog(
            auth()->user(),
            'leave_type_force_deleted',
            'web',
            __('audit_logs.force_deleted_entity', ['entity' => 'leave_type', 'name' => $leaveType->name]),
            $leaveType, // Pass model for enhanced tracking
            $leaveType->getAttributes(), // Capture values before deletion
            [], // No new values for force deletes
            ['entity' => 'leave_type'] // Metadata
        );
    }
}
