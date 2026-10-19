# Pallets Flow System - Laravel 12

Proyecto clonado y funcionando. Este archivo explica como usarlo.

## Arranque

Doble clic en `C:\laragon\iniciar-entorno.cmd` (levanta MySQL + Apache).

Luego abris el sitio en una de estas dos direcciones:

| Direccion | Cuando usarla |
|---|---|
| `http://localhost/Pallets-Flow-System/public/` | siempre funciona, no hay que hacer nada |
| `http://pallets-flow-system.test` | mas comoda, pero hay que agregar el hosts una vez |

Para tener el dominio corto, doble clic en **`C:\laragon\agregar-hosts-pallets.cmd`**
(y aceptar el aviso de administrador). Es una sola vez.

### Credenciales

| Email | Contrasena | Rol |
|---|---|---|
| `admin@ecommerce.com` | `admin1234` | Admin |
| `vendedor@ecommerce.com` | `vendedor1234` | Vendedor |
| `deposito@ecommerce.com` | `deposito1234` | Deposito |
| `cliente@gmail.com` | `cliente1234` | Cliente |

### Rutas que existen

El proyecto usa rutas en ingles: `/employees`, `/roles`, `/suppliers`,
`/articles`, `/raw-materials`, `/sales`, `/customers`, `/cart`...

Para ver todas:

```cmd
php artisan route:list
```

---

## Donde esta

```
C:\laragon\www\Pallets-Flow-System\    el proyecto
C:\laragon\www\sistema\                 el otro proyecto (tambien funcionando)
C:\laragon\etc\apache2\sites-enabled\auto.pallets-flow-system.test.conf
```

## Comandos

```cmd
cd /d C:\laragon\www\Pallets-Flow-System

php artisan serve            # servidor en :8000
php artisan migrate          # migraciones
php artisan migrate:fresh --seed   # resetear la base con datos
php artisan tinker           # consola REPL
php artisan route:list       # ver rutas

npm run dev                  # Vite en desarrollo
npm run build                # compilar assets

git pull                     # traer cambios del profesor
```

VS Code:

```cmd
code C:\laragon\www\Pallets-Flow-System
```

---

## Base de datos

MySQL 8.4.3 en `127.0.0.1:3306`, base `pallets_flow_system`, usuario `root` sin contrasena.

```cmd
"C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe" -u root pallets_flow_system
```

24 tablas: `users`, `employees`, `customers`, `roles`, `suppliers`,
`raw_materials`, `raw_material_stock`, `articles`, `article_materials`,
`product_stock`, `cart`, `cart_items`, `sales`, `receipts`, mas las de
`spatie/laravel-permission` y las de infraestructura.

**Ojo:** la PK de `users` se llama `id_user`, no `id`. Si haces consultas a mano,
`SELECT id_user, email, name FROM users`.

### Un detalle del repo

El `.env.example` del profesor trae `SESSION_DRIVER=database`,
`CACHE_STORE=database` y `QUEUE_CONNECTION=database`, pero **al repo le faltan
las migraciones de `sessions`, `jobs`, `job_batches`, `failed_jobs` y
`password_reset_tokens`**. Solo esta la de `cache`.

Eso hacia que la pagina tirara HTTP 500 (`Table 'pallets_flow_system.sessions'
doesn't exist`).

**Solucion aplicada:** cree esas 5 tablas directamente en la base, sin agregar
archivos al repo. Si el profesor sube las migraciones despues, se van a
crear de nuevo y no habra conflicto.

Si borras la base y volves a migrar, las tablas se pierden. Para rehacerlas,
el SQL esta en `C:\laragon\tablas-faltantes.sql`.

Tambien cambie el `.env` de `DB_CONNECTION=sqlite` a `mysql` (el
`.env.example` venia con sqlite, que es el default de Laravel 12, pero el curso
usa MySQL). Si tu profesor quiere SQLite, en `.env` poné:

```
DB_CONNECTION=sqlite
DB_DATABASE=C:/laragon/www/Pallets-Flow-System/database/database.sqlite
```

y creá el archivo vacio con `type nul > database\database.sqlite`.

---

## Dos cosas de Laragon que conviene saber

1. **El `php.ini` lo reescribe Laragon.** Cuando abris la GUI, Laragon regenera
   el `php.ini` y puede desactivar `zip` y `opcache`. Si `composer install`
   vuelve a fallar con *"The zip extension ... are both missing"*, hay que
   reactivarlos en
   `C:\laragon\bin\php\php-8.4.26-Win32-vs17-x64\php.ini`:
   ```
   extension=zip
   zend_extension=opcache
   ```
   (estan comentados con `;` al principio). Respaldo en `php.ini.bak-laragon`.

2. **Usar PHP 8.4, no 8.3.** El `composer.lock` fija paquetes que exigen PHP
   8.4 (`dragonmantank/cron-expression`, `nette/schema`, `nette/utils`). Con
   8.3 falla. Si en la GUI de Laragon dice 8.3, cambialo a 8.4.26.

Ademas, `httpd -k start` **no funciona** en Windows (intenta arrancar el
servicio "Apache2.4"). Por eso los scripts usan `httpd -f <conf>` a secas.
