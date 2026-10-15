<?php

use App\Http\Controllers\Api\HeartbeatController;
use App\Http\Controllers\Api\MeasurementController;
use App\Http\Controllers\Api\SensorStatusController;
use Illuminate\Support\Facades\Route;

Route::prefix('iot')
    ->middleware(['throttle:120,1', 'device.token'])
    ->group(function (): void {
        Route::post('/measurements', [MeasurementController::class, 'store'])->name('api.iot.measurements.store');
        Route::post('/heartbeat', [HeartbeatController::class, 'store'])->name('api.iot.heartbeat.store');
        Route::get('/sensors/{sensor}/status', [SensorStatusController::class, 'show'])->name('api.iot.sensors.status');
    });