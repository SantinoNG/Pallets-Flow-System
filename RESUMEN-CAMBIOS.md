# Resumen de modificaciones �?? Pallets Flow System

Proyecto: `C:\laragon\www\Pallets-Flow-System`
Fecha: 15 de octubre de 2026

**19 archivos modificados · 3 migraciones nuevas · 541 líneas agregadas, 103 eliminadas**

Nada fue commiteado. Los cambios están en el working tree para poder revisarlos
antes de hacer `git commit`.

---

## Resultado

| Indicador | Antes | Después |
|---|---|---|
| Tests | 5 pasan / 20 fallan | **25 pasan / 0 fallan** |
| Rutas que devuelven HTTP 500 | 4+ | **0** |
| Tablas en la base | 21 (3 a mano) | **28 (todas por migración)** |
| `php artisan migrate:fresh` | Rompía la base | **Deja la base completa y funcional** |
| Asignar roles a un usuario | Renderizaba 0 opciones | **6 opciones, funciona** |
| Registro de usuario nuevo | HTTP 500 | **Funciona** |
| Editar el propio perfil | Error "email ya registrado" | **Funciona** |
| Usuario no-admin cambia roles | **Se lo podía** | **403 Forbidden** |

---

## Los 3 errores críticos

### 1. El módulo de roles no funcionaba

**Problema**

`UserController` importaba el modelo de Spatie:

```php
use Spatie\Permission\Models\Role;   // lee la tabla `spatie_roles`
```

El proyecto tiene **dos clases `Role` sobre tablas distintas**:

| Clase | Tabla | Contenido |
|---|---|---|
| `Spatie\Permission\Models\Role` | `spatie_roles` | **vacía** |
| `App\Models\Role` | `roles` | Jefe, Administrador, Encargado, Cliente |

Como `UserController` usaba la primera, `Role::all()` devolvía cero filas y la
página de asignación de roles se renderizaba **sin una sola opción**. La
funcionalidad directamente no existía.

`RoleController`, en cambio, sí usaba `App\Models\Role` �?? por eso el CRUD de
roles sí andaba y el resto del proyecto no daba señales.

**Solución**

- `UserController` usa `App\Models\Role` (tabla `roles`), el sistema real del proyecto.
- `syncRoles()` de Spatie reemplazado por actualización de `users.id_role`.
- La vista pasó de checkboxes a un `<select>`, porque `id_role` es una Foreign
  Key simple: **un** rol por usuario, no varios.
- Se documentó en `User` que conviven dos sistemas de roles distintos y cuál es el real.

### 2. Los roles de los usuarios estaban mal asignados

**Problema**

`UserSeeder` asignaba el rol escribiendo el ID a mano:

```php
'id_role' => 1,   // el comentario decía "1=Admin"
```

Pero los IDs los genera `auto_increment`, así que el `1` no era "Admin": era el
primer rol que creó `RoleSeeder`, que es **Jefe**. Y como el orden de inserción
no está garantizado, el resultado real era:

```
admin@ecommerce.com     -> Jefe
vendedor@ecommerce.com  -> Administrador    <-- escalada de privilegios
deposito@ecommerce.com  -> Encargado
cliente@gmail.com       -> Cliente
```

Es decir: **el usuario vendedor tenía permisos de administrador.**

**Solución**

- `RoleSeeder` conserva los 4 roles originales y agrega `Vendedor` y `Deposito`,
  que el `UserSeeder` daba por supuestos pero nunca existieron.
- `UserSeeder` ahora busca el rol **por nombre** con un helper `roleId()` que
  corta con un mensaje explícito si el rol no existe, en vez de adivinar un ID.
- `UserSeeder` busca los usuarios **por email** para armar `employees` y
  `customers`, en vez de asumir `id_user` 1, 2, 3.
- `updateOrInsert` en lugar de `insert`, para que se pueda re-sembrar.

**Verificado**

```
admin@ecommerce.com     -> Administrador
vendedor@ecommerce.com  -> Vendedor
deposito@ecommerce.com  -> Deposito
cliente@gmail.com       -> Cliente
```

### 3. Los 20 tests estaban rotos

**Problema**

Dos causas acumuladas:

a) La tabla `users` no tenía `email_verified_at` ni `remember_token`. Las
asumía tanto `User::casts()` como `UserFactory`.

```
table users has no column named email_verified_at
```

b) `UserFactory` no daba valor a `id_role`, que es `NOT NULL` con Foreign Key.

```
NOT NULL constraint failed: users.id_role
```

Como fallaba la fábrica, **todos** los tests de autenticación de Breeze
(registro, login, verificación de email, reset de contraseña, perfil) morían al
crear su primer usuario. Es decir: el módulo de autenticación estaba
completamente sin probar.

**Solución**

- Migración `2026_10_15_000001` que agrega las dos columnas faltantes.
- `UserFactory` resuelve el rol "Cliente" (lo crea si la tabla está vacía), y
  suma un helper `withRol()` para tests que necesiten permisos.

---

## El error de seguridad

**Problema**

Las rutas de gestión de roles solo tenían `middleware(['auth'])`:

```php
$user->syncRoles($request->roles);   // sin validate(), sin policy
```

Cualquier usuario logueado podía llamar `/usuarios/{cualquiera}/roles` y
**asignarse a sí mismo el rol Administrador**. Tampoco se validaba la entrada.

**Solución**

- Validación: `'id_role' => ['required', 'integer', 'exists:roles,id_role']`
- Autorización: un `authorizeAdmin()` privado que corta con 403 si el rol del
  usuario no es "Administrador", aplicado a `index()`, `editRoles()` y
  `updateRoles()`.
- Se agregó `User::role()` y `User::isAdmin()`.
- `id_role` sigue **fuera** del `$fillable` del modelo, a propósito, para que
  nadie pueda cambiar su rol con un mass assignment.

**Verificado**

```
vendedor -> GET /usuarios          403
vendedor -> GET /usuarios/1/roles   403
admin    -> GET /usuarios/1/roles   200
```

---

## Otros 3 errores encontrados al verificar

Estos no estaban en el análisis inicial; aparecieron al probar las correcciones.

### La tabla `audits` no existía

`App\Models\User` implementa `OwenIt\Auditing\Contracts\Auditable` y
`config/audit.php` tiene `'enabled' => true`. Eso significa que **cada**
create/update/delete de un usuario escribe una fila de auditoría.

Como la tabla no existía, **toda escritura en `users` devolvía HTTP 500**.
El login funcionaba (solo lee), por eso no se notaba.

Se agregó `2026_10_15_000003` con el stub oficial del paquete.

### El registro de usuarios estaba roto

`RegisteredUserController` no seteaba `id_role`. Como el campo está fuera del
`$fillable` (a propósito), `User::create()` lo descartaba **en silencio** y el
INSERT salía sin la columna:

```
SQLSTATE[HY000]: Field 'id_role' doesn't have a default value
```

Un usuario se creaba y al instante reventaba la página con un 500.

Ahora usa `forceFill()`, que setea el campo explícitamente sin abrir el modelo a
mass-assignment. El rol es "Cliente" hardcodeado en el controlador, **no** viene
del formulario: si viniera, cualquiera podría registrarse eligiendo Administrador.

### No se podía editar el propio perfil

`ProfileUpdateRequest` usaba:

```php
Rule::unique(User::class)->ignore($this->user()->id)
```

Como la clave primaria es `id_user` y no `id`, la regla comparaba contra la
columna equivocada y el usuario recibía "El campo correo electrónico ya ha sido
registrado" al guardar su propio email sin cambios.

Ahora pasa el **modelo entero** (`->ignore($this->user())`), que es la forma en
que Laravel resuelve bien la PK cuando no se llama `id`.

---

## Tablas de infraestructura (migración `2026_10_15_000002`)

El `.env.example` pedía guardar sesión, caché y cola en base de datos:

```
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```

pero al repo le faltaban las migraciones de esas tablas. Solo estaba la de
`cache`. Por eso la app fallaba con `Table 'pallets_flow_system.sessions' doesn't
exist`.

La migración nueva crea, si no existen:

- `sessions` �?? para `SESSION_DRIVER=database`
- `jobs`, `job_batches`, `failed_jobs` �?? para `QUEUE_CONNECTION=database`
- `password_reset_tokens` �?? la usa el "olvidé mi contraseña" de Breeze

Todas tienen guarda `if (!Schema::hasTable(...))` para que no choquen si ya
existieran.

> **Nota**: antes de esta migración esas tablas se habían creado a mano con SQL
> sobre MySQL. Eso era frágil: un `migrate:fresh` las borraba y rompía la app.
> Ahora forman parte del esquema versionado.

---

## `getRouteKeyName()` en 11 modelos

Diez modelos ya tienen `$primaryKey = 'id_x'` (porque el profesor�?�?为 las
tablas a mano), pero no definían `getRouteKeyName()`.

Laravel usa ese método para el *route model binding*. Sin él, una ruta del tipo
`/articles/{article}` busca por la columna `id`, que en esas tablas no existe, y
falla.

| Modelo | PK |
|---|---|
| `Article`, `ProductStock` | `id_article` |
| `Cart` | `id_cart` |
| `CartItem` | `id_cart_item` |
| `Customer` | `id_customer` |
| `RawMaterial`, `RawMaterialStock` | `id_raw_material` |
| `Receipt` | `id_receipt` |
| `Sale` | `id_sale` |
| `Supplier` | `id_supplier` |
| `User` | `id_user` |

No rompía nada hoy porque no hay rutas con binding para esos modelos, pero era
una bomba de tiempo.

### `User` además lleva un accessor `id`

```php
public function getIdAttribute(): int
{
    return (int) $this->getKey();
}
```

Laravel da por hecho que el atributo de la clave primaria se llama `id`.
En este proyecto la PK real es `id_user`, así que sin este alias fallaba con
`Missing parameter: [id]` �?? por ejemplo en las URLs firmadas de verificación de
email que genera Breeze:

```php
URL::temporarySignedRoute('verification.verify', ..., ['id' => $user->id])
```

El accessor no se agrega a `$appends`, así que no cambia la serialización del
modelo.

---

## Archivos tocados

### Controladores y requests

| Archivo | Cambio |
|---|---|
| `app/Http/Controllers/UserController.php` | Import correcto de `Role`, autorización, validación, `id_role` en vez de `syncRoles` |
| `app/Http/Controllers/Auth/RegisteredUserController.php` | Asigna `id_role` con `forceFill`, valida que exista el rol |
| `app/Http/Requests/ProfileUpdateRequest.php` | `ignore($this->user())` en vez de `ignore($this->user()->id)` |

### Modelos

| Archivo | Cambio |
|---|---|
| `app/Models/User.php` | `getRouteKeyName()`, accessor `id`, relación `role()`, `isAdmin()` |
| `app/Models/Article.php` | `getRouteKeyName()` |
| `app/Models/Cart.php` | `getRouteKeyName()` |
| `app/Models/CartItem.php` | `getRouteKeyName()` |
| `app/Models/Customer.php` | `getRouteKeyName()` |
| `app/Models/ProductStock.php` | `getRouteKeyName()` |
| `app/Models/RawMaterial.php` | `getRouteKeyName()` |
| `app/Models/RawMaterialStock.php` | `getRouteKeyName()` |
| `app/Models/Receipt.php` | `getRouteKeyName()` |
| `app/Models/Sale.php` | `getRouteKeyName()` |
| `app/Models/Supplier.php` | `getRouteKeyName()` |

### Base de datos

| Archivo | Cambio |
|---|---|
| `database/seeders/RoleSeeder.php` | 6 roles (4 originales + Vendedor + Deposito), idempotente |
| `database/seeders/UserSeeder.php` | Roles por nombre, usuarios por email, idempotente |
| `database/factories/UserFactory.php` | Resuelve `id_role`, helper `withRol()` |

### Migraciones nuevas

| Archivo | Qué crea |
|---|---|
| `2026_10_15_000001_add_email_verified_at_and_remember_token_to_users_table.php` | Las 2 columnas que faltaban en `users` |
| `2026_10_15_000002_create_infrastructure_tables.php` | `sessions`, `jobs`, `job_batches`, `failed_jobs`, `password_reset_tokens` |
| `2026_10_15_000003_create_audits_table.php` | `audits` (stub oficial de owen-it) |

### Vistas y tests

| Archivo | Cambio |
|---|---|
| `resources/views/users/roles.blade.php` | `role_name` / `id_role` en vez de `name` / `id`, `<select>` en vez de checkboxes, mensaje si no hay roles |
| `tests/Feature/Auth/RegistrationTest.php` | Siembra `RoleSeeder` y verifica que el usuario nuevo quede con el rol |

---

## Lo que NO se tocó, y por qué

**`ProductController` sigue completamente vacío** (8 métodos con `//`), igual que
6 métodos de `UserController`. Es andamiaje del profesor, sin rutas ni vistas
asociadas. Borrarlo sería destructivo sin saber la intención.

**Los seeders `SupplierSeeder`, `RawMaterialSeeder`, `ArticleSeeder` y
`SaleSeeder` siguen usando `insert()` plano**, que no es idempotente: si se corre
`php artisan db:seed` dos veces sobre la misma base, falla con `Duplicate entry`.

Se dejó así a propósito porque la forma correcta de reiniciar la base es:

```cmd
php artisan migrate:fresh --seed
```

que dropea todo y vuelve a crearlo, y **sí funciona** (verificado).

La alternativa era cambiar esos cuatro por `insertOrIgnore`, pero eso silencia
también errores de Foreign Key reales, que es peor.

---

## Decisiones de diseño que conviene revisar

**1. El módulo de roles usa el rol propio, no Spatie.**

Spatie está instalado y `User` tiene el trait `HasRoles`, pero la tabla
`spatie_roles` está vacía y el sistema real del proyecto es `users.id_role`
(usado por `RoleSeeder`, `RoleController` y `employees.id_role`).

La corrección alineó el módulo con el sistema que realmente está en uso. La
alternativa sería hacer que Spatie funcione sobre la tabla `roles` existente, lo
que exige que `App\Models\Role` extienda el modelo de Spatie y mapear
`role_name` �?? `name`. Es un cambio de mayor alcance y probablemente deserve una
conversación con el profesor.

**2. Se agregaron dos roles al catálogo.**

`RoleSeeder` definía Jefe/Administrador/Encargado/Cliente, pero `UserSeeder`
necesitaba un rol para el usuario vendedor y otro para el de depósito. Se
agregaron conservando los 4 originales.

Si el profesor prefiere otro catálogo (por ejemplo, cambiar "Jefe" por
"Administrador" y "Encargado" por "Vendedor"), se ajusta en un solo lugar:
`database/seeders/RoleSeeder.php`.

---

## Cómo verificar las correcciones

```cmd
cd /d C:\laragon\www\Pallets-Flow-System

REM 1. Tests (debe dar 25 passed)
php artisan test

REM 2. La base completa desde cero
php artisan migrate:fresh --seed

REM 3. Sintaxis de todo
php -l app\Models\User.php
```

Para ver el módulo de roles funcionando, entrar como administrador en
`http://localhost/Pallets-Flow-System/public/` y abrir *Usuarios* �?? asignar rol.

Con `vendedor@ecommerce.com / vendedor1234` se puede comprobar que
`/usuarios` devuelve **403**.
