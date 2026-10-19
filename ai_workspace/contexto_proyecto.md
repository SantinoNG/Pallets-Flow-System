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
| `User` | `users` | `id_user` | `hasOne Employee`. Usa `HasRoles` (Spatie) y `Auditable`. `$rememberTokenName = null`. **No tiene relación a `Customer`.** |
| `Role` (propio) | `roles` | `id_role` | `hasMany User`, `hasMany Employee` |
| `Employee` | `employees` | `id_employee` | `belongsTo User`, `belongsTo Role`, `hasMany Sale` |
| `Customer` | `customers` | `id_customer` | `belongsTo User`, `hasMany Cart` (`carts`), `hasMany Sale` |
| `Supplier` | `suppliers` | `id_supplier` | `hasMany RawMaterial` |
| `RawMaterial` | `raw_materials` | `id_raw_material` | `belongsTo Supplier`, `hasOne RawMaterialStock`, `belongsToMany Article` (pivote `article_materials`, `quantity_required`) |
| `RawMaterialStock` | `raw_material_stock` | `id_raw_material` | `belongsTo RawMaterial` |
| `Article` | `articles` | `id_article` | `hasOne ProductStock`, `belongsToMany RawMaterial` (pivote `article_materials`), `hasMany CartItem` |
| `ProductStock` | `product_stock` | `id_article` | `belongsTo Article` |
| `Cart` | `cart` | `id_cart` | `belongsTo Customer`, `hasMany CartItem` (`items`), `hasOne Sale`. **Falta `protected $table = 'cart'`** (Laravel inferiría `carts`). |
| `CartItem` | `cart_items` | `id_cart_item` | `belongsTo Cart`, `belongsTo Article`. Sin `getRouteKeyName()` ni casts. |
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
| Usuarios y asignación de roles (Spatie) | Funcional | `UserController`, vistas `users/*` |
| Roles | Rutas y controlador presentes | `RoleController`, vistas `roles/*` |
| Empleados | CRUD | `EmployeeController`, vistas `employees/*` |
| Artículos (+ stock + materias primas) | CRUD | `ArticleController`, vistas `articles/*` |
| Proveedores (+ materias primas + stock) | CRUD | `SupplierController`, vistas `suppliers/*` |
| Tutorial / Guía técnica | Vista estática | `resources/views/tutorial/index.blade.php` |
| **Carrito de compras** | **Solo capa de datos** (migraciones + modelos); sin controladores, rutas ni vistas | ver `001_plan_carrito_compras.md` |
| Ventas / Comprobantes | Solo capa de datos (modelos + migraciones + seeder) | `Sale`, `Receipt` |

## 7. Deuda técnica y hallazgos a tener en cuenta

1. `Cart` no declara `$table = 'cart'` → cualquier consulta Eloquent fallará con "table carts not found".
2. `ProductController`, `ProductPolicy`, `StoreProductRequest` y `UpdateProductRequest` referencian `App\Models\Product`, que **no existe**; son restos de `artisan make:*` (policies/requests con `authorize() = false`). No hay ruta que los use.
3. `RoleSeeder` crea los roles `Jefe`, `Administrador`, `Encargado`, `Cliente`, pero `navigation.blade.php` usa `@hasanyrole('Administrador|Supervisor')` (`Supervisor` no existe) y los comentarios de `UserSeeder` hablan de "Vendedor/Depósito". No se verificó si los roles Spatie están sembrados en `spatie_roles`.
4. `users.id_role` es NOT NULL pero `id_role` no está en `User::$fillable` (no se revisó `RegisteredUserController`).
5. `User` no tiene relación `customer()`; hoy no hay forma directa de obtener el `Customer` del usuario autenticado.
6. Livewire está instalado y sin uso; el resto del código es Blade + controladores tradicionales.
7. Tests: solo los de Breeze; ningún test de dominio.

## 8. Convenciones para cambios futuros

- Mantener PKs `id_<entidad>` y `getRouteKeyName()` en modelos usados con route model binding.
- Comentarios en español; seguir el estilo de los controladores existentes.
- Registrar cada cambio nuevo en este archivo (sección 4, 5, 6) y crear el plan siguiente en `ai_workspace/NNN_plan_<tema>.md` siguiendo `PLANtemplate.md`.

## 9. Planes de `ai_workspace`

| Archivo | Descripción | Estado |
|---|---|---|
| `PLANtemplate.md` | Plantilla ExecPlan (openai-codex-exec-plan) | Referencia |
| `PLAN.md` | Ejemplo de ExecPlan completado (del repo origen, no pertenece a este proyecto) | Referencia |
| `001_plan_carrito_compras.md` | Scaffolding del Carrito de Compras | Pendiente de ejecución |
