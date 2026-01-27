<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\PendienteController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Página de inicio pública (welcome)
Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// Ruta para cambiar el tema (modo oscuro/claro) - accesible sin autenticación
Route::post('/toggle-dark-mode', [ThemeController::class, 'toggleDarkMode'])->name('theme.toggle');

// Grupo de rutas protegidas (requiere autenticación)
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Perfil de usuario (proporcionado por Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Estudiantes (CRUD completo)
    Route::resource('students', StudentController::class);
    Route::get('students/export', [StudentController::class, 'export'])->name('students.export');
    Route::post('students/{student}/toggle-status', [StudentController::class, 'toggleStatus'])->name('students.toggle-status');

    // Solicitudes (con algunas restricciones)
    Route::resource('solicitudes', SolicitudController::class)->except(['destroy']);
    Route::get('solicitudes/{solicitud}/historial', [SolicitudController::class, 'historial'])->name('solicitudes.historial');
    Route::post('solicitudes/{solicitud}/cambiar-estado', [SolicitudController::class, 'cambiarEstado'])->name('solicitudes.cambiar-estado');
    Route::get('solicitudes/{solicitud}/descargar-archivo/{archivo}', [SolicitudController::class, 'descargarArchivo'])->name('solicitudes.descargar-archivo');
    Route::delete('solicitudes/archivo/{archivo}', [SolicitudController::class, 'eliminarArchivo'])->name('solicitudes.eliminar-archivo');

    // Pendientes (solicitudes pendientes y en revisión)
    Route::prefix('pendientes')->name('pendientes.')->group(function () {
        Route::get('/', [PendienteController::class, 'index'])->name('index');
        Route::post('acciones-masivas', [PendienteController::class, 'accionesMasivas'])->name('acciones-masivas');
        Route::post('{solicitud}/aprobar', [PendienteController::class, 'aprobar'])->name('aprobar');
        Route::post('{solicitud}/rechazar', [PendienteController::class, 'rechazar'])->name('rechazar');
        Route::post('{solicitud}/mover-revision', [PendienteController::class, 'moverRevision'])->name('mover-revision');
        Route::get('estadisticas', [PendienteController::class, 'estadisticas'])->name('estadisticas');
    });

    // Rutas adicionales para estadísticas y reportes
    Route::get('dashboard/estadisticas', [DashboardController::class, 'estadisticas'])->name('dashboard.estadisticas');
});

// Rutas de autenticación de Breeze
require __DIR__.'/auth.php';