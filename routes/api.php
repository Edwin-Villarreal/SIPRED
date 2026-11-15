<?php

use App\Http\Controllers\Api\HeartbeatController;
use App\Http\Controllers\Api\Management\AlertController as ManagementAlertController;
use App\Http\Controllers\Api\Management\EventController as ManagementEventController;
use App\Http\Controllers\Api\Management\MeasurementController as ManagementMeasurementController;
use App\Http\Controllers\Api\Management\SensorController as ManagementSensorController;
use App\Http\Controllers\Api\Management\SensorTypeController;
use App\Http\Controllers\Api\Management\ZoneController as ManagementZoneController;
use App\Http\Controllers\Api\MeasurementController;
use App\Http\Controllers\Api\SensorStatusController;
use App\Http\Middleware\AuthenticateManagementApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->get('/csrf-cookie', static function (Request $request) {
    return response()->json(['success' => true, 'csrf_token' => $request->session()->token()]);
})->name('api.csrf-cookie');

// API administrativa para la interfaz SIPRED. Usa la sesión web y CSRF,
// separada de la autenticación por token de los dispositivos IoT.
Route::middleware(['web', AuthenticateManagementApi::class])
    ->prefix('gestion')
    ->name('api.gestion.')
    ->group(function (): void {
        Route::apiResource('zonas', ManagementZoneController::class)->parameters(['zonas' => 'zona']);
        Route::apiResource('sensores', ManagementSensorController::class)->parameters(['sensores' => 'sensor']);
        Route::get('tipos-sensores', [SensorTypeController::class, 'index'])->name('tipos-sensores.index');
        Route::apiResource('alertas', ManagementAlertController::class)->only(['index', 'show', 'update'])->parameters(['alertas' => 'alerta']);
        Route::apiResource('mediciones', ManagementMeasurementController::class)->only(['index', 'show'])->parameters(['mediciones' => 'medicion']);
        Route::apiResource('eventos', ManagementEventController::class)->only(['index', 'show'])->parameters(['eventos' => 'evento']);
    });

Route::prefix('iot')
    ->middleware(['throttle:120,1', 'device.token'])
    ->group(function (): void {
        Route::post('/measurements', [MeasurementController::class, 'store'])->name('api.iot.measurements.store');
        Route::post('/heartbeat', [HeartbeatController::class, 'store'])->name('api.iot.heartbeat.store');
        Route::get('/sensors/{sensor}/status', [SensorStatusController::class, 'show'])->name('api.iot.sensors.status');
    });
