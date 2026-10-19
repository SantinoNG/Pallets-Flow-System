# ExecPlan 001: Scaffolding del módulo Carrito de Compras

_A living design & execution record for this feature._

Este documento es **autocontenido**: Opencode puede ejecutarlo de principio a fin sin otros documentos (solo se menciona `ai_workspace/contexto_proyecto.md` para actualizarlo al final).
Mantener `Progress`, `Surprises & Discoveries`, `Decision Log` y `Outcomes & Retrospective` actualizados durante la ejecución.

---

## Metadata
- **Owner:** Usuario / Opencode (ejecución)
- **Created:** 2026-10-17
- **Last Updated:** 2026-10-17
- **Agent / Module:** Laravel 12 — módulo Carrito (`app/Models/Cart*.php`, `app/Http/Controllers/Cart*Controller.php`, `routes/web.php`)
- **Related Plans:** ninguno (primer plan numerado). Plantilla base: `ai_workspace/PLANtemplate.md`
- **Plan Policy (if applicable):** no existe `PLANS.md` en la raíz; se siguen las secciones de `PLANtemplate.md`.
- **Working directory de todos los comandos:** `C:\laragon\www\Pallets-Flow-System` (PowerShell)

---

## Scaffolding estructural del Carrito de Compras (solo estructura, sin lógica de negocio)
Al terminar este plan existirán: una migración incremental sobre las tablas del carrito, modelos `Cart`/`CartItem`/`Customer`/`User` ajustados, dos controladores con métodos **vacíos** y seis rutas nombradas `cart.*` visibles en `php artisan route:list`.
"Hecho" = `route:list` muestra las rutas, `migrate` corre sin errores y `Cart::query()->count()` funciona en tinker (sin error de tabla `carts`).

---

## Purpose / Big Picture
Preparar el terreno para implementar el carrito del cliente (agregar/quitar artículos, ver el carrito, iniciar checkout hacia `Sale`). **Este plan NO implementa lógica**: ningún método de controlador contiene código funcional; solo firmas, comentarios `TODO` y rutas.

**Alcance excluido explícitamente:** vistas Blade, lógica de agregar/actualizar/eliminar, cálculo de totales, validación de stock, creación de `Sale`/`Receipt`, permisos finos, tests funcionales.

---

## Context and Orientation

**Stack:** Laravel 12, PHP ^8.2, Breeze (Blade/Tailwind/Alpine), `spatie/laravel-permission`, `owen-it/laravel-auditing`. Controladores tradicionales con validación inline, rutas escritas a mano agrupadas por módulo con `Route::middleware(['auth'])`. PKs con formato `id_<entidad>`.

**IMPORTANTE — lo que YA existe (no recrear):**
- Migración `database/migrations/2024_01_01_000011_create_cart_table.php`: tabla **`cart`** (singular) con `id_cart` (PK), `id_customer` (FK → `customers.id_customer`), `status` enum `active|completed|abandoned` (default `active`), timestamps.
- Migración `database/migrations/2024_01_01_000012_create_cart_items_table.php`: tabla `cart_items` con `id_cart_item` (PK), `id_cart` (FK), `id_article` (FK → `articles.id_article`), `quantity` (int), `unit_price` (decimal 10,2), timestamps.
- Migración `..._000013_create_sales_table.php` (`sales.id_cart` FK → `cart`) y `..._000014_create_receipts_table.php`.
- Modelos `app/Models/Cart.php`, `CartItem.php` (con relaciones básicas), `Customer::carts()`, `Article::cartItems()`, `Sale::cart()`.
- `database/seeders/SaleSeeder.php` inserta 1 carrito `completed` (id 1) con 2 ítems (artículos 1 y 3).

**Por lo tanto, "crear las tablas" se reduce a una migración incremental** (índices/unicidad), no a tablas nuevas. Ver Decision Log.

**Problemas conocidos que este plan corrige:**
- `Cart` no define `$table = 'cart'`; Eloquent usaría `carts` y fallaría.
- `User` no tiene relación hacia `Customer`; el controlador necesitará `auth()->user()->customer`.
- `CartItem` y `Cart` no definen `getRouteKeyName()` (necesario para route model binding con PK `id_cart_item`).

**Términos:**
- *Carrito activo*: fila de `cart` con `status = 'active'` para un `Customer`. Regla de negocio futura: máximo uno por cliente (no se aplica en este plan).
- *Checkout*: conversión futura del carrito `active` en una `Sale` + `Receipt`.

**Interfaces nuevas (nombres + firmas):**
- `App\Http\Controllers\CartController`: `show(Request $request)`, `clear(Request $request)`, `checkout(Request $request)`
- `App\Http\Controllers\CartItemController`: `store(Request $request)`, `update(Request $request, CartItem $cartItem)`, `destroy(CartItem $cartItem)`
- `App\Models\User::customer()` (hasOne), `App\Models\Customer::activeCart()` (hasOne filtrado por status)

---

## Plan of Work

### Paso 1 — Migración incremental de índices
**Crear** `database/migrations/2026_10_17_000001_add_indexes_to_cart_tables.php` con este contenido exacto:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Acelera la búsqueda del carrito activo de un cliente.
        Schema::table('cart', function (Blueprint $table) {
            $table->index(['id_customer', 'status'], 'cart_customer_status_index');
        });

        // Un artículo aparece una sola vez por carrito (la cantidad se incrementa, no se duplica la fila).
        Schema::table('cart_items', function (Blueprint $table) {
            $table->unique(['id_cart', 'id_article'], 'cart_items_cart_article_unique');
        });
    }

    public function down(): void {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique('cart_items_cart_article_unique');
        });

        Schema::table('cart', function (Blueprint $table) {
            $table->dropIndex('cart_customer_status_index');
        });
    }
};
```

- **Validación:** `php artisan migrate:status` lista la nueva migración como `Pending`; luego `php artisan migrate` la ejecuta sin error.
- **Idempotence & recovery:** si falla a la mitad, `php artisan migrate:rollback --step=1`. **No usar `migrate:fresh`** (borra datos de la BD de desarrollo). Si la BD ya contiene filas duplicadas `(id_cart, id_article)` en `cart_items`, la migración fallará: resolver los duplicados y reintentar. El seeder actual no genera duplicados.
- **Artifacts:** pegar en "Surprises & Discoveries" la salida de `php artisan migrate`.

### Paso 2 — Ajustes en modelos

**2a. Reemplazar por completo** `app/Models/Cart.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    public const STATUS_ACTIVE    = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ABANDONED = 'abandoned';

    // La tabla es singular ('cart'); sin esto Eloquent buscaría 'carts'.
    protected $table = 'cart';
    protected $primaryKey = 'id_cart';
    protected $fillable = ['id_customer', 'status'];

    public function getRouteKeyName(): string
    {
        return 'id_cart';
    }

    public function customer() {
        return $this->belongsTo(Customer::class, 'id_customer');
    }
    public function items() {
        return $this->hasMany(CartItem::class, 'id_cart');
    }
    public function sale() {
        return $this->hasOne(Sale::class, 'id_cart');
    }
}
```

**2b. Reemplazar por completo** `app/Models/CartItem.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $primaryKey = 'id_cart_item';
    protected $fillable = ['id_cart', 'id_article', 'quantity', 'unit_price'];

    public function getRouteKeyName(): string
    {
        return 'id_cart_item';
    }

    protected function casts(): array
    {
        return [
            'quantity'   => 'integer',
            'unit_price' => 'decimal:2',
        ];
    }

    public function cart() {
        return $this->belongsTo(Cart::class, 'id_cart');
    }
    public function article() {
        return $this->belongsTo(Article::class, 'id_article');
    }
}
```

**2c. Editar** `app/Models/Customer.php`: agregar este método dentro de la clase, justo después de `carts()`:

```php
    // Carrito activo del cliente (estructura; la regla "uno solo" se implementará después).
    public function activeCart() {
        return $this->hasOne(Cart::class, 'id_customer')->where('status', Cart::STATUS_ACTIVE);
    }
```

**2d. Editar** `app/Models/User.php`: agregar dentro de la clase, junto a `employee()`:

```php
    public function customer()
    {
        return $this->hasOne(Customer::class, 'id_user');
    }
```

- **Validación:** `php artisan tinker --execute="echo App\Models\Cart::query()->count();"` imprime un número (con el seeder: `1`) sin excepción `no such table: carts`. Antes del cambio este comando falla.
- **Idempotence:** los archivos se reescriben completos; repetir el paso es seguro. En 2c/2d, verificar con búsqueda que el método no esté ya agregado antes de insertarlo.

### Paso 3 — Controladores (solo estructura)

**3a. Crear** `app/Http/Controllers/CartController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    // READ — Muestra el carrito activo del cliente autenticado
    public function show(Request $request)
    {
        // TODO: obtener $request->user()->customer->activeCart con items.article y devolver view('cart.show')
    }

    // DELETE — Vacía el carrito activo del cliente autenticado
    public function clear(Request $request)
    {
        // TODO: eliminar los ítems del carrito activo y redirigir a cart.show
    }

    // CHECKOUT — Convierte el carrito activo en una venta (Sale + Receipt)
    public function checkout(Request $request)
    {
        // TODO: validar stock, crear Sale y Receipt, marcar el carrito como completed
    }
}
```

**3b. Crear** `app/Http/Controllers/CartItemController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use Illuminate\Http\Request;

class CartItemController extends Controller
{
    // CREATE — Agrega un artículo al carrito activo (espera id_article y quantity)
    public function store(Request $request)
    {
        // TODO: validar id_article/quantity, obtener o crear el carrito activo, crear o incrementar CartItem
    }

    // UPDATE — Cambia la cantidad de un ítem del carrito
    public function update(Request $request, CartItem $cartItem)
    {
        // TODO: validar quantity y que el ítem pertenezca al carrito del cliente autenticado
    }

    // DELETE — Quita un ítem del carrito
    public function destroy(CartItem $cartItem)
    {
        // TODO: validar propiedad del ítem y eliminarlo
    }
}
```

- **Validación:** `php -l app/Http/Controllers/CartController.php` y `php -l app/Http/Controllers/CartItemController.php` → "No syntax errors detected".
- **Idempotence:** si el archivo ya existe, **no sobrescribir** sin revisar su contenido; este plan asume que no existen.

### Paso 4 — Rutas

**Editar** `routes/web.php`:

1. Agregar a los `use` del inicio (junto a los demás controladores):
```php
use App\Http\Controllers\CartController;
use App\Http\Controllers\CartItemController;
```

2. Insertar este bloque **inmediatamente antes** de la línea `require __DIR__.'/auth.php';` (después del módulo de proveedores):
```php
// -----------------------------------------------------------------------------
// MÓDULO DE CARRITO DE COMPRAS (estructura — la lógica se implementa en otro plan)
// -----------------------------------------------------------------------------
Route::middleware(['auth'])->group(function () {
    Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
    Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');
    Route::post('/cart/checkout', [CartController::class, 'checkout'])->name('cart.checkout');

    Route::post('/cart/items', [CartItemController::class, 'store'])->name('cart.items.store');
    Route::patch('/cart/items/{cartItem}', [CartItemController::class, 'update'])->name('cart.items.update');
    Route::delete('/cart/items/{cartItem}', [CartItemController::class, 'destroy'])->name('cart.items.destroy');
});
```

- **Validación:** `php artisan route:list --name=cart` debe mostrar exactamente 6 rutas (`cart.show`, `cart.clear`, `cart.checkout`, `cart.items.store`, `cart.items.update`, `cart.items.destroy`) apuntando a los controladores nuevos.
- **Nota:** el `{cartItem}` se resuelve por `id_cart_item` gracias al paso 2b. Solo se usa middleware `auth`; el control por rol (p. ej. `role:Cliente`) queda para el plan de lógica (ver Risks / Open Questions).

### Paso 5 — Verificación final y documentación
Ejecutar en orden:
```powershell
php artisan migrate:status
php artisan route:list --name=cart
php artisan tinker --execute="echo App\Models\Cart::with('items')->first()->items->count();"
php artisan test
```
Resultados esperados: migración nueva `Ran`; 6 rutas `cart.*`; el tinker imprime `2` (carrito sembrado con 2 ítems; si la BD no tiene el seeder, puede fallar por `null` — en ese caso usar `Cart::query()->count()`); `php artisan test` sin nuevos fallos respecto a la línea base.

Luego **actualizar** `ai_workspace/contexto_proyecto.md`:
- Sección 4: `Cart` ahora declara `$table = 'cart'`; `CartItem` con casts y route key; `Customer::activeCart()`; `User::customer()`.
- Sección 5: agregar la migración `2026_10_17_000001_add_indexes_to_cart_tables`.
- Sección 6: módulo Carrito → "Estructura creada (controladores y rutas vacíos)".
- Sección 7: quitar el hallazgo 1 y el 5.
- Sección 9: estado de `001_plan_carrito_compras.md` → "Ejecutado".
- Marcar los pasos en `Progress` de este archivo.

- **Artifacts:** pegar en este archivo (Outcomes) la salida de `route:list --name=cart` y de `migrate:status`.

---

## Progress
Track all steps with checkboxes and timestamps (UTC).

- [x] (2026-10-17) Plan redactado a partir del análisis del repositorio.
- [ ] Paso 1 — Migración incremental de índices creada y ejecutada.
- [ ] Paso 2 — Modelos `Cart`, `CartItem`, `Customer`, `User` ajustados.
- [ ] Paso 3 — `CartController` y `CartItemController` creados (métodos vacíos).
- [ ] Paso 4 — Rutas `cart.*` registradas en `routes/web.php`.
- [ ] Paso 5 — Verificación ejecutada y `contexto_proyecto.md` actualizado.

_Last updated: 2026-10-17_

---

## Surprises & Discoveries

- **Observation:** las tablas `cart` y `cart_items` y los modelos `Cart`/`CartItem` ya existían antes de este plan.
- **Evidence:** `database/migrations/2024_01_01_000011_create_cart_table.php`, `..._000012_create_cart_items_table.php`, `app/Models/Cart.php`, `app/Models/CartItem.php`.

- **Observation:** `Cart` no define `$table`, y la tabla es `cart` (singular).
- **Evidence:** `app/Models/Cart.php` solo define `$primaryKey` y `$fillable`.

---

## Decision Log

- **Decision:** No crear nuevas tablas ni migraciones de creación; solo una migración incremental de índices.
- **Rationale:** las tablas del carrito ya existen y ya están referenciadas por `sales` (FK `id_cart`) y por el seeder; recrearlas rompería datos y dependencias.
- **Date/Author:** 2026-10-17 - Claude

- **Decision:** Mantener el nombre de tabla `cart` (singular) y corregir el modelo con `$table`, en vez de renombrar la tabla.
- **Rationale:** renombrar requeriría migración destructiva y tocar `sales`, el seeder y los modelos; el cambio mínimo es declarar `$table`.
- **Date/Author:** 2026-10-17 - Claude

- **Decision:** Validación inline con `Request` (sin `FormRequest`) en los futuros métodos.
- **Rationale:** es el patrón de los controladores en uso (`ArticleController`, `SupplierController`); los `FormRequest` existentes son stubs huérfanos.
- **Date/Author:** 2026-10-17 - Claude

- **Decision:** Dos controladores (`CartController` para el carrito, `CartItemController` para sus ítems) y rutas escritas a mano, sin `Route::resource`.
- **Rationale:** separa responsabilidades y sigue la convención del `routes/web.php` actual.
- **Date/Author:** 2026-10-17 - Claude

- **Decision:** No crear `CartPolicy` ni vistas en este plan.
- **Rationale:** quedan fuera del alcance de "solo scaffolding" solicitado (tablas, modelos, controladores, rutas).
- **Date/Author:** 2026-10-17 - Claude

---

## Outcomes & Retrospective
_Completar al terminar la ejecución._

### Result
- [ ] Success
- [ ] Partial success
- [ ] Abandoned

### Summary
_(pendiente)_

### Lessons Learned
_(pendiente)_

### Follow-up Items
- Plan 002: lógica del carrito (agregar/actualizar/eliminar, totales con `unit_price` congelado al agregar, validación contra `product_stock`).
- Plan 003: checkout → `Sale` + `Receipt` dentro de transacción y descuento de stock.
- Vistas Blade (`resources/views/cart/show.blade.php`) y enlace en `layouts/navigation.blade.php`.

---

## Risks / Open Questions

- **No existe `.env`** en el repositorio: confirmar si la BD es SQLite o MySQL (Laragon) antes de `php artisan migrate`. Con MySQL, el índice único compuesto y el FK conviven sin problema (los FK originales ya crearon sus propios índices).
- **Rol del cliente:** `RoleSeeder` define el rol `Cliente` en la tabla propia `roles`, pero no se verificó que exista el rol equivalente en Spatie (`spatie_roles`). Decidir antes del plan 002 si las rutas `cart.*` llevan `middleware('role:Cliente')`.
- **Usuario sin `Customer`:** un `User` autenticado que no sea cliente (p. ej. empleado) tendrá `customer = null`; el plan 002 debe definir qué hacer.
- **Migración de índices con datos duplicados:** puede fallar si hay filas repetidas `(id_cart, id_article)`; ver Paso 1.
- **Recovery general:** `php artisan migrate:rollback --step=1` revierte la migración; los archivos de modelos/controladores/rutas se revierten con `git checkout -- <archivo>` o eliminando los archivos nuevos. Hacer commit previo recomendado.

---

## Next Steps / Handoff Notes

- Al finalizar: Opencode actualiza `ai_workspace/contexto_proyecto.md` (ver Paso 5) y marca `Progress`.
- Crear `002_plan_logica_carrito.md` con la lógica de negocio usando `PLANtemplate.md`.
