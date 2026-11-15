<?php

namespace App\Providers;

use App\Imports\Services\AdapterRegistry;
use App\Imports\Services\FieldMappingService;
use App\Imports\Services\ImportService;
use Illuminate\Support\ServiceProvider;

class ImportServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // AdapterRegistry auto-discovers adapters in constructor — singleton so we only scan once
        $this->app->singleton(AdapterRegistry::class, function () {
            return new AdapterRegistry();
        });

        // FieldMappingService is stateless
        $this->app->singleton(FieldMappingService::class);

        // ImportService depends on AdapterRegistry + FieldMappingService
        $this->app->singleton(ImportService::class, function ($app) {
            return new ImportService(
                $app->make(AdapterRegistry::class),
                $app->make(FieldMappingService::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
