<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PendienteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // PENDIENTES - Con botón de rechazo incluido
    Route::prefix('pendientes')->name('pendientes.')->group(function () {
        Route::get('/', [PendienteController::class, 'index'])->name('index');
        Route::post('/{solicitud}/check', [PendienteController::class, 'updateEstado'])->name('check');
        Route::post('/{solicitud}/rechazar', [PendienteController::class, 'rechazar'])->name('rechazar'); // NUEVA
    });

    // Ruta para cambiar estado desde el detalle de solicitud
    Route::post('/solicitudes/{solicitud}/estado', [SolicitudController::class, 'updateEstado'])->name('solicitudes.cambiarEstado');
    
    Route::resource('solicitudes', SolicitudController::class)
        ->parameters(['solicitudes' => 'solicitud']);

    Route::resource('students', StudentController::class);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';