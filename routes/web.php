<?php

use App\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\AccountRecoveryController;
use App\Http\Controllers\RecoverySettingsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MeasurementPageController;
use App\Http\Controllers\OperationalManagementPageController;
use App\Http\Controllers\OperationalPageController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
    Route::get('/recuperar-contrasena', [AccountRecoveryController::class, 'showRecovery'])->name('password.recovery');
    Route::post('/recuperar-contrasena/codigo', [AccountRecoveryController::class, 'verifyCode'])->middleware('throttle:10,1')->name('password.recovery.code');
    Route::post('/recuperar-contrasena/archivo', [AccountRecoveryController::class, 'verifyFile'])->middleware('throttle:10,1')->name('password.recovery.file');
    Route::get('/restablecer-contrasena', [AccountRecoveryController::class, 'showResetForm'])->name('password.recovery.reset');
    Route::post('/restablecer-contrasena', [AccountRecoveryController::class, 'resetPassword'])->middleware('throttle:10,1')->name('password.recovery.update');
});

Route::middleware(['auth', 'auth.session'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/cuenta/recuperacion', [AccountRecoveryController::class, 'accountRecovery'])->name('account.recovery');
    Route::post('/cuenta/recuperacion/codigos', [AccountRecoveryController::class, 'generateCodes'])->middleware('throttle:3,10')->name('account.recovery.codes');
    Route::post('/cuenta/recuperacion/archivo', [AccountRecoveryController::class, 'downloadRecoveryFile'])->middleware('throttle:3,10')->name('account.recovery.file');
    Route::get('/configuracion/recuperacion', [RecoverySettingsController::class, 'edit'])->name('recovery.settings.edit');
    Route::put('/configuracion/recuperacion', [RecoverySettingsController::class, 'update'])->middleware('throttle:10,1')->name('recovery.settings.update');
    Route::get('/mapa-riesgos', [OperationalPageController::class, 'riskMap'])->name('riesgos.index');
    Route::get('/mapa-riesgos/crear', [OperationalManagementPageController::class, 'createZone'])->name('riesgos.create');
    Route::get('/mapa-riesgos/{zona}/editar', [OperationalManagementPageController::class, 'editZone'])->name('riesgos.edit');
    Route::get('/sensores', [OperationalPageController::class, 'sensors'])->name('sensores.index');
    Route::get('/sensores/crear', [OperationalManagementPageController::class, 'createSensor'])->name('sensores.create');
    Route::get('/sensores/{sensor}/editar', [OperationalManagementPageController::class, 'editSensor'])->name('sensores.edit');
    Route::get('/alertas', [OperationalPageController::class, 'alerts'])->name('alertas.index');
    Route::get('/eventos', [OperationalPageController::class, 'events'])->name('eventos.index');
    Route::get('/reportes', [OperationalPageController::class, 'reports'])->name('reportes.index');
    Route::get('/usuarios', [OperationalPageController::class, 'users'])->name('usuarios.index');
    Route::get('/configuracion', [OperationalPageController::class, 'configuration'])->name('configuracion.index');
    Route::get('/mediciones', [MeasurementPageController::class, 'index'])->name('mediciones.index');
    Route::get('/mediciones/{medicion}', [MeasurementPageController::class, 'show'])->name('mediciones.show');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
