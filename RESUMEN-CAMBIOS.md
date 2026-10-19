# Resumen de modificaciones â€” Pallets Flow System

Proyecto: `C:\laragon\www\Pallets-Flow-System`
Fecha: 15 de octubre de 2026

**19 archivos modificados Â· 3 migraciones nuevas Â· 541 lÃ­neas agregadas, 103 eliminadas**

Nada fue commiteado. Los cambios estÃ¡n en el working tree para poder revisarlos
antes de hacer `git commit`.

---

## Resultado

| Indicador | Antes | DespuÃ©s |
|---|---|---|
| Tests | 5 pasan / 20 fallan | **25 pasan / 0 fallan** |
| Rutas que devuelven HTTP 500 | 4+ | **0** |
| Tablas en la base | 21 (3 a mano) | **28 (todas por migraciÃ³n)** |
| `php artisan migrate:fresh` | RompÃ­a la base | **Deja la base completa y funcional** |
| Asignar roles a un usuario | Renderizaba 0 opciones | **6 opciones, funciona** |
| Registro de usuario nuevo | HTTP 500 | **Funciona** |
| Editar el propio perfil | Error "email ya registrado" | **Funciona** |
| Usuario no-admin cambia roles | **Se lo podÃ­a** | **403 Forbidden** |

---

## Los 3 errores crÃ­ticos

### 1. El mÃ³dulo de roles no funcionaba

**Problema**

`UserController` importaba el modelo de Spatie:

```php
use Spatie\Permission\Models\Role;   // lee la tabla `spatie_roles`
```

El proyecto tiene **dos clases `Role` sobre tablas distintas**:

| Clase | Tabla | Contenido |
|---|---|---|
| `Spatie\Permission\Models\Role` | `spatie_roles` | **vacÃ­a** |
| `App\Models\Role` | `roles` | Jefe, Administrador, Encargado, Cliente |

Como `UserController` usaba la primera, `Role::all()` devolvÃ­a cero filas y la
pÃ¡gina de asignaciÃ³n de roles se renderizaba **sin una sola opciÃ³n**. La
funcionalidad directamente no existÃ­a.

`RoleController`, en cambio, sÃ­ usaba `App\Models\Role` â€” por eso el CRUD de
roles sÃ­ andaba y el resto del proyecto no daba seÃ±ales.

**SoluciÃ³n**

- `UserController` usa `App\Models\Role` (tabla `roles`), el sistema real del proyecto.
- `syncRoles()` de Spatie reemplazado por actualizaciÃ³n de `users.id_role`.
- La vista pasÃ³ de checkboxes a un `<select>`, porque `id_role` es una Foreign
  Key simple: **un** rol por usuario, no varios.
- Se documentÃ³ en `User` que conviven dos sistemas de roles distintos y cuÃ¡l es el real.

### 2. Los roles de los usuarios estaban mal asignados

**Problema**

`UserSeeder` asignaba el rol escribiendo el ID a mano:

```php
'id_role' => 1,   // el comentario decÃ­a "1=Admin"
```

Pero los IDs los genera `auto_increment`, asÃ­ que el `1` no era "Admin": era el
primer rol que creÃ³ `RoleSeeder`, que es **Jefe**. Y como el orden de inserciÃ³n
no estÃ¡ garantizado, el resultado real era:

```
admin@ecommerce.com     -> Jefe
vendedor@ecommerce.com  -> Administrador    <-- escalada de privilegios
deposito@ecommerce.com  -> Encargado
cliente@gmail.com       -> Cliente
```

Es decir: **el usuario vendedor tenÃ­a permisos de administrador.**

**SoluciÃ³n**

- `RoleSeeder` conserva los 4 roles originales y agrega `Vendedor` y `Deposito`,
  que el `UserSeeder` daba por supuestos pero nunca existieron.
- `UserSeeder` ahora busca el rol **por nombre** con un helper `roleId()` que
  corta con un mensaje explÃ­cito si el rol no existe, en vez de adivinar un ID.
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

a) La tabla `users` no tenÃ­a `email_verified_at` ni `remember_token`. Las
asumÃ­a tanto `User::casts()` como `UserFactory`.

```
table users has no column named email_verified_at
```

b) `UserFactory` no daba valor a `id_role`, que es `NOT NULL` con Foreign Key.

```
NOT NULL constraint failed: users.id_role
```

Como fallaba la fÃ¡brica, **todos** los tests de autenticaciÃ³n de Breeze
(registro, login, verificaciÃ³n de email, reset de contraseÃ±a, perfil) morÃ­an al
crear su primer usuario. Es decir: el mÃ³dulo de autenticaciÃ³n estaba
completamente sin probar.

**SoluciÃ³n**

- MigraciÃ³n `2026_10_15_000001` que agrega las dos columnas faltantes.
- `UserFactory` resuelve el rol "Cliente" (lo crea si la tabla estÃ¡ vacÃ­a), y
  suma un helper `withRol()` para tests que necesiten permisos.

---

## El error de seguridad

**Problema**

Las rutas de gestiÃ³n de roles solo tenÃ­an `middleware(['auth'])`:

```php
$user->syncRoles($request->roles);   // sin validate(), sin policy
```

Cualquier usuario logueado podÃ­a llamar `/usuarios/{cualquiera}/roles` y
**asignarse a sÃ­ mismo el rol Administrador**. Tampoco se validaba la entrada.

**SoluciÃ³n**

- ValidaciÃ³n: `'id_role' => ['required', 'integer', 'exists:roles,id_role']`
- AutorizaciÃ³n: un `authorizeAdmin()` privado que corta con 403 si el rol del
  usuario no es "Administrador", aplicado a `index()`, `editRoles()` y
  `updateRoles()`.
- Se agregÃ³ `User::role()` y `User::isAdmin()`.
- `id_role` sigue **fuera** del `$fillable` del modelo, a propÃ³sito, para que
  nadie pueda cambiar su rol con un mass assignment.

**Verificado**

```
vendedor -> GET /usuarios          403
vendedor -> GET /usuarios/1/roles   403
admin    -> GET /usuarios/1/roles   200
```

---

## Otros 3 errores encontrados al verificar

Estos no estaban en el anÃ¡lisis inicial; aparecieron al probar las correcciones.

### La tabla `audits` no existÃ­a

`App\Models\User` implementa `OwenIt\Auditing\Contracts\Auditable` y
`config/audit.php` tiene `'enabled' => true`. Eso significa que **cada**
create/update/delete de un usuario escribe una fila de auditorÃ­a.

Como la tabla no existÃ­a, **toda escritura en `users` devolvÃ­a HTTP 500**.
El login funcionaba (solo lee), por eso no se notaba.

Se agregÃ³ `2026_10_15_000003` con el stub oficial del paquete.

### El registro de usuarios estaba roto

`RegisteredUserController` no seteaba `id_role`. Como el campo estÃ¡ fuera del
`$fillable` (a propÃ³sito), `User::create()` lo descartaba **en silencio** y el
INSERT salÃ­a sin la columna:

```
SQLSTATE[HY000]: Field 'id_role' doesn't have a default value
```

Un usuario se creaba y al instante reventaba la pÃ¡gina con un 500.

Ahora usa `forceFill()`, que setea el campo explÃ­citamente sin abrir el modelo a
mass-assignment. El rol es "Cliente" hardcodeado en el controlador, **no** viene
del formulario: si viniera, cualquiera podrÃ­a registrarse eligiendo Administrador.

### No se podÃ­a editar el propio perfil

`ProfileUpdateRequest` usaba:

```php
Rule::unique(User::class)->ignore($this->user()->id)
```

Como la clave primaria es `id_user` y no `id`, la regla comparaba contra la
columna equivocada y el usuario recibÃ­a "El campo correo electrÃ³nico ya ha sido
registrado" al guardar su propio email sin cambios.

Ahora pasa el **modelo entero** (`->ignore($this->user())`), que es la forma en
que Laravel resuelve bien la PK cuando no se llama `id`.

---

## Tablas de infraestructura (migraciÃ³n `2026_10_15_000002`)

El `.env.example` pedÃ­a guardar sesiÃ³n, cachÃ© y cola en base de datos:

```
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```

pero al repo le faltaban las migraciones de esas tablas. Solo estaba la de
`cache`. Por eso la app fallaba con `Table 'pallets_flow_system.sessions' doesn't
exist`.

La migraciÃ³n nueva crea, si no existen:

- `sessions` â€” para `SESSION_DRIVER=database`
- `jobs`, `job_batches`, `failed_jobs` â€” para `QUEUE_CONNECTION=database`
- `password_reset_tokens` â€” la usa el "olvidÃ© mi contraseÃ±a" de Breeze

Todas tienen guarda `if (!Schema::hasTable(...))` para que no choquen si ya
existieran.

> **Nota**: antes de esta migraciÃ³n esas tablas se habÃ­an creado a mano con SQL
> sobre MySQL. Eso era frÃ¡gil: un `migrate:fresh` las borraba y rompÃ­a la app.
> Ahora forman parte del esquema versionado.

---

## `getRouteKeyName()` en 11 modelos

Diez modelos ya tienen `$primaryKey = 'id_x'` (porque el profesorå®šä¹‰ä¸º las
tablas a mano), pero no definÃ­an `getRouteKeyName()`.

Laravel usa ese mÃ©todo para el *route model binding*. Sin Ã©l, una ruta del tipo
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

No rompÃ­a nada hoy porque no hay rutas con binding para esos modelos, pero era
una bomba de tiempo.

### `User` ademÃ¡s lleva un accessor `id`

```php
public function getIdAttribute(): int
{
    return (int) $this->getKey();
}
```

Laravel da por hecho que el atributo de la clave primaria se llama `id`.
En este proyecto la PK real es `id_user`, asÃ­ que sin este alias fallaba con
`Missing parameter: [id]` â€” por ejemplo en las URLs firmadas de verificaciÃ³n de
email que genera Breeze:

```php
URL::temporarySignedRoute('verification.verify', ..., ['id' => $user->id])
```

El accessor no se agrega a `$appends`, asÃ­ que no cambia la serializaciÃ³n del
modelo.

---

## Archivos tocados

### Controladores y requests

| Archivo | Cambio |
|---|---|
| `app/Http/Controllers/UserController.php` | Import correcto de `Role`, autorizaciÃ³n, validaciÃ³n, `id_role` en vez de `syncRoles` |
| `app/Http/Controllers/Auth/RegisteredUserController.php` | Asigna `id_role` con `forceFill`, valida que exista el rol |
| `app/Http/Requests/ProfileUpdateRequest.php` | `ignore($this->user())` en vez de `ignore($this->user()->id)` |

### Modelos

| Archivo | Cambio |
|---|---|
| `app/Models/User.php` | `getRouteKeyName()`, accessor `id`, relaciÃ³n `role()`, `isAdmin()` |
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

| Archivo | QuÃ© crea |
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

## Lo que NO se tocÃ³, y por quÃ©

**`ProductController` sigue completamente vacÃ­o** (8 mÃ©todos con `//`), igual que
6 mÃ©todos de `UserController`. Es andamiaje del profesor, sin rutas ni vistas
asociadas. Borrarlo serÃ­a destructivo sin saber la intenciÃ³n.

**Los seeders `SupplierSeeder`, `RawMaterialSeeder`, `ArticleSeeder` y
`SaleSeeder` siguen usando `insert()` plano**, que no es idempotente: si se corre
`php artisan db:seed` dos veces sobre la misma base, falla con `Duplicate entry`.

Se dejÃ³ asÃ­ a propÃ³sito porque la forma correcta de reiniciar la base es:

```cmd
php artisan migrate:fresh --seed
```

que dropea todo y vuelve a crearlo, y **sÃ­ funciona** (verificado).

La alternativa era cambiar esos cuatro por `insertOrIgnore`, pero eso silencia
tambiÃ©n errores de Foreign Key reales, que es peor.

---

## Decisiones de diseÃ±o que conviene revisar

**1. El mÃ³dulo de roles usa el rol propio, no Spatie.**

Spatie estÃ¡ instalado y `User` tiene el trait `HasRoles`, pero la tabla
`spatie_roles` estÃ¡ vacÃ­a y el sistema real del proyecto es `users.id_role`
(usado por `RoleSeeder`, `RoleController` y `employees.id_role`).

La correcciÃ³n alineÃ³ el mÃ³dulo con el sistema que realmente estÃ¡ en uso. La
alternativa serÃ­a hacer que Spatie funcione sobre la tabla `roles` existente, lo
que exige que `App\Models\Role` extienda el modelo de Spatie y mapear
`role_name` â†’ `name`. Es un cambio de mayor alcance y probablemente deserve una
conversaciÃ³n con el profesor.

**2. Se agregaron dos roles al catÃ¡logo.**

`RoleSeeder` definÃ­a Jefe/Administrador/Encargado/Cliente, pero `UserSeeder`
necesitaba un rol para el usuario vendedor y otro para el de depÃ³sito. Se
agregaron conservando los 4 originales.

Si el profesor prefiere otro catÃ¡logo (por ejemplo, cambiar "Jefe" por
"Administrador" y "Encargado" por "Vendedor"), se ajusta en un solo lugar:
`database/seeders/RoleSeeder.php`.

---

## CÃ³mo verificar las correcciones

```cmd
cd /d C:\laragon\www\Pallets-Flow-System

REM 1. Tests (debe dar 25 passed)
php artisan test

REM 2. La base completa desde cero
php artisan migrate:fresh --seed

REM 3. Sintaxis de todo
php -l app\Models\User.php
```

Para ver el mÃ³dulo de roles funcionando, entrar como administrador en
`http://localhost/Pallets-Flow-System/public/` y abrir *Usuarios* â†’ asignar rol.

Con `vendedor@ecommerce.com / vendedor1234` se puede comprobar que
`/usuarios` devuelve **403**.
