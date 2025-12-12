<?php

use App\Http\Controllers\HealthCheckController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Health Check Routes
|--------------------------------------------------------------------------
|
| These routes are used for monitoring and health checks. They provide
| endpoints that external monitoring systems can use to check the
| health of the application and its services.
|
*/

Route::get('/health', [HealthCheckController::class, 'basic'])
    ->name('health.basic');

Route::get('/health/detailed', [HealthCheckController::class, 'detailed'])
    ->name('health.detailed');

// Optional: Add these to RouteServiceProvider or web.php
// Route::prefix('api')->group(function () {
//     require __DIR__.'/health.php';
// });