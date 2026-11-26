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
            'created_entity',
            $leaveType, // Pass model for enhanced tracking
            [], // No old values for creates
            $leaveType->getAttributes(), // New values
            [
                'translation_key' => 'created_entity',
                'translation_params' => ['entity' => 'leave_type', 'name' => $leaveType->name],
                'entity' => 'leave_type'
            ]
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
            'updated_entity',
            $leaveType, // Pass model - changes will be auto-detected
            [], // Old values will be auto-detected from getOriginal()
            [], // New values will be auto-detected from getDirty()
            [
                'translation_key' => 'updated_entity',
                'translation_params' => ['entity' => 'leave_type', 'name' => $leaveType->name],
                'entity' => 'leave_type'
            ]
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
            'deleted_entity',
            $leaveType, // Pass model for enhanced tracking
            $leaveType->getAttributes(), // Capture values before deletion
            [], // No new values for deletes
            [
                'translation_key' => 'deleted_entity',
                'translation_params' => ['entity' => 'leave_type', 'name' => $leaveType->name],
                'entity' => 'leave_type'
            ]
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
            'force_deleted_entity',
            $leaveType, // Pass model for enhanced tracking
            $leaveType->getAttributes(), // Capture values before deletion
            [], // No new values for force deletes
            [
                'translation_key' => 'force_deleted_entity',
                'translation_params' => ['entity' => 'leave_type', 'name' => $leaveType->name],
                'entity' => 'leave_type'
            ]
        );
    }
}
