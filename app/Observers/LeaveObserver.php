<?php

namespace App\Observers;

use App\Models\Leave;

class LeaveObserver
{
    /**
     * Handle the Leave "created" event.
     *
     * @param  \App\Models\Leave  $leave
     * @return void
     */
    public function created(Leave $leave)
    {
        auditLog(
            auth()->user(),
            'leave_created',
            'web',
            __('audit_logs.created_absence', ['date' => $leave->start_date->format('Y-m-d')]),
            $leave, // Pass model for enhanced tracking
            [], // No old values for creates
            $leave->getAttributes(), // New values
            [
                'entity' => 'leave',
                'user_id' => $leave->user_id,
                'leave_type_id' => $leave->leave_type_id,
                'start_date' => $leave->start_date->format('Y-m-d'),
                'end_date' => $leave->end_date ? $leave->end_date->format('Y-m-d') : null,
            ] // Metadata
        );
    }

    /**
     * Handle the Leave "updated" event.
     *
     * @param  \App\Models\Leave  $leave
     * @return void
     */
    public function updated(Leave $leave)
    {
        // Check if approval status changed
        $dirty = $leave->getDirty();
        $isApprovalChange = isset($dirty['supervisor_approval_status']) || 
                           isset($dirty['manager_approval_status']);
        
        if ($isApprovalChange) {
            $approvalType = isset($dirty['supervisor_approval_status']) ? 'supervisor' : 'manager';
            $status = $dirty[$approvalType . '_approval_status'] ?? null;
            
            $actionType = match($status) {
                Leave::SUPERVISOR_APPROVAL_APPROVED, Leave::MANAGER_APPROVAL_APPROVED => 'leave_approved',
                Leave::SUPERVISOR_APPROVAL_REJECTED, Leave::MANAGER_APPROVAL_REJECTED => 'leave_rejected',
                default => 'leave_updated',
            };
            
            $message = match($status) {
                Leave::SUPERVISOR_APPROVAL_APPROVED, Leave::MANAGER_APPROVAL_APPROVED => __('audit_logs.approved_absence', [
                    'user' => $leave->user->name ?? 'User',
                    'date' => $leave->start_date->format('Y-m-d')
                ]),
                Leave::SUPERVISOR_APPROVAL_REJECTED, Leave::MANAGER_APPROVAL_REJECTED => __('audit_logs.rejected_absence', [
                    'user' => $leave->user->name ?? 'User',
                    'date' => $leave->start_date->format('Y-m-d')
                ]),
                default => __('audit_logs.updated_absence', [
                    'user' => $leave->user->name ?? 'User',
                    'date' => $leave->start_date->format('Y-m-d')
                ]),
            };
        } else {
            $actionType = 'leave_updated';
            $message = __('audit_logs.updated_absence', [
                'user' => $leave->user->name ?? 'User',
                'date' => $leave->start_date->format('Y-m-d')
            ]);
        }
        
        auditLog(
            auth()->user(),
            $actionType,
            'web',
            $message,
            $leave, // Pass model - changes will be auto-detected
            [], // Old values will be auto-detected from getOriginal()
            [], // New values will be auto-detected from getDirty()
            [
                'entity' => 'leave',
                'user_id' => $leave->user_id,
                'leave_type_id' => $leave->leave_type_id,
                'start_date' => $leave->start_date->format('Y-m-d'),
                'end_date' => $leave->end_date ? $leave->end_date->format('Y-m-d') : null,
            ] // Metadata
        );
    }

    /**
     * Handle the Leave "deleted" event.
     *
     * @param  \App\Models\Leave  $leave
     * @return void
     */
    public function deleted(Leave $leave)
    {
        auditLog(
            auth()->user(),
            'leave_deleted',
            'web',
            __('audit_logs.deleted_absence', ['user' => $leave->user->name ?? 'User', 'date' => $leave->start_date->format('Y-m-d')]),
            $leave, // Pass model for enhanced tracking
            $leave->getAttributes(), // Capture values before deletion
            [], // No new values for deletes
            [
                'entity' => 'leave',
                'user_id' => $leave->user_id,
                'leave_type_id' => $leave->leave_type_id,
            ] // Metadata
        );
    }

    /**
     * Handle the Leave "restored" event.
     *
     * @param  \App\Models\Leave  $leave
     * @return void
     */
    public function restored(Leave $leave)
    {
        //
    }

    /**
     * Handle the Leave "force deleted" event.
     *
     * @param  \App\Models\Leave  $leave
     * @return void
     */
    public function forceDeleted(Leave $leave)
    {
        auditLog(
            auth()->user(),
            'leave_force_deleted',
            'web',
            __('audit_logs.force_deleted_entity', ['entity' => 'leave', 'name' => $leave->user->name ?? 'User']),
            $leave, // Pass model for enhanced tracking
            $leave->getAttributes(), // Capture values before deletion
            [], // No new values for force deletes
            [
                'entity' => 'leave',
                'user_id' => $leave->user_id,
                'leave_type_id' => $leave->leave_type_id,
            ] // Metadata
        );
    }
}
