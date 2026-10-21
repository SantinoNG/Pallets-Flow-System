<?php
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\DashboardController;

// -----------------------------------------------------------------------------
// RUTAS PÚBLICAS
// -----------------------------------------------------------------------------
Route::get('/', function () {
    return view('welcome');
});

// -----------------------------------------------------------------------------
// RUTAS BÁSICAS DE AUTENTICACIÓN (Cualquiera que inicie sesión)
// -----------------------------------------------------------------------------
// El panel de control cambia segun el rol: el del negocio (KPIs de la
// empresa, stock, proveedores) para el personal, y uno personal con sus
// compras para el Cliente. Ver DashboardController.
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

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
Route::middleware(['auth', 'role:Jefe|Administrador'])->group(function () {
    Route::get('/usuarios', [UserController::class, 'index'])->name('users.index');
});

/* * NIVEL 2: Escritura/Edición.
 * Estas rutas son críticas. Las dejamos en un grupo exclusivo donde SOLO
 * el Administrador puede entrar a ver el formulario y guardar cambios.
 */
Route::middleware(['auth', 'role:Jefe|Administrador'])->group(function () {
    Route::get('/usuarios/pdf', [UserController::class, 'exportPdf'])->name('users.pdf');
    Route::get('/usuarios/{user}/roles', [UserController::class, 'editRoles'])->name('users.roles.edit');
    Route::put('/usuarios/{user}/roles', [UserController::class, 'updateRoles'])->name('users.roles.update');
});


Route::get('/tutorial', function () {
    return view('tutorial.index');
})->middleware(['auth'])->name('tutorial');


// -----------------------------------------------------------------------------
// MÓDULO DE ROLES (Solo Administrador)
// -----------------------------------------------------------------------------
// Control por rol: sin esto, cualquier usuario logueado (incluso el Cliente)
// podia crear, editar y BORRAR roles del sistema.
Route::middleware(['auth', 'role:Jefe|Administrador'])->group(function () {
    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
});

// -----------------------------------------------------------------------------
// MÓDULO DE EMPLEADOS
// -----------------------------------------------------------------------------
// El Depósito gestiona stock, los empleados los ve el Encargado y el
// Administrador. El Cliente y el Vendedor no entran.
Route::middleware(['auth', 'role:Jefe|Administrador|Encargado'])->group(function () {
    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
    Route::get('/employees/pdf', [EmployeeController::class, 'exportPdf'])->name('employees.pdf');
    Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
    Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
    Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
    Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
});

// -----------------------------------------------------------------------------
// MÓDULO DE ARTÍCULOS
// -----------------------------------------------------------------------------
// Control de acceso por rol (middleware de Spatie, registrado en
// bootstrap/app.php con el alias 'role').
//
// El CATÁLOGO se puede ver con cualquier usuario autenticado: el Cliente
// necesita ver los productos y sus precios para comprar. Lo que no puede es
// GESTIONAR el inventario: eso sigue siendo del Jefe, Administrador,
// Encargado y Depósito.
//
// El middleware de Spatie lee la tabla `spatie_roles`, que se puebla como
// espejo del catálogo de `roles` (ver RoleSeeder y User::asignarRol()).
Route::middleware(['auth'])->group(function () {
    Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
});

Route::middleware(['auth', 'role:Jefe|Administrador|Encargado|Deposito'])->group(function () {
    Route::get('/articles/create', [ArticleController::class, 'create'])->name('articles.create');
    Route::get('/articles/pdf', [ArticleController::class, 'exportPdf'])->name('articles.pdf');
    Route::post('/articles', [ArticleController::class, 'store'])->name('articles.store');
    Route::get('/articles/{article}/edit', [ArticleController::class, 'edit'])->name('articles.edit');
    Route::put('/articles/{article}', [ArticleController::class, 'update'])->name('articles.update');
    Route::delete('/articles/{article}', [ArticleController::class, 'destroy'])->name('articles.destroy');
});

// -----------------------------------------------------------------------------
// MÓDULO DE PROVEEDORES
// -----------------------------------------------------------------------------
// Los proveedores y las materias primas los gestionan Jefe, Administrador
// y Encargado. El Depósito sólo consulta stock, no gestiona proveedores.
Route::middleware(['auth', 'role:Jefe|Administrador|Encargado'])->group(function () {
    Route::get('/proveedores/pdf', [SupplierController::class, 'exportPdf'])->name('suppliers.pdf');
    Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::get('/suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
    Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::get('/suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
    Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
});

// -----------------------------------------------------------------------------
// MÓDULO DE VENTAS / CARRITO
// -----------------------------------------------------------------------------
// El middleware `carrito.propio` va DENTRO del grupo: si se encadena
// después del ->group(), Laravel ya registró las rutas con los atributos
// anteriores y el middleware no se aplica (se ve en route:list -v).
Route::middleware(['auth', 'carrito.propio'])->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart', [CartController::class, 'createOrGetCart'])->name('cart.create');
    Route::get('/cart/{cart}', [CartController::class, 'show'])->name('cart.show');
    Route::post('/cart/{cart}/items', [CartController::class, 'addItem'])->name('cart.addItem');
    Route::delete('/cart/{cart}/items/{item}', [CartController::class, 'removeItem'])->name('cart.removeItem');
    Route::get('/cart/{cart}/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
    Route::post('/cart/{cart}/confirm', [CartController::class, 'confirmSale'])->name('cart.confirm');
    Route::get('/sales/{sale}/receipt', [CartController::class, 'showReceipt'])->name('cart.receipt');
});

// -----------------------------------------------------------------------------
// CARRITO DEL CLIENTE
// -----------------------------------------------------------------------------
// El flujo del mostrador (/cart) es para el personal: elige a qué cliente le
// está vendiendo. Para el rol Cliente el carrito es el suyo: no elige a quién
// le compra, y no ve el selector de clientes.
//
// Estas rutas resuelven el carrito del cliente a partir de su ficha, así que
// un Cliente no puede meter cosas en el carrito de otro.
Route::middleware(['auth', 'role:Cliente'])->group(function () {
    Route::get('/mi-carrito', [CartController::class, 'miCarrito'])->name('cart.mine');
    Route::post('/mi-carrito/items', [CartController::class, 'agregarAlMiCarrito'])->name('cart.mine.addItem');
});

// -----------------------------------------------------------------------------
// MÓDULO DE VENTAS
// -----------------------------------------------------------------------------
// Listado, detalle y comprobante en PDF de las ventas realizadas.
//
// Antes solo se llegaba a una venta por el flujo del carrito, y el import de
// SaleController en este archivo no correspondía a ningún archivo existente.
Route::middleware(['auth'])->group(function () {
    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
    Route::get('/sales/{sale}/pdf', [SaleController::class, 'pdf'])->name('sales.pdf');
});
// -----------------------------------------------------------------------------
require __DIR__.'/auth.php';