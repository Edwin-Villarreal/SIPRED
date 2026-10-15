<?php

use App\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MeasurementPageController;
use App\Http\Controllers\OperationalPageController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/mapa-riesgos', [OperationalPageController::class, 'riskMap'])->name('riesgos.index');
    Route::get('/sensores', [OperationalPageController::class, 'sensors'])->name('sensores.index');
    Route::get('/alertas', [OperationalPageController::class, 'alerts'])->name('alertas.index');
    Route::get('/eventos', [OperationalPageController::class, 'events'])->name('eventos.index');
    Route::get('/reportes', [OperationalPageController::class, 'reports'])->name('reportes.index');
    Route::get('/usuarios', [OperationalPageController::class, 'users'])->name('usuarios.index');
    Route::get('/configuracion', [OperationalPageController::class, 'configuration'])->name('configuracion.index');
    Route::get('/mediciones', [MeasurementPageController::class, 'index'])->name('mediciones.index');
    Route::get('/mediciones/{medicion}', [MeasurementPageController::class, 'show'])->name('mediciones.show');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
