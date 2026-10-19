# Contexto del Proyecto: Pallets-Flow-System

> **Fuente de la verdad del proyecto.** Escrito inicialmente el 2026-10-17 por Claude (análisis de solo lectura del repositorio).
> **Mantenimiento:** Opencode debe actualizar este archivo al terminar cada cambio en el sistema (nuevos modelos, migraciones, rutas, módulos, decisiones).
> **Ruta del repositorio:** `C:\laragon\www\Pallets-Flow-System`

---

## 1. Descripción general

Sistema de gestión para una empresa de producción/venta de pallets (artículos terminados fabricados a partir de materias primas). Cubre:

- Catálogo de **artículos** (productos terminados) con stock y receta de materias primas.
- **Proveedores** y **materias primas** con stock.
- **Empleados**, **clientes** y **usuarios** con roles.
- Flujo de venta: **carrito → venta → comprobante** (el modelo de datos ya existe; la capa HTTP del carrito aún no).

El `README.md` es genérico/desactualizado (menciona `/var/www/html/sistema-gestion/`); no es fiable como documentación.

## 2. Stack tecnológico

| Componente | Detalle |
|---|---|
| Lenguaje | PHP `^8.2` |
| Framework | Laravel `^12.0` |
| Frontend | Blade + Tailwind CSS + Alpine.js (Laravel Breeze), Vite (`vite.config.js`, `tailwind.config.js`, `postcss.config.js`) |
| Auth | Laravel Breeze (rutas en `routes/auth.php`, controladores en `app/Http/Controllers/Auth`) |
| Roles/permisos | `spatie/laravel-permission ^8.0` (alias de middleware `role`, `permission`, `role_or_permission` en `bootstrap/app.php`) |
| Auditoría | `owen-it/laravel-auditing ^14.0` (`config/audit.php`); hoy solo `User` implementa `Auditable` |
| Livewire | `livewire/livewire ^4.4` instalado, **pero no hay componentes Livewire** en `app/` |
| Localización | `laravel-lang/common` (dev); carpetas `lang/en` y `lang/es` |
| Tests | PHPUnit `^11.5` (solo tests por defecto de Breeze + ejemplos) |
| Calidad | Laravel Pint |
| Entorno | Laragon (Windows) |

### Base de datos
- `config/database.php`: conexión por defecto `env('DB_CONNECTION', 'sqlite')`.
- `.env.example`: `DB_CONNECTION=sqlite`; `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`, `CACHE_STORE=database`, `APP_LOCALE=en`.
- **No existe `.env` en la raíz del repositorio** (lectura fallida), por lo que la conexión real en uso (SQLite o MySQL de Laragon) **no pudo confirmarse**. Verificar antes de ejecutar migraciones.

## 3. Arquitectura y patrones

- MVC clásico de Laravel. **No hay capa de servicios/repositorios**: la lógica vive en los controladores ("fat controllers").
- Validación **inline** con `$request->validate()` en los controladores existentes (`ArticleController`, `SupplierController`). Existen `StoreProductRequest`/`UpdateProductRequest` pero son stubs sin usar.
- Los controladores comentan los pasos de diagramas de secuencia (`DSD-1`, `DSD-2`, `DSD-3`, `DSD-6`...) y los comentarios están en español.
- Rutas CRUD escritas **a mano** (no `Route::resource`), agrupadas por módulo bajo `Route::middleware(['auth'])`, con nombres `modulo.accion`.
- Convención de claves primarias: `id_<entidad>` (p. ej. `id_article`, `id_cart`), con `getRouteKeyName()` sobreescrito en los modelos que se usan en route model binding.
- Tablas pivote/stock con PK = FK (`product_stock.id_article`, `raw_material_stock.id_raw_material`; `$incrementing = false`).
- Nombres de tabla irregulares: `cart` (singular), `product_stock`, `raw_material_stock` (singulares) vs. `articles`, `cart_items`, `sales` (plurales).

## 4. Modelos y relaciones (`app/Models`)

| Modelo | Tabla | PK | Relaciones |
|---|---|---|---|
| `User` | `users` | `id_user` | `hasOne Employee`, `hasOne Customer` (`customer()`), `belongsTo Role` (`role()`, rol de negocio). Usa `HasRoles` (Spatie) y `Auditable`. `$rememberTokenName = null`. `isAdmin()` delega en Spatie (`Jefe` o `Administrador`). `asignarRol($nombre)` escribe **los dos** sistemas de roles (ver sección 6). |
| `Role` (propio) | `roles` | `id_role` | `hasMany User`, `hasMany Employee` |
| `Employee` | `employees` | `id_employee` | `belongsTo User`, `belongsTo Role`, `hasMany Sale` |
| `Customer` | `customers` | `id_customer` | `belongsTo User`, `hasMany Cart` (`carts`), `hasMany Sale` |
| `Supplier` | `suppliers` | `id_supplier` | `hasMany RawMaterial` |
| `RawMaterial` | `raw_materials` | `id_raw_material` | `belongsTo Supplier`, `hasOne RawMaterialStock`, `belongsToMany Article` (pivote `article_materials`, `quantity_required`) |
| `RawMaterialStock` | `raw_material_stock` | `id_raw_material` | `belongsTo RawMaterial` |
| `Article` | `articles` | `id_article` | `hasOne ProductStock`, `belongsToMany RawMaterial` (pivote `article_materials`), `hasMany CartItem` |
| `ProductStock` | `product_stock` | `id_article` | `belongsTo Article` |
| `Cart` | `cart` | `id_cart` | `belongsTo Customer`, `hasMany CartItem` (`items`), `hasOne Sale`. Declara `protected $table = 'cart'`. |
| `CartItem` | `cart_items` | `id_cart_item` | `belongsTo Cart`, `belongsTo Article`. Con `getRouteKeyName()`. |
| `Sale` | `sales` | `id_sale` | `belongsTo Cart`, `belongsTo Customer`, `belongsTo Employee`, `hasOne Receipt` |
| `Receipt` | `receipts` | `id_receipt` | `belongsTo Sale` |

Diagrama lógico: `User 1─1 Employee | Customer` · `Customer 1─N Cart 1─N CartItem N─1 Article` · `Cart 1─1 Sale 1─1 Receipt` · `Sale N─1 Employee` · `Article N─M RawMaterial` · `Article 1─1 ProductStock` · `Supplier 1─N RawMaterial 1─1 RawMaterialStock`.

## 5. Migraciones (`database/migrations`)

Orden actual: `roles` → `users` (con `id_role` NOT NULL) → `employees` → `customers` → `suppliers` → `raw_materials` → `raw_material_stock` → `articles` → `product_stock` → `article_materials` → **`cart`** (`id_customer`, `status` enum `active|completed|abandoned` default `active`) → **`cart_items`** (`id_cart`, `id_article`, `quantity`, `unit_price`) → `sales` (`id_cart`, `id_customer`, `id_employee` nullable, `payment_method`) → `receipts` (`id_sale`, `issue_date`, `due_date`) → `2026_06_21_225300_create_permission_tables` (Spatie) → `2026_06_21_230236_create_cache_table`.

Notas:
- Las migraciones de dominio están fechadas `2024_01_01_*` (convención manual de orden), las de paquetes `2026_06_21_*`.
- `config/permission.php` mapea la tabla de roles de Spatie a `spatie_roles` para no chocar con la tabla propia `roles`. Además la clave `'permissions'` aparece duplicada dentro de `table_names` (la segunda sobreescribe a la primera → tabla `permissions`).
- `cart_items` no tiene restricción única `(id_cart, id_article)`; `cart` no tiene índice `(id_customer, status)`.
- Seeders: `RoleSeeder`, `UserSeeder`, `SupplierSeeder`, `RawMaterialSeeder`, `ArticleSeeder`, `SaleSeeder` (crea un carrito `completed`, sus ítems, una venta y un comprobante). Usuarios semilla: admin, vendedor, depósito, cliente.

## 6. Módulos actuales

| Módulo | Estado | Archivos clave |
|---|---|---|
| Autenticación / Perfil (Breeze) | Funcional | `routes/auth.php`, `Auth/*`, `ProfileController` |
| Usuarios y asignación de roles | Funcional. **Dos capas sincronizadas**: `users.id_role` (rol de negocio, FK simple) y `model_has_roles` (Spatie, capa de autorización). Se asignan siempre juntas con `User::asignarRol()`. `spatie_roles` es el espejo de `roles`. | `UserController`, `RoleController`, vistas `users/*`, `roles/*` |
| Roles | CRUD completo. Al crear/editar/borrar un rol se mantiene el espejo en `spatie_roles` | `RoleController`, vistas `roles/*` |
| Empleados | CRUD | `EmployeeController`, vistas `employees/*` |
| Artículos (+ stock + materias primas) | CRUD | `ArticleController`, vistas `articles/*` |
| Proveedores (+ materias primas + stock) | CRUD | `SupplierController`, vistas `suppliers/*` |
| Tutorial / Guía técnica | Vista estática | `resources/views/tutorial/index.blade.php` |
| **Carrito de compras** | **Funcional** (controlador, rutas `cart.*` y vistas) | `CartController`, vistas `cart/*` |
| Ventas / Comprobantes | Solo capa de datos (modelos + migraciones + seeder). Sin `SaleController` ni vistas propias: el comprobante se ve desde `cart.receipt` | `Sale`, `Receipt` |

## 7. Deuda técnica y hallazgos a tener en cuenta

1. **RESUELTO** — `Cart` ya declara `protected $table = 'cart'`.
2. **PENDIENTE** — `ProductController`, `ProductPolicy`, `ProductFactory`, `StoreProductRequest` y `UpdateProductRequest` referencian `App\Models\Product`, que **no existe**; son restos de `artisan make:*` (el módulo se renombró a `Article`). No hay ruta que los use, pero `ProductFactory` revienta si alguien lo invoca.
3. **RESUELTO** — El catálogo de roles quedó alineado: `RoleSeeder` crea `Jefe`, `Administrador`, `Encargado`, `Cliente`, `Vendedor`, `Deposito` (los dos últimos faltaban y se agregaron). `spatie_roles` ahora se puebla como espejo del catálogo, así que `@role(...)` y el middleware `role:` funcionan. El `Supervisor` inexistente se reemplazó por `Jefe|Administrador`.
4. **RESUELTO** — `RegisteredUserController` asigna el rol con `User::asignarRol()`, que escribe `users.id_role` y el espejo en Spatie. `id_role` sigue fuera del `$fillable` a propósito.
5. **RESUELTO** — `User` ahora tiene `hasOne Customer` (`customer()`).
6. **RESUELTO** — Livewire se usa en el dashboard (`app/Livewire/Dashboard.php` + `views/livewire/dashboard.blade.php`).
7. **PARCIAL** — Tests: los de Breeze más `CartTest` (dominio). Faltan tests de artículos, proveedores, empleados y roles.
8. **PENDIENTE** — El dashboard (`app/Livewire/Dashboard.php`) tiene los datos de los charts **hardcodeados** (`$totalVentas = 348000` y arrays fijos). Hay que conectarlos a la base.
9. **PENDIENTE** — `layouts/navigation.blade.php` (navbar viejo de Breeze) no lo incluye ninguna vista: es código muerto. `layouts/app.blade.php` es el sidebar real.
10. **PENDIENTE** — Módulo de Ventas: no existe `SaleController` ni vistas de ventas, aunque `routes/web.php` importa `SaleController` (import muerto). Solo se llega a la venta por el flujo del carrito.
11. **PENDIENTE** — `PLANtemplate.md` se referencia en la sección 8 pero **no existe** en `ai_workspace/`.

## 8. Convenciones para cambios futuros

- Mantener PKs `id_<entidad>` y `getRouteKeyName()` en modelos usados con route model binding.
- Comentarios en español; seguir el estilo de los controladores existentes.
- **Roles**: el catálogo es la tabla `roles` (columna `role_name`). Cualquier rol nuevo se crea ahí y se refleja en `spatie_roles`; a los usuarios se les asigna con `User::asignarRol($nombre)`, que escribe en los dos sistemas a la vez. No usar `syncRoles()` ni `assignRole()` por separado.
- **Autorización de usuarios/roles**: `User::isAdmin()` (Jefe o Administrador). Para proteger rutas con el middleware de Spatie usar `->middleware('role:Jefe|Administrador')`.
- Registrar cada cambio nuevo en este archivo (sección 4, 5, 6) y crear el plan siguiente en `ai_workspace/NNN_plan_<tema>.md` siguiendo `PLANtemplate.md`.

## 9. Planes de `ai_workspace`

| Archivo | Descripción | Estado |
|---|---|---|
| `PLANtemplate.md` | Plantilla ExecPlan (openai-codex-exec-plan) | Referencia. **No está en el repositorio** (ver deuda técnica 11) |
| `PLAN.md` | Ejemplo de ExecPlan completado (del repo origen, no pertenece a este proyecto) | Referencia |
| `001_plan_carrito_compras.md` | Scaffolding del Carrito de Compras | **Ejecutado** (commits `eb8fed6`, `2c57837`, `f23f1b7`). Los checkboxes de "Progress" siguen sin marcar |
