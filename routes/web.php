<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// -----------------------------------------------------------------------------
// RUTAS PÚBLICAS
// -----------------------------------------------------------------------------
Route::get('/', function () {
    return view('welcome');
});

// -----------------------------------------------------------------------------
// RUTAS BÁSICAS DE AUTENTICACIÓN (Cualquiera que inicie sesión)
// -----------------------------------------------------------------------------
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Rutas del perfil nativas de Laravel Breeze
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// -----------------------------------------------------------------------------
// MÓDULO DE USUARIOS Y ROLES (Protegido por Spatie)
// -----------------------------------------------------------------------------

/* * NIVEL 1: Lectura. 
 * Separamos el "index" para que en el futuro más roles (ej: Supervisor, Director) 
 * puedan entrar a ver la tabla. Aquí es donde brilla el @can en la vista.
 */
Route::middleware(['auth', 'role:Administrador|Supervisor'])->group(function () {
    Route::get('/usuarios', [UserController::class, 'index'])->name('users.index'); 
});

/* * NIVEL 2: Escritura/Edición. 
 * Estas rutas son críticas. Las dejamos en un grupo exclusivo donde SOLO 
 * el Administrador puede entrar a ver el formulario y guardar cambios.
 */
Route::middleware(['auth', 'role:Administrador'])->group(function () {
    Route::get('/usuarios/{user}/roles', [UserController::class, 'editRoles'])->name('users.roles.edit');
    Route::put('/usuarios/{user}/roles', [UserController::class, 'updateRoles'])->name('users.roles.update');
});


Route::get('/tutorial', function () {
    return view('tutorial.index');
})->middleware(['auth'])->name('tutorial');

// -----------------------------------------------------------------------------

require __DIR__.'/auth.php';