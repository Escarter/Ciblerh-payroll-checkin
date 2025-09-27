<?php

namespace App\Observers;

use App\Models\Service;

class ServiceObserver
{
    /**
     * Handle the Service "created" event.
     *
     * @param  \App\Models\Service  $service
     * @return void
     */
    public function created(Service $service)
    {
        auditLog(
            auth()->user(),
            'service_created',
            'web',
           __('Created service with name ') . $service->name
        );
    }

    /**
     * Handle the Service "updated" event.
     *
     * @param  \App\Models\Service  $service
     * @return void
     */
    public function updated(Service $service)
    {
        auditLog(
            auth()->user(),
            'service_updated',
            'web',
           __('Updated service with name ') . $service->name
        );
    }

    /**
     * Handle the Service "deleted" event.
     *
     * @param  \App\Models\Service  $service
     * @return void
     */
    public function deleted(Service $service)
    {
        auditLog(
            auth()->user(),
            'service_deleted',
            'web',
           __('Deleted service with name ') . $service->name
        );
    }

    /**
     * Handle the Service "restored" event.
     *
     * @param  \App\Models\Service  $service
     * @return void
     */
    public function restored(Service $service)
    {
        //
    }

    /**
     * Handle the Service "force deleted" event.
     *
     * @param  \App\Models\Service  $service
     * @return void
     */
    public function forceDeleted(Service $service)
    {
        auditLog(
            auth()->user(),
            'service_force_deleted',
            'web',
           __('Permanently deleted service with name ') . $service->name
        );
    }
}
