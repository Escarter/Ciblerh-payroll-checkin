<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function created(User $user)
    {
        // Automatically assign employee role if no roles are assigned
        if ($user->roles()->count() === 0) {
            $user->assignRole('employee');
        }
        
        auditLog(
            auth()->user(),
            'user_created',
            'web',
            __('audit_logs.created_entity', ['entity' => 'user', 'name' => $user->name])
        );
    }

    /**
     * Handle the User "updated" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function updated(User $user)
    {
        auditLog(
            auth()->user(),
            'user_updated',
            'web',
            __('audit_logs.updated_entity', ['entity' => 'user', 'name' => $user->name])
        );
    }

    /**
     * Handle the User "deleted" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function deleted(User $user)
    {
        auditLog(
            auth()->user(),
            'user_deleted',
            'web',
            __('audit_logs.deleted_entity', ['entity' => 'user', 'name' => $user->name])
        );
    }

    /**
     * Handle the User "restored" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function restored(User $user)
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function forceDeleted(User $user)
    {
        //
    }
}
