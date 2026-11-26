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
            'created_entity',
            $service,
            [],
            [],
            [
                'translation_key' => 'created_entity',
                'translation_params' => ['entity' => 'service', 'name' => $service->name],
            ]
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
           'updated_service',
           $service,
           [],
           [],
           [
               'translation_key' => 'updated_service',
               'translation_params' => ['name' => $service->name],
           ]
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
           'deleted_service',
           $service,
           [],
           [],
           [
               'translation_key' => 'deleted_service',
               'translation_params' => ['name' => $service->name],
           ]
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
           'permanently_deleted_service',
           $service,
           [],
           [],
           [
               'translation_key' => 'permanently_deleted_service',
               'translation_params' => ['name' => $service->name],
           ]
        );
    }
}
