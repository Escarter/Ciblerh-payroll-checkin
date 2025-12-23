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
            'created_absence',
            $leave, // Pass model for enhanced tracking
            [], // No old values for creates
            $leave->getAttributes(), // New values
            [
                'translation_key' => 'created_absence',
                'translation_params' => ['date' => $leave->start_date->format('Y-m-d')],
                'entity' => 'leave',
                'user_id' => $leave->user_id,
                'leave_type_id' => $leave->leave_type_id,
                'start_date' => $leave->start_date->format('Y-m-d'),
                'end_date' => $leave->end_date ? $leave->end_date->format('Y-m-d') : null,
            ]
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
            
            $translationKey = match($status) {
                Leave::SUPERVISOR_APPROVAL_APPROVED, Leave::MANAGER_APPROVAL_APPROVED => 'approved_absence',
                Leave::SUPERVISOR_APPROVAL_REJECTED, Leave::MANAGER_APPROVAL_REJECTED => 'rejected_absence',
                default => 'updated_absence',
            };
            $translationParams = [
                'user' => $leave->user->name ?? 'User',
                'date' => $leave->start_date->format('Y-m-d')
            ];
            $message = $translationKey;
        } else {
            $actionType = 'leave_updated';
            $translationKey = 'updated_absence';
            $translationParams = [
                'user' => $leave->user->name ?? 'User',
                'date' => $leave->start_date->format('Y-m-d')
            ];
            $message = 'updated_absence';
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
                'translation_key' => $translationKey,
                'translation_params' => $translationParams,
                'entity' => 'leave',
                'user_id' => $leave->user_id,
                'leave_type_id' => $leave->leave_type_id,
                'start_date' => $leave->start_date->format('Y-m-d'),
                'end_date' => $leave->end_date ? $leave->end_date->format('Y-m-d') : null,
            ]
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
            'deleted_absence',
            $leave, // Pass model for enhanced tracking
            $leave->getAttributes(), // Capture values before deletion
            [], // No new values for deletes
            [
                'translation_key' => 'deleted_absence',
                'translation_params' => ['user' => $leave->user->name ?? 'User', 'date' => $leave->start_date->format('Y-m-d')],
                'entity' => 'leave',
                'user_id' => $leave->user_id,
                'leave_type_id' => $leave->leave_type_id,
            ]
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
            'force_deleted_entity',
            $leave, // Pass model for enhanced tracking
            $leave->getAttributes(), // Capture values before deletion
            [], // No new values for force deletes
            [
                'translation_key' => 'force_deleted_entity',
                'translation_params' => ['entity' => 'leave', 'name' => $leave->user->name ?? 'User'],
                'entity' => 'leave',
                'user_id' => $leave->user_id,
                'leave_type_id' => $leave->leave_type_id,
            ]
        );
    }
}
