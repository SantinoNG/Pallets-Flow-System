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
| Sistema visual | **Paleta industrial + modo oscuro.** Acento unico `hazard` (naranja de seguridad) sobre superficies `zinc` (grafito). `darkMode: 'class'` con interruptor manual. Ver seccion 6.1. |
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
| `RawMaterial` | `raw_materials` | `id_raw_material` | `belongsTo Supplier`, `hasOne RawMaterialStock`, `belongsToMany Article` (pivote `article_materials`, `quantity_required`). Tiene `unit` (metro, bolsa, caja, litro, pliego, rollo, plancha, unidad, kg) y `unit_cost` (costo por unidad). `UNIDADES` guarda el par singular/plural y `cantidad($n)` arma "3 metros" / "1 bolsa" / "1.50 litros". |
| `RawMaterialStock` | `raw_material_stock` | `id_raw_material` | `belongsTo RawMaterial` |
| `Article` | `articles` | `id_article` | `hasOne ProductStock`, `belongsToMany RawMaterial` (pivote `article_materials`), `hasMany CartItem` |
| `ProductStock` | `product_stock` | `id_article` | `belongsTo Article` |
| `Cart` | `cart` | `id_cart` | `belongsTo Customer`, `hasMany CartItem` (`items`), `hasOne Sale`. Declara `protected $table = 'cart'`. |
| `CartItem` | `cart_items` | `id_cart_item` | `belongsTo Cart`, `belongsTo Article`. Con `getRouteKeyName()`. |
| `Sale` | `sales` | `id_sale` | `belongsTo Cart`, `belongsTo Customer`, `belongsTo Employee`, `hasOne Receipt`, `hasMany SalePayment` (`payments()`). `payment_method` guarda un **resumen**: `Efectivo` o `Pago combinado (Efectivo + Transferencia)`. `esPagoCombinado()` y `numeroComprobante()`. |
| `SalePayment` | `sale_payments` | `id_payment` | `belongsTo Sale`. Una fila por medio de pago usado, con `payment_method`, `amount` e `installments` (cuotas; 1 en los medios que no son tarjeta). La suma de los importes es el total de la venta. `etiqueta()` arma "Tarjeta de crédito (6 cuotas de $X)" y `enCuotas()` dice si va en cuotas. |
| `Receipt` | `receipts` | `id_receipt` | `belongsTo Sale`. Guarda el numero fiscal (`receipt_number` correlativo por cliente + `point_of_sale`) y el desglose de IVA (`tax_rate`, `net_amount`, `tax_amount`, `total_amount`). `numeroComprobante()` arma `0001-00000003`; `desglose()` devuelve neto/IVA/total guardados. |

Diagrama lógico: `User 1─1 Employee | Customer` · `Customer 1─N Cart 1─N CartItem N─1 Article` · `Cart 1─1 Sale 1─1 Receipt` · `Sale N─1 Employee` · `Article N─M RawMaterial` · `Article 1─1 ProductStock` · `Supplier 1─N RawMaterial 1─1 RawMaterialStock`.

## 5. Migraciones (`database/migrations`)

Orden actual: `roles` → `users` (con `id_role` NOT NULL) → `employees` → `customers` → `suppliers` → `raw_materials` → `raw_material_stock` → `articles` → `product_stock` → `article_materials` → **`cart`** (`id_customer`, `status` enum `active|completed|abandoned` default `active`) → **`cart_items`** (`id_cart`, `id_article`, `quantity`, `unit_price`) → `sales` (`id_cart`, `id_customer`, `id_employee` nullable, `payment_method`) → `receipts` (`id_sale`, `receipt_number`, `point_of_sale`, `issue_date`, `due_date`, `tax_rate`, `net_amount`, `tax_amount`, `total_amount`) → `sale_payments` (`id_sale`, `payment_method`, `amount`, `installments`) → `2026_06_21_225300_create_permission_tables` (Spatie) → `2026_06_21_230236_create_cache_table`.

Notas:
- Las migraciones de dominio están fechadas `2024_01_01_*` (convención manual de orden), las de paquetes `2026_06_21_*`.
- `config/permission.php` mapea la tabla de roles de Spatie a `spatie_roles` para no chocar con la tabla propia `roles`. Además la clave `'permissions'` aparece duplicada dentro de `table_names` (la segunda sobreescribe a la primera → tabla `permissions`).
- `cart_items` no tiene restricción única `(id_cart, id_article)`; `cart` no tiene índice `(id_customer, status)`.
- Seeders: `RoleSeeder`, `UserSeeder`, `CustomerSeeder`, `SupplierSeeder` (**10 proveedores**), `RawMaterialSeeder` (**16 materias primas** con unidad y costo), `ArticleSeeder` (**8 articulos con su receta** en `article_materials`), `ImagenesArticuloSeeder`, `SaleSeeder`. Usuarios semilla: admin, vendedor, depósito, cliente. `SupplierSeeder`, `RawMaterialSeeder` y `ArticleSeeder` son **idempotentes** (`updateOrInsert` / busqueda por nombre): se pueden correr de nuevo sin duplicar y ademas limpian los insumos que salieron del catalogo. `ArticleSeeder` resetea el stock de demo de los articulos. `SaleSeeder` sigue con `insert()`: las ventas historicas no se re-siembran.
- `2026_10_15_000001_add_email_verified_at_and_remember_token_to_users_table.php`, `2026_10_15_000002_create_infrastructure_tables.php` (sessions, jobs, job_batches, failed_jobs, password_reset_tokens), `2026_10_15_000003_create_audits_table.php` (stub de owen-it).
- `2026_10_20_000001_add_image_to_articles_table.php`: agrega `articles.image` (string nullable) para la foto del producto. Guarda la **ruta relativa** dentro del disco `public` (por ejemplo `articles/sillon.jpg`), no la URL completa: si el dominio cambia, la ruta sigue sirviendo.
- `2026_10_20_000002_rename_deposito_role.php`: renombra el rol "Depósito" a "Deposito" (ver deuda técnica 34).
- `2026_10_22_000001_create_sale_payments_table.php`: detalle del cobro. Una fila por medio usado (`id_sale`, `payment_method`, `amount`). Permite el **pago combinado**. `sales.payment_method` queda como resumen para listados y filtros.
- `2026_10_22_000002_add_fiscal_fields_to_receipts_table.php`: agrega al comprobante el **numero correlativo por cliente** (`receipt_number`), el punto de venta (`point_of_sale`, default `0001`) y el **desglose de IVA** (`tax_rate`, `net_amount`, `tax_amount`, `total_amount`). La migracion tambien **numera y desglosa los comprobantes existentes** y crea la fila de pago que falte en las ventas viejas, usando el query builder (no los modelos: una migracion no puede depender de codigo de la aplicacion).
- `2026_10_22_000003_add_installments_to_sale_payments_table.php`: agrega `sale_payments.installments` (entero, default 1) para las **cuotas de la tarjeta de credito**. El importe sigue siendo el total cobrado; las cuotas son la forma de pago.
- `2026_10_22_000004_add_unit_and_cost_to_raw_materials_table.php`: agrega `raw_materials.unit` (string 20, default `unidad`) y `raw_materials.unit_cost` (decimal 12,2, default 0). Con eso una materia prima deja de ser solo un texto: dice **en que se mide** y **cuanto cuesta la unidad**, y se puede calcular el costo de materiales de cada producto.

### Seeders de clientes y movimiento

| Seeder | Que hace |
|---|---|
| `CustomerSeeder` | Crea **10 clientes mas** (Bruno Ferrari, Carla Méndez, Diego Sosa, Elena Duarte, Facundo Ríos, Gabriela Paz, Hernán Cabrera, Irina López, Julián Vargas, Karen Aquino), cada uno con su usuario (rol Cliente) y su ficha en `customers`. Es idempotente (`updateOrInsert`). |
| `SaleSeeder` | Reparte **36 ventas** entre los 11 clientes: 6 de Ana Torres (las de antes) y 3 de cada cliente nuevo, separadas 37 dias y dentro de los ultimos cuatro meses. Los precios salen del catalogo actual y los medios de pago se alternan. Cada venta escribe su **detalle en `sale_payments`**, numera el comprobante **por cliente** (1, 2, 3…) y guarda el **desglose de IVA**. La primera venta de Ana queda **combinada** (efectivo + tarjeta en 6 cuotas) para mostrar la funcion. |
| `ImagenesArticuloSeeder` | Genera una silueta SVG para cada artículo sin foto, para que el catálogo no quede vacío tras `migrate:fresh --seed`. No toca los artículos que ya tienen imagen. |

Resultado: el filtro "Últimos 90 días" del dashboard muestra **4 meses con ventas** (jul a oct) y el de "Últimos 6 meses", 7 meses. Antes había un solo cliente con 6 ventas.

## 6. Módulos actuales

| Módulo | Estado | Archivos clave |
|---|---|---|
| Autenticación / Perfil (Breeze) | Funcional | `routes/auth.php`, `Auth/*`, `ProfileController` |
| Usuarios y asignación de roles | Funcional. **Dos capas sincronizadas**: `users.id_role` (rol de negocio, FK simple) y `model_has_roles` (Spatie, capa de autorización). Se asignan siempre juntas con `User::asignarRol()`. `spatie_roles` es el espejo de `roles`. | `UserController`, `RoleController`, vistas `users/*`, `roles/*` |
| Roles | CRUD completo. Al crear/editar/borrar un rol se mantiene el espejo en `spatie_roles` | `RoleController`, vistas `roles/*` |
| Empleados | CRUD | `EmployeeController`, vistas `employees/*` |
| Artículos (+ stock + materias primas) | CRUD. **Con foto**: se sube desde el formulario (JPG/PNG/WebP, máx 2 MB) y se guarda en `storage/app/public/articles/`. La columna `articles.image` es nullable; cuando está vacía, la vista dibuja un placeholder industrial. | `ArticleController`, vistas `articles/*`, `Article::imagen_url` |
| Proveedores (+ materias primas + stock) | CRUD | `SupplierController`, vistas `suppliers/*` |
| Tutorial / Guía técnica | Vista estática | `resources/views/tutorial/index.blade.php` |
| **Carrito de compras** | **Funcional** (controlador, rutas `cart.*` y vistas). El cobro acepta **varios medios de pago** (`payments[]`) y valida que sumen el total. | `CartController`, `App\Support\MediosPago`, vistas `cart/*` |
| **Ventas / Comprobantes** | **Funcional.** Listado paginado con filtros (medio de pago, empleado, rango de fechas), detalle y comprobante en PDF (dompdf). `Sale::total()` calcula el monto desde los items del carrito, porque la tabla `sales` no tiene columna de importe. El comprobante muestra **numero fiscal por cliente**, **neto + IVA 21% + total** y el **detalle de pagos**. | `SaleController`, vistas `sales/*`, `Sale`, `Receipt`, `App\Support\Iva` |
| Dashboard | **Funcional con datos reales.** Componente Livewire con 5 consultas agregadas a la base y selector de periodo (30 días / 90 días / 6 meses / año / todo). Charts de Chart.js se re-inicializan en cada morph de Livewire. | `app/Livewire/Dashboard.php`, `views/livewire/dashboard.blade.php` |

### 6.1 Sistema visual (interfaz)

Se unifico la paleta y se agrego modo oscuro. La interfaz quedo con una
identidad **industrial**: grafito metalico, un solo color de acento y tipografia
tecnica.

| Elemento | Decision | Donde se define |
|---|---|---|
| Acento de marca | `hazard` (naranja de seguridad `#F97316`). Reemplaza a `indigo`, `blue` y `amber`, que convivian. | `tailwind.config.js` -> `theme.extend.colors.hazard` |
| Superficies | `zinc` (gris neutro, sin el tinte azul de `slate`). | `tailwind.config.js`, clases `bg-zinc-*` |
| Modo oscuro | `darkMode: 'class'`: la clase `dark` en `<html>` lo decide el interruptor, no el sistema operativo. | `tailwind.config.js` |
| Preferencia | `localStorage` con la clave `tema` (`dark` / `light`). Si no hay, se respeta `prefers-color-scheme`. | `resources/views/layouts/*.blade.php` (script del `<head>`) |
| Sin destello | El script del `<head>` aplica la clase **antes** de pintar. | `layouts/app.blade.php`, `layouts/guest.blade.php` |
| Interruptor | Componente reutilizable, boton sol/luna. | `resources/views/components/theme-toggle.blade.php` |
| Tipografia | `Barlow` (grotesca tecnica) para la interfaz, `IBM Plex Mono` para los datos. Antes estaba `Figtree`, que **nunca se cargaba** (no habia `@font-face` ni link a Google Fonts): el navegador usaba la del sistema. | `tailwind.config.js` -> `theme.extend.fontFamily` |
| Numeros en tablas | `font-mono tabular-nums` en las celdas de dato (ID, precios, cantidades, stock) para que las columnas de decimales alineen. | vistas de `articles`, `sales`, `employees`, `roles`, `suppliers`, `users`, `cart` |
| Esquinas | Radios achicados (`rounded-lg` = 0.25rem): la industria es precisa, no redondeada. | `tailwind.config.js` -> `theme.extend.borderRadius` |
| Boton principal | `x-primary-button` paso de `bg-gray-800` a `bg-hazard-600`: la accion primaria es del color de marca. | `resources/views/components/primary-button.blade.php` |
| Paginacion | Se publicaron las vistas de Laravel (`resources/views/vendor/pagination/`) y se reestilaron: usaban el azul de Laravel. | `resources/views/vendor/pagination/` |
| Boton de PDF | **Siempre en rojo** (`bg-red-600`), en todos los modulos. Es la unica accion de la interfaz que conserva el rojo de Tailwind, y es a proposito: el rojo queda reservado para lo que genera un documento o saca datos del sistema, y lo separa del naranja de marca. | `articles/index`, `employees/index`, `suppliers/index`, `users/index`, `sales/index`, `sales/show` |
| Rotulos principales | Los rotulos en mayusculas de las tarjetas del dashboard van en **blanco puro** en modo oscuro (`dark:text-zinc-100`): son el texto que define cada tarjeta. El texto de apoyo (subtitulos, contadores) se queda en gris claro (`dark:text-zinc-300`) para no competir con los datos. | `views/livewire/dashboard.blade.php` |
| Logo | Archivo `public/images/logo-marca.svg`: el monograma "PF" con el pallet y la hoja, sobre pergamino. Se muestra con el componente `<x-app-logo>` en el sidebar y en el login. Hay dos PNG derivados porque el PDF no renderiza SVG: `logo-marca-256.png` (cabecera de los comprobantes) y `marca.png` (la marca sola, con fondo transparente, para la marca de agua). | `public/images/logo-marca.svg`, `components/app-logo.blade.php` |
| Marca de agua | Los comprobantes (HTML y PDF) llevan la marca de la empresa **difuminada** por detras del contenido: `opacity` 0.06 en el HTML y `/CA 0.07` en el PDF, con dompdf. En el PDF se dibuja a 315x315 pt, centrada en la A4. | `views/cart/receipt.blade.php`, `views/sales/pdf.blade.php` |
| Foto del articulo | Componente `<x-article-image>`: muestra la foto si hay, y si no dibuja un placeholder (trama diagonal de plano tecnico + glifo de pallet + inicial del producto). El placeholder es un SVG inline, sin archivos de por medio. | `components/article-image.blade.php` |
| Ilustraciones de la marca | `App\Support\IlustracionArticulo` dibuja en SVG el lenguaje de la marca PALLETS FLOW (pergamino crema con trama diagonal, vineta circular, madera calida y plantas) para los 8 articulos, y el logo (circulo con el nombre arcedado, el pallet de dibujo lineal con una planta brotando y el eslogan). Son SVG: se ven nitidos a cualquier tamaño y pesan 1-4 KB. Cuando lleguen las fotos reales, se suben desde *Articulos > Editar* y reemplazan a estas. | `app/Support/IlustracionArticulo.php`, `public/images/logo.svg` |
| Icono de la aplicacion | `public/images/icono-pallet.svg`: un glifo de pallet de madera (tablas, tacos y una hoja) en los tonos de la marca. Es el que va en el sidebar y en el login, donde el espacio es de 32 o 44 px. El logo completo (`logo-marca.svg`) NO se lee a ese tamano: el texto "PALLETS FLOW" se vuelve una mancha. El favicon se genera con `scripts/generar-favicon.php` (dibuja el pallet con GD y arma el `.ico` con el PNG adentro). | `public/images/icono-pallet.svg`, `public/favicon.ico`, `scripts/generar-favicon.php` |
| Subida de foto | Componente `<x-image-upload>`: input de archivo con **vista previa en vivo** (Alpine + FileReader) y la foto actual si ya habia una. | `components/image-upload.blade.php` |

**Scripts de mantenimiento** en `scripts/` (ver `scripts/README.md`):
`agregar-modo-oscuro.php` (agrega las variantes `dark:` que falten),
`auditar-modo-oscuro.php` (detecta huecos) y `auditar-paleta.php` (detecta
paletas viejas). Para el tema de datos: `buscar-fugas.php` (entra con cada rol
y busca el email de cualquier otro usuario en todas las paginas que ese rol
puede ver) y `http-cliente.php` (el cliente HTTP con las cookies en memoria que
comparten los scripts de testeo).

**Pruebas:** 182 tests, 1525 aserciones, todas en verde.
`tests/Feature/InterfazTest.php` (5 casos, 354 aserciones) fija
el comportamiento: el script del tema va antes del `<body>`, el interruptor
esta en el login y en el panel, ninguna pagina deja superficies sin variante
oscura, la paleta vieja no sobrevive y el acento sigue siendo `hazard`.
`tests/Feature/FugasTest.php` (9 casos) y `tests/Feature/UsuariosTest.php`
(17 casos) fijan la separacion entre clientes y personal.
`tests/Feature/PagoCombinadoTest.php` (18 casos) fija el cobro combinado, el
IVA y la numeracion del comprobante.
`tests/Feature/CheckoutEdicionTest.php` (12 casos) fija la edicion del pedido
desde el checkout: cambiar producto, cambiar cantidad sin re-preciar, fusionar
renglones repetidos, tope de stock, quitar (y volver al carrito si era el
ultimo) y que no se pueda tocar el item de otro carrito.
`tests/Feature/RecetasTest.php` (8 casos) fija los 10 proveedores, las 16
materias primas con unidad y costo, la receta de los 8 articulos, el
singular/plural ("1 bolsa" / "3 bolsas"), el costo de materiales, el alcance
limitado por stock y que el Cliente reciba 403 en la receta.

### 6.2 Cobro, IVA y comprobante

| Elemento | Decision | Donde se define |
|---|---|---|
| Medios de pago | Catalogo unico: `Efectivo`, `Tarjeta de débito`, `Tarjeta de crédito`, `Transferencia`, `Mercado Pago`. El **nombre es el valor guardado**: no hay ids ni slugs intermedios. | `App\Support\MediosPago` |
| Pago combinado | El checkout manda `payments[]` (una lista de `method` + `amount`). Una venta con un solo medio es una lista de un elemento: **no hay dos caminos de cobro**. La suma tiene que dar el total (tolerancia `0.009`). | `CartController@confirmSale`, `views/cart/checkout.blade.php` |
| Resumen en la venta | `sales.payment_method` guarda `Efectivo` o `Pago combinado (Efectivo + Transferencia)`. Sirve para el listado y los filtros; el detalle real vive en `sale_payments`. | `MediosPago::resumen()` |
| Filtro por medio | Mira **el detalle** (`sale_payments`) con un `or` sobre el resumen, asi una venta combinada aparece al filtrar por cualquiera de sus medios. | `SaleController@index` |
| Grafico de medios | Suma `sale_payments.amount` agrupado por medio, no el resumen: cada peso cae en su medio. | `app/Livewire/Dashboard.php` |
| Cuotas | Solo **Tarjeta de crédito**: 1, 3, 6, 9, 12, 18 o 24. El importe del pago es el **total** cobrado; las cuotas son la forma de pago y no llevan interes. En los demas medios el servidor las normaliza a 1. | `MediosPago::CUOTAS`, `MediosPago::cuotas()`, `sale_payments.installments` |
| Recuadro del cobro | El checkout muestra los medios dentro de un **recuadro**: boton "+ Agregar otro medio", un renglon por medio (con cuotas si es credito) y el resumen **Total / Pagado / Falta**. Al agregar un medio, la fila nueva se completa con **lo que falta**. | `views/cart/checkout.blade.php`, `views/cart/partials/fila-pago.blade.php` |
| Etiqueta del pago | `SalePayment::etiqueta()` arma "Tarjeta de crédito (6 cuotas de $20,000.00)". La usan el comprobante HTML, el PDF y el detalle de la venta. | `SalePayment` |
| IVA | Los precios del catalogo **ya incluyen IVA**. El comprobante desglosa: `neto = round(total / 1,21)` e `iva = total - neto`, asi la suma da exacto. `TASA_GENERAL = 21.0`; `TASA_REDUCIDA = 10.5` esta declarada pero sin uso. | `App\Support\Iva` |
| Desglose guardado | `receipts` guarda `tax_rate`, `net_amount`, `tax_amount`, `total_amount` al emitir. **No se recalcula al mostrar**: un comprobante emitido no cambia si cambia un precio. | `Receipt::desglose()` |
| Numero de comprobante | Correlativo **por cliente**, no por venta: su primera compra es la 1. Se guarda en `receipts.receipt_number` y se muestra como `0001-00000003` (punto de venta + 8 digitos). | `Receipt::numeroComprobante()` |
| Donde se muestra | Listado de ventas, detalle, panel del cliente, comprobante HTML y PDF, y nombre del archivo PDF. **El id de la venta no se muestra**: es interno y las rutas lo siguen usando. | `sales/*`, `dashboard/cliente`, `cart/receipt`, `sales/pdf` |
| Alcance fiscal | **No hay CAE ni integracion con AFIP.** El sistema arma numeracion y desglose; la autorizacion fiscal electronica es de otro sistema. | — |

### 6.3 Editar el pedido desde el checkout

| Elemento | Decision | Donde se define |
|---|---|---|
| Renglon editable | El checkout permite **cambiar de producto, cambiar la cantidad y quitar el renglon** sin volver al carrito. Cada accion recarga el checkout con el total nuevo (y el IVA y la primera fila de pago). | `views/cart/checkout.blade.php` |
| Cambiar producto | `PATCH /cart/{cart}/items/{item}`. Cambia el articulo **y toma el precio del catalogo del producto nuevo**. | `CartController@updateItem` |
| Cambiar cantidad | El mismo endpoint. **No re-precio**: se respeta el `unit_price` con el que el articulo entro al carrito, aunque el catalogo haya subido despues. | `CartController@updateItem` |
| Sumar / restar de a uno | Los botones **− / +** mandan `updateItem` con la cantidad resultante: en el carrito, cada boton lleva su propio `quantity` en el submit (sin JS); en el checkout, un `onclick` ajusta el campo y manda el formulario. `−` se deshabilita en 1 y `+` al llegar al stock. | `views/cart/show.blade.php`, `views/cart/checkout.blade.php` |
| Producto repetido | Si el articulo elegido ya esta en **otro renglon** del carrito, los dos se **fusionan** (suma de cantidades) y el renglon original desaparece: no queda el mismo producto dos veces. | `CartController@updateItem` |
| Quitar | `DELETE` de siempre. Si era el ultimo articulo, vuelve al carrito y lo marca `abandoned`: un checkout vacio no tiene nada que confirmar. | `CartController@removeItem` |
| Stock | Se controla contra la **cantidad final** (incluida la suma al fusionar). En el desplegable, los productos sin stock salen deshabilitados. | `updateItem`, vista |
| Seguridad | El `CartItem` tiene que pertenecer al `Cart` de la URL: si no, 404 (no se edita el renglon de otro cambiando el id). | `updateItem`, `removeItem` |
| Cuando se guarda | La cantidad se guarda en `change` (al salir del campo), no solo con el boton: asi no se puede escribir una cantidad nueva y confirmar la compra con la vieja. | vista |

### 6.4 Proveedores, materias primas y recetas

| Elemento | Decision | Donde se define |
|---|---|---|
| Proveedores | **10** en total: los 2 originales (Distribuidora Norte, Materiales del Sur) y 8 nuevos (maderas, aserradero, tornillos, clavos, pinturas, telas, espumas, herrajes). | `SupplierSeeder` |
| Unidad de medida | Cada insumo dice en que se mide: `unidad`, `metro`, `bolsa`, `caja`, `litro`, `pliego`, `rollo`, `plancha`, `kg`. Se guarda en singular y la vista pluraliza. | `raw_materials.unit`, `RawMaterial::UNIDADES` |
| Costo del insumo | `unit_cost` es el costo de **una** unidad del insumo. El costo de materiales de un producto es la suma de `cantidad_required * unit_cost`. | `raw_materials.unit_cost`, `ArticleController@show` |
| Receta | `article_materials` (articulo + materia prima + `quantity_required`) dice que lleva cada producto. Las 8 recetas tienen entre 5 y 8 insumos. | `ArticleSeeder` |
| Pantalla de receta | `GET /articles/{article}` (solo personal): variedad, costo de materiales, margen bruto, alcance con stock y la tabla de insumos con cantidad, proveedor, stock actual, costo unitario y subtotal. | `ArticleController@show`, `views/articles/show.blade.php` |
| Alcance con stock | Para cada insumo se calcula `floor(stock / cantidad_requerida)` y se toma el minimo. El insumo que da ese minimo se muestra como **limitante**. | `ArticleController@show` |
| Listado | Debajo del nombre del articulo aparece "N insumo(s) en la receta" y el boton **Receta**. El Cliente no ve ninguna de las dos cosas. | `views/articles/index.blade.php` |
| Proveedores en pantalla | Cada fila muestra cuantas materias primas tiene y cada chip muestra la unidad (`Tabla de pallet reciclada · unidades`). | `views/suppliers/index.blade.php` |
| Alta de proveedor | Por cada materia prima se pide descripcion + unidad (desplegable). El servidor valida contra `RawMaterial::UNIDADES` y usa `unidad` si no se elige. | `views/suppliers/create.blade.php`, `SupplierController@store` |
| Idempotencia | `SupplierSeeder`, `RawMaterialSeeder` y `ArticleSeeder` actualizan por nombre. `RawMaterialSeeder` borra los insumos viejos con su stock y su renglon de receta; `ArticleSeeder` rehace la receta de cada articulo. | seeders |

## 7. Deuda técnica y hallazgos a tener en cuenta

1. **RESUELTO** — `Cart` ya declara `protected $table = 'cart'`.
2. **RESUELTO** — Se eliminaron `ProductController`, `ProductPolicy`, `ProductFactory`, `StoreProductRequest` y `UpdateProductRequest`: todos referenciaban `App\Models\Product`, que no existe (el módulo se renombró a `Article`) y ningún route los usaba.
3. **RESUELTO** — El catálogo de roles quedó alineado: `RoleSeeder` crea `Jefe`, `Administrador`, `Encargado`, `Cliente`, `Vendedor`, `Deposito` (los dos últimos faltaban y se agregaron). `spatie_roles` ahora se puebla como espejo del catálogo, así que `@role(...)` y el middleware `role:` funcionan. El `Supervisor` inexistente se reemplazó por `Jefe|Administrador`.
4. **RESUELTO** — `RegisteredUserController` asigna el rol con `User::asignarRol()`, que escribe `users.id_role` y el espejo en Spatie. `id_role` sigue fuera del `$fillable` a propósito.
5. **RESUELTO** — `User` ahora tiene `hasOne Customer` (`customer()`).
6. **RESUELTO** — Livewire se usa en el dashboard (`app/Livewire/Dashboard.php` + `views/livewire/dashboard.blade.php`).
7. **PARCIAL** — Tests: los de Breeze más `CartTest`, `SalesTest` y `NoNPlusOneTest` (dominio y regresión). Faltan tests de artículos, proveedores, empleados y roles.
8. **RESUELTO** — El dashboard ya lee los datos de la base. `SaleSeeder` genera 6 ventas repartidas en los últimos 6 meses con fechas, distintos medios de pago y empleados, para que los agregaciones tengan con qué trabajar. `totalVentas` sale de `SUM(quantity * unit_price)` sobre `cart_items` unido a `sales`.
9. **PENDIENTE** — `layouts/navigation.blade.php` (navbar viejo de Breeze) no lo incluye ninguna vista: es código muerto. `layouts/app.blade.php` es el sidebar real.
10. **RESUELTO** — Módulo de Ventas implementado: `SaleController` (index/show/pdf), vistas `sales/*`, rutas `sales.index`, `sales.show` y `sales.pdf`, y `SalesTest` con 9 casos.
11. **PENDIENTE** — `PLANtemplate.md` se referencia en la sección 8 pero **no existe** en `ai_workspace/`.
12. **RESUELTO** — `/sales` filtra por cliente: el Cliente solo ve sus propias ventas (`scopeVisibles()` en `SaleController`), y `show`, `pdf` y `cart.receipt` devuelven 404 si la venta es de otro cliente. Antes el Cliente veía las ventas de todos.
13. **PENDIENTE** — `SaleSeeder` resuelve cliente, empleados y artículos **por nombre** en vez de por id, porque los ids son `auto_increment` y no se puede asumir que coincidan con los de los otros seeders. Vale la pena extender ese criterio a los seeders que quedan.
14. **RESUELTO** — La ruta `GET /usuarios/pdf` estaba registrada pero `UserController` no tenía el método `exportPdf`: devolvía HTTP 500. Se implementó, con la vista `users/pdf.blade.php` siguiendo el estilo de los otros módulos.
15. **RESUELTO** — N+1 en `/sales`: la vista llama `$venta->total()` y `$venta->unidades()`, que usan la relación `items()` (`hasManyThrough`), que no estaba en el eager load. Eloquent la resolvía con una consulta por fila: **25 consultas, 12 idénticas**. Con `items` en el eager load bajó a **13**. `NoNPlusOneTest` falla si vuelve.
16. **RESUELTO** — Control por rol en las rutas que solo tenían `auth`: `/usuarios` y `/roles` ahora piden `role:Jefe|Administrador` y `/employees` pide `role:Jefe|Administrador|Encargado`. Antes **cualquier usuario logueado podía crear, editar y borrar roles**, y el Cliente entraba al listado de empleados.
17. **RESUELTO** — Carrera en el descuento de stock: en `CartController::confirmSale` la validación estaba **fuera** de la transacción y el descuento era un read-modify-write sin lock. Dos checkouts simultáneos podían pasar los dos la validación y sobre-vender. Ahora la validación va dentro de la transacción y las filas se bloquean con `lockForUpdate()`.
18. **RESUELTO** — La interfaz tenia **tres acentos conviviendo**: `amber` (sidebar y login), `indigo` (modulos de articulos, roles y perfil, y los componentes de Breeze) y `blue` (proveedores, empleados y gestion de roles). El login mostraba el logo naranja junto a un boton indigo. Se unifico todo a `hazard` (naranja de seguridad) sobre superficies `zinc`. En el CSS compilado quedaron **0 ocurrencias** de `indigo`, `blue`, `amber` y `slate`.
19. **RESUELTO** — El build usa **Tailwind v3.4.19**, no v4: `postcss.config.js` carga el plugin `tailwindcss` (v3). El paquete `@tailwindcss/vite` v4 esta instalado pero **no se usa** (`vite.config.js` solo registra el plugin de Laravel). Se dejo como esta, sin tocar las dependencias del profesor.
20. **RESUELTO** — `tailwind.config.js` escaneaba `vendor/laravel/framework/.../Pagination/` para generar el CSS, y esas vistas usan el **azul de Laravel**. Se publico la paginacion al proyecto (`resources/views/vendor/pagination/`) y se reestilo, y se quito esa ruta del `content` de Tailwind.
21. **RESUELTO** — `Figtree` figuraba en `fontFamily.sans` pero **no se cargaba nunca**: no habia `@font-face` ni link a Google Fonts, asi que el navegador caia en la fuente del sistema. Se cambio por `Barlow` + `IBM Plex Mono`, ahora sí cargadas desde los dos layouts.
22. **RESUELTO** — Modo oscuro: se agrego el interruptor, la preferencia persistida en `localStorage` y las variantes `dark:` en las vistas. La auditoria de las 15 paginas (13 autenticadas + login y registro) da **0 clases claras sin equivalente oscuro**. Las vistas `*/pdf.blade.php` se excluyen a proposito: se imprimen en blanco.
23. **PENDIENTE** — `tailwind.config.js` todavia no declara la paleta nueva en `safelist`, y `theme('colors.zinc.*')` en `app.css` se evalua en tiempo de build: si se cambia la escala de `zinc`, hay que recompilar. Es el comportamiento normal de Tailwind v3, no un bug.
24. **RESUELTO** — Los modulos con acciones en vivo (Livewire) reconicializan los graficos de Chart.js en cada morph, pero no hay prueba automatica que lo cubra: si Livewire cambia el comportamiento del morph, el grafico se queda congelado sin avisar. **Ademas el grafico no respondia al filtro de periodo**: el `wire:ignore` estaba en el div que envuelve al `<canvas>`, asi que Livewire nunca actualizaba los atributos `data-*` y el grafico se quedaba con los datos del primer render (el KPI si cambiaba, el grafico no). Ahora el `wire:ignore` va solo sobre el `<canvas>` y los datos viven en un `<span id="datos-XXX">` aparte, que es lo que Livewire si actualiza. Cubierto por `tests/Feature/DashboardTest.php`.
25. **RESUELTO** — Los graficos del dashboard tenian los colores **hardcodeados** (`#f3f4f6` para la grilla, `#d97706` y `#3b82f6` para las series). En modo oscuro la grilla clara quedaba sobre el fondo grafito y el azul no existia en la paleta nueva. Ahora el JS elige la paleta segun el tema activo (`paleta()` en `views/livewire/dashboard.blade.php`) y el interruptor emite el evento `tema:cambiado`, que redibuja los graficos. Verificado en el navegador: al alternar el modo, los ejes pasan de `#52525b` a `#a1a1aa` y la grilla de `#e4e4e7` a `#3f3f46`.
26. **RESUELTO** — La entrada "Usuarios y Roles" del sidebar solo marcaba activo con `routeIs('users.*')`, pero las rutas del modulo de roles son `roles.*`: en `/roles` y `/roles/create` ningun item del menu aparecia seleccionado. Ahora acepta ambas familias.
27. **RESUELTO** — El boton de PDF no era consistente: articulos, empleados y proveedores usaban `bg-red-600`, pero el link "PDF" del listado de ventas era gris y el "Descargar comprobante" del detalle era naranja de marca. Ahora **los seis son rojos**. Ademas, la ruta `users.pdf` existia y funcionaba, pero **ninguna vista tenia un boton** que llegara a ella: se agrego el boton "Exportar PDF" en `users/index.blade.php`.
28. **RESUELTO** — En modo oscuro quedaban textos apagados: los rotulos de las tarjetas del dashboard y los encabezados de la tabla de stock estaban en `zinc-400`/`zinc-500` sobre fondo grafito. Los rotulos principales pasaron a blanco puro y los textos de apoyo a `zinc-300`. Verificado en el navegador: 0 elementos con color `zinc-500` en el dashboard.
29. **RESUELTO** — El sidebar es grafito **en los dos modos** (siempre `bg-zinc-900`), asi que sus textos no pueden depender de la clase `dark`: se fijaron en `zinc-400` para que se lean igual con el tema claro y el oscuro.
30. **RESUELTO** — Los artículos no tenian forma de guardar una foto: la tabla `articles` no tenia ninguna columna de imagen. Ahora hay `articles.image` (nullable), subida desde el formulario, con reemplazo automatico de la imagen vieja y borrado del archivo al borrar el artículo.
31. **RESUELTO** — El logo era un SVG inline duplicado en el sidebar y en el login. Ahora es un archivo (`public/images/logo.svg`) mostrado con `<x-app-logo>`: se reemplaza por el logo propio sin tocar codigo.
32. **PENDIENTE** — El reporte PDF de artículos **no incluye la foto**. dompdf genera el PDF sin error pero no renderiza los SVG (0 objetos de imagen en el archivo resultante). Se decidio dejar el reporte como tabla: una columna de imágenes vacías se ve peor que ninguna. Si se quieren fotos en el PDF, hay que convertir las imagenes a PNG antes de pasarlas.
33. **PENDIENTE** — El modulo de artículos sigue cerrado para el rol Cliente (`role:Jefe|Administrador|Encargado|Deposito`): un cliente no puede ver el catalogo, solo el carrito. Es decision del profesor, escrita en el comentario de las rutas, pero como usuario final es raro: el cliente no ve los productos que puede comprar. Mismo tema con `/cart`, que muestra el flujo de caja ("seleccionar cliente") en vez de un carrito de compra.
34. **RESUELTO** — El rol de deposito se llamaba **"Depósito" (con tilde)** en `RoleSeeder` y `UserSeeder`, pero el codigo del profesor lo escribe **"Deposito" (sin tilde)**: `routes/web.php` (`role:Jefe|Administrador|Encargado|Deposito`) y `layouts/app.blade.php` (`@role('Jefe|Administrador|Encargado|Deposito')`). MySQL no distingue tildes al comparar (collation `*_ci`), asi que la base de datos SI encontraba el rol y no habia error visible; pero Spatie compara los nombres como cadenas de PHP, donde `'Depósito' === 'Deposito'` es **false**. Resultado: el usuario de deposito caia en **HTTP 403** en todo el modulo de articulos (su trabajo) y el link del sidebar tampoco le aparecia. Se unifico la grafia en "Deposito" (la que usa el codigo del profesor) y se agrego `tests/Feature/RolesTest.php` para que no vuelva: el test lee los roles de las rutas, de las directivas de Blade y de los seeders, y exige que existan **exactamente** en el catalogo.
35. **RESUELTO** — El usuario de deposito no podia ver el modulo de articulos por el bug 34. Verificado con barrido HTTP: antes 403, ahora **200**, y el link "Articulos" le aparece en el sidebar.
36. **RESUELTO** — Las descargas en PDF no se veian funcionar. Eran dos cosas juntas:
    - **Tres botones de exportacion** (empleados, proveedores, usuarios) tenian `target="_blank"`. Con `Content-Disposition: attachment` el navegador abre una pestania **vacia** y baja el archivo en silencio: el usuario ve "no pasa nada". Se saco el `target`.
    - **Cada comprobante de venta pesaba 968 KB**, de los cuales 858 eran la fuente: dompdf venia con `enable_font_subsetting => false` (su default) y embebia la DejaVu Sans **entera**. Se publico `config/dompdf.php` y se activo el subsetting: el PDF bajo a **39 KB**. Ademas las imagenes del PDF pasaron a JPEG (un PNG con canal alfa se embebe doble, imagen + mascara) y se borraron dos PNG que quedaban sin uso (1,4 MB).
    - En dompdf 3.x las opciones viven dentro de `dompdf.options.*`: el service provider mueve ahi las claves de nivel superior.
46. **RESUELTO** — **Borrar un artículo o un proveedor daba HTTP 500** por claves foraneas:
    - Un artículo vendido tiene items en carritos (`cart_items.id_article`) y ese item es el historial de la venta. Ahora `ArticleController@destroy` cuenta las ventas y avisa ("tiene N venta(s) registradas") en vez de reventar. Los items que quedaron en carritos SIN venta se limpian y el artículo se borra.
    - Un proveedor tiene materias primas (`raw_materials.id_supplier`) y esas pueden estar en la receta de un artículo (`article_materials`). Ahora `SupplierController@destroy` avisa si estan en alguna receta; si no, borra las materias con su stock.
    - De paso, `ArticleController@update` creaba la fila de stock si no existia: si el artículo se habia quedado sin fila, el update reventaba con un error de tipo.
47. **PENDIENTE** — El `SaleSeeder` no es idempotente (inserta las ventas con `insertGetId`): correr `db:seed` dos veces sobre la misma base duplica las ventas y viola la clave foranea de `receipts.id_sale`. El flujo previsto sigue siendo `migrate:fresh --seed`.
36. **RESUELTO** — El rol Cliente no podia ver el catalogo (403) y el carrito le mostraba el flujo de caja del mostrador ("seleccionar cliente"), que es para el personal. Ahora el Cliente compra como usuario final: ve el catalogo en modo lectura (sin botones de alta, edicion, borrado ni PDF), tiene **su propio carrito** (`/mi-carrito`, resuelto desde su ficha, no desde un parametro) y el mostrador sigue intacto para el personal.
37. **RESUELTO** — Un Cliente podia abrir el carrito de otro cliente con el id en la URL, ver sus productos y hasta confirmar la venta. Se agrego el middleware `carrito.propio` (`app/Http/Middleware/CarritoPropio.php`), que devuelve 403 si el rol Cliente pide un carrito que no es suyo. **OJO:** el middleware tiene que ir DENTRO del `Route::middleware([...])->group(...)`: si se encadena despues del `->group()`, Laravel ya registro las rutas con los atributos anteriores y no se aplica (se verifica con `route:list -v`).
38. **RESUELTO** — Habia un solo cliente (Ana Torres) con las 6 ventas, asi que el dashboard no mostraba movimiento. Ahora hay **11 clientes con 36 ventas** repartidas en los ultimos cuatro meses (ver seccion 5).
39. **RESUELTO** — `Sale::items()` estaba mal armada: era un `hasManyThrough` que generaba `where cart.id_cart = ?` con el **id_sale** en el binding, en vez del **id_cart**. Buscaba, entonces, el carrito cuyo `id_cart` coincidiera con el `id_sale` de la venta. Parecia funcionar porque en los datos sembrados `id_sale` e `id_cart` coinciden (el seeder crea ventas y carritos en el mismo orden), pero en una venta real no: la venta 39 tiene el carrito 40, y la relacion devolvia los items del carrito 39. **El listado de ventas mostraba el total y las unidades de otra venta.** Se cambio a `hasMany(CartItem::class, 'id_cart', 'id_cart')`, que ademas es mas simple (una tabla, sin join). Cubierto por `tests/Feature/SaleTotalTest.php`, que arma a proposito una venta cuyo `id_cart` es distinto de su `id_sale`.
40. **RESUELTO** — El rol Cliente veia "Venta #39" en el detalle de su propia compra, y el listado se titulaba "Ventas". Para el Cliente, una venta es una **compra**: el detalle dice "Compra #39", el listado "Mis compras", y no se muestra el campo "Empleado" (en una compra propia no hay vendedor). El flag `$esCliente` lo pasa el controlador.
41. **RESUELTO** — El link del panel de control en el sidebar decía "Dashboard" (en ingles). Ahora dice "Panel de control", que es lo que decia la seccion de arriba. Tambien "Top Productos Vendidos" -> "Productos Más Vendidos".
42. **RESUELTO** — En pantallas angostas, el titulo del encabezado y los botones no entraban en una linea: el flex los aplastaba y el ultimo boton quedaba cortado fuera de la pantalla. Se agrego `flex-wrap` al header del layout y a los headers de las vistas, y se separaron las acciones de las tablas (`space-x-2` -> `space-x-4`).
43. **PENDIENTE** — Un bloque `@php` dentro del slot de un componente `<x-app-layout>` **no define variables** para el resto de la vista, y un comentario `{{-- --}}` adentro de un bloque `@php` rompe el PHP con un error de sintaxis (Blade compila el `@php` antes de reemplazar los comentarios). Por eso los flags como `$esCliente` los pasan los controladores y no se calculan en las vistas.
44. **RESUELTO** — El rol Cliente veia el **panel de control del negocio**: la venta total de la empresa, el estado del inventario, los proveedores, las materias primas y la facturacion por medio de pago. Ademas podia pedir ese panel por el endpoint de Livewire. Ahora hay dos paneles:
    - **El del negocio** (KPIs de la empresa, 4 graficos, stock, proveedores): del personal (Jefe, Administrador, Encargado, Vendedor, Deposito).
    - **El del Cliente** (`dashboard/cliente.blade.php`): sus propias compras (compras realizadas, total gastado, articulos distintos, ultima compra y sus ultimas 5 compras). El menu dice "Mi panel" y el del personal "Panel de control".
    - El componente `App\Livewire\Dashboard` aborta con 403 si quien lo pide es un Cliente, para que no se lo pueda pedir por su endpoint.
45. **RESUELTO** — La imagen de la marca (el mosaico de 6 fichas) no llego como archivo, asi que se REHIZO el sistema de diseno en SVG: las ilustraciones de los 8 articulos y el logo. Todo queda en `App\Support\IlustracionArticulo`, que es el unico lugar del que salen las imagenes (lo usan el seeder `ImagenesArticuloSeeder` y el script `scripts/generar-imagenes-marca.php`). El logo quedo como `public/images/logo.svg` con el texto arcedado por `textPath`; el componente `<x-app-logo>` le pone un `?v=` con la fecha de modificacion del archivo, asi el navegador no se queda con el viejo.
48. **RESUELTO** — **Un usuario es O empleado O cliente**, pero la aplicacion los mezclaba. En el formulario de alta de empleados se ofrecian **todos los clientes** como candidatos (el criterio era "todo usuario sin ficha de `employee`", o sea todos los compradores), y el `orWhere` de la edicion iba **sin agrupar**: en SQL quedaba `WHERE NOT EXISTS(empleado) OR id_user = ?`, con el AND/OR mezclado. Ahora el criterio es el **ROL**: `User::scopeEsCliente()` (rol `Cliente`) y `User::scopeEsPersonal()` (rol distinto de `Cliente`), con `User::idRolCliente()` para buscar el id por nombre y nunca fijo. Son dos cosas distintas: alguien puede tener ficha de cliente y ser del personal.
49. **RESUELTO** — **Fuga de informacion entre roles.** El email de un cliente se le escapaba a mas gente de la que le correspondia:
    - `/cart` (el mostrador) mostraba el email de los 12 clientes en el selector: ahora muestra nombre y telefono.
    - `/sales` mostraba nombre + email de cada comprador: ahora el email solo lo ve quien gestiona las fichas (Jefe y Administrador); el resto del personal ve nombre y telefono.
    - `/sales/{id}` y el comprobante en PDF mostraban el email del cliente: mismo criterio.
    - El Cliente veia al personal en su compra (columna "Empleado", campo "Empleado" y "Atendido por" en HTML y PDF): ahora no lo ve, y en el PDF una venta suya es una **compra** (`comprobante-compra-39.pdf`).
    - Verificacion automatica: `scripts/buscar-fugas.php` entra con cada rol y busca el email de cualquier otro usuario en todas las paginas que ese rol puede ver. Cliente, Vendedor y Deposito: **0 fugas**. El administrador tiene 22, todas en `/usuarios` (gestionar cuentas) y `/sales` (los clientes que gestiona): es exactamente lo suyo. Cubierto por `tests/Feature/FugasTest.php`.
50. **RESUELTO** — **Faltaba la pantalla de alta de usuario**, y eso dejaba el alta de personal en banda muerta: el formulario de empleados solo ofrece cuentas libres, todas las existentes ya eran empleados o clientes, el desplegable salia vacio y no habia forma de crear una cuenta. Se agrego `/usuarios/create` (`UserController@create`/`@store` + `StoreUserRequest`): nombre, email, contrasena y rol. Si el rol es **Cliente** pide tambien la ficha y crea la fila en `customers` (sin esa fila el usuario entra pero no puede comprar: no tiene carrito ni historial). Si el rol es de personal, la cuenta queda disponible en el formulario de empleados. Cuando no quedan cuentas libres, ese formulario lo avisa y lleva al alta, en vez de mostrar un desplegable vacio.
51. **RESUELTO** — El `UserSeeder` insertaba los cuatro usuarios base **sin `created_at`**. Como el listado de usuarios ordena por `latest()` (`created_at DESC`), esos usuarios quedaban al final: el administrador no aparecia ni en la primera pagina. Los seeders ahora ponen las marcas de tiempo **solo al crear**, para que la siembra siga siendo idempotente (mismo criterio que `CustomerSeeder`).
52. **RESUELTO** — El listado de usuarios (`/usuarios`) mezclaba personal y clientes sin forma de separarlos. Ahora se puede filtrar por **tipo** (`Todos` / `Solo personal` / `Solo clientes`), por **rol** (los 6 del catalogo) y por **texto** (nombre o email). Los filtros vacios no recortan nada.
53. **RESUELTO** — El icono de la aplicacion era el logo de marca entero (`logo-marca.svg`, un cuadrado de 1024 px con el monograma "PF" y el texto PALLETS FLOW). En el sidebar (32 px) y en el login (44 px) ese texto se convertia en una mancha: no se leia ni que era un pallet. Ahora hay un **glifo** aparte, `public/images/icono-pallet.svg` (tablas, tacos y una hoja, con los mismos tonos de madera y el mismo contorno `#4A3626` del logo), que es el que usa el componente `<x-app-logo>`. El logo completo sigue donde hay espacio: los comprobantes en PDF y la marca de agua.
54. **RESUELTO** — `public/favicon.ico` estaba en **0 bytes**: el navegador pedia un archivo vacio y mostraba el icono por defecto. Ahora lo genera `scripts/generar-favicon.php`, que dibuja el pallet con GD y arma un ICO con el PNG adentro. Los dos layouts anuncian ademas el SVG y un PNG de 32 px con `<link rel="icon">`.
55. **RESUELTO** — **No habia forma de llegar al registro**: `auth/register.blade.php` existia (era la pantalla de Breeze, en ingles y sin el diseño del sistema) y la ruta `/register` estaba, pero **ningun enlace del login llevaba a ella**. Ahora el login tiene el boton "Crear una Cuenta" y la pantalla se rehizo con el split-screen del login, en español, pidiendo nombre, apellido, correo y contraseña (dos veces).
56. **RESUELTO** — **El registro publico no creaba la ficha del cliente**. Creaba la fila en `users` y nada mas, asi que el cliente nuevo quedaba con rol Cliente pero sin `customers`. Consecuencia: su panel mostraba todo en cero, y `/mi-carrito` lo mandaba a `/cart`, que a su vez manda al Cliente a `/mi-carrito`: **bucle infinito de redirecciones (ERR_TOO_MANY_REDIRECTS)**. La cuenta se guardaba, pero parecia que no. Ahora el registro crea las dos filas (con el nombre y el apellido del formulario), el correo se guarda en minusculas, y `User::asegurarFichaCliente()` crea la ficha si falta en el registro, en el cambio de rol, en el carrito y en el panel del cliente (los usuarios que ya habian quedado sin ficha se arreglan solos al entrar). `miCarrito` ya no puede rebotar a `/cart`: si falla, vuelve al panel.
57. **RESUELTO** — **Una venta se cobraba con un solo medio de pago.** En el mostrador es normal cobrar una parte en efectivo y el resto con transferencia, pero `sales.payment_method` era una sola cadena y el checkout un `<select>`. Ahora hay `sale_payments` (una fila por medio con su importe), el checkout manda `payments[]`, el servidor exige que la suma sea el total y `sales.payment_method` queda como **resumen** para el listado. El grafico de medios y el filtro de ventas miran el detalle.
58. **RESUELTO** — **El comprobante no desglosaba el IVA.** Mostraba solo el total. Ahora guarda `tax_rate`, `net_amount`, `tax_amount` y `total_amount` al emitir y los muestra (neto + IVA 21% + total). El desglose no se recalcula al abrir el comprobante: un documento emitido no cambia si despues cambia un precio (cubierto por test).
59. **RESUELTO** — **El comprobante mostraba el id de la venta** (`#39`), que es un numero interno. Ahora muestra el numero fiscal `0001-00000003` (punto de venta + correlativo **por cliente**) y debajo "Compra N.º 3 del cliente". El id se reemplazo en el listado, el detalle, el panel del cliente, el HTML, el PDF y el nombre del archivo. Las rutas siguen usando `id_sale`.
60. **PENDIENTE** — La numeracion del comprobante calcula `max(receipt_number) + 1` **sin lock**. Hoy hay una sola caja, asi que no hay carrera. Si algun dia hay varias cajas cobrando al mismo cliente en el mismo instante, dos ventas podrian llevarse el mismo numero: ahi hace falta bloquear por cliente (o una tabla de contadores).
61. **PENDIENTE** — **No hay CAE ni integracion con AFIP.** El sistema arma la numeracion y el desglose de IVA, pero no autoriza el comprobante electronicamente. Es alcance de otro sistema; si el profesor lo pide, hay que integrar un web service de facturacion.
62. **RESUELTO** — **Para corregir el pedido habia que volver al carrito.** En la pantalla de confirmacion el detalle era de solo lectura: si el vendedor o el cliente se equivocaba de producto o de cantidad, tenia que salir a `/cart`, corregir y entrar de nuevo a confirmar (perdiendo el armado del pedido). Ahora el checkout tiene, por renglon, un desplegable con el catalogo, un campo de cantidad y un boton **Quitar**, mas un **"+ Agregar"** abajo. Cambiar de producto toma el precio nuevo; cambiar solo la cantidad **no** cambia el precio con el que entro la venta; si el producto elegido ya estaba, los renglones se fusionan; el stock se controla contra la cantidad final y quitar el ultimo articulo vuelve al carrito. La cantidad se guarda al salir del campo, para que no se pueda confirmar la compra con una cantidad que quedo escrita sin guardar.

## 8. Convenciones para cambios futuros

- Mantener PKs `id_<entidad>` y `getRouteKeyName()` en modelos usados con route model binding.
- Comentarios en español; seguir el estilo de los controladores existentes.
- **Roles**: el catálogo es la tabla `roles` (columna `role_name`). Cualquier rol nuevo se crea ahí y se refleja en `spatie_roles`; a los usuarios se les asigna con `User::asignarRol($nombre)`, que escribe en los dos sistemas a la vez. No usar `syncRoles()` ni `assignRole()` por separado.
- **Autorización de usuarios/roles**: `User::isAdmin()` (Jefe o Administrador). Para proteger rutas con el middleware de Spatie usar `->middleware('role:Jefe|Administrador')`.
- **Interfaz**: el color de acento es `hazard` y las superficies son `zinc`. No volver a `indigo`, `blue`, `amber` ni `slate`. Toda superficie clara (`bg-white`, `text-gray-*`, `border-gray-*`) necesita su variante `dark:`; al agregar vistas, correr `php scripts/agregar-modo-oscuro.php` y despues `php scripts/auditar-modo-oscuro.php`.
- **Avisos y botones destructivos**: los colores de aviso tambien necesitan variante oscura (`bg-red-50`/`bg-red-100`/`bg-green-100` → `dark:bg-red-950/40`/`dark:bg-green-950/40`; `text-red-600/700/800` → `dark:text-red-400`/`-300`; `border-red-200` → `dark:border-red-900`). Sin eso quedan como recuadros casi blancos sobre el zinc. La auditoria ya controla estas familias: si da 0, esta bien.
- **Medios de pago**: cualquier medio nuevo se agrega **solo** en `App\Support\MediosPago` (constante + `todos()`). Lo usan el checkout, la validacion, el filtro y el comprobante. No escribir la lista de medios a mano en otro archivo.
- **Cobro**: el contrato es `payments[]` (`method` + `amount`), nunca un `payment_method` suelto. La suma tiene que dar el total (tolerancia `0.009`); si no, se rechaza y el carrito queda activo. `sales.payment_method` es un resumen: para saber cuanto entro por cada medio, mirar `sale_payments`.
- **IVA**: los precios del catalogo **incluyen IVA**. Usar `Iva::desglosar($total)` (neto redondeado, IVA por diferencia) y **guardar** el resultado en el comprobante. No recalcular al mostrar.
- **Comprobante**: el numero visible es `receipts.receipt_number` (correlativo por cliente), formateado con `Receipt::numeroComprobante()` → `0001-00000003`. El `id_sale` es interno: puede ir en la URL, pero no se muestra en pantalla, PDF ni nombre de archivo.
- **Editar el pedido**: se hace desde el checkout, no desde el carrito. Cambiar de producto o cantidad es `CartController@updateItem` (`PATCH /cart/{cart}/items/{item}`); quitar es `removeItem`. El precio se actualiza **solo** cuando cambia el producto. Si dos renglones quedan con el mismo articulo, se fusionan. Cualquier accion vuelve a `back()` (el checkout) salvo que el carrito quede vacio: ahi se vuelve al carrito.
- **Cantidad de a uno**: los botones − / + son el mismo `updateItem`, no rutas nuevas. En el carrito cada boton manda `name="quantity"` con el valor resultante (funciona sin JS); en el checkout `ajustarCantidad()` ajusta el campo y manda el form. `−` se deshabilita en 1 (para sacar el renglon esta "Quitar") y `+` al llegar al stock.
- **Modo oscuro**: la clase `dark` en `<html>` la pone el script del `<head>` segun `localStorage['tema']`. No usar `prefers-color-scheme` en las vistas ni poner la clase a mano: el interruptor es el unico que decide.
- **Dos tipos de compra, dos flujos**: el mostrador (`/cart`) es del personal y elige a quién le vende. El flujo del Cliente (`/mi-carrito`) es suyo y no lleva selector. Para agregar un flujo nuevo, decidir primero cual de los dos es.
- **Catalogo**: el Cliente ve `/articles` en modo lectura. Los controles de gestion (alta, edicion, borrado, PDF) van detras de `role:Jefe|Administrador|Encargado|Deposito` en las RUTAS, y la vista los oculta con el flag `$esCliente` que pasa el controlador. Las dos capas: la ruta es la seguridad, la vista es la usanza.
- **Cliente o personal, segun el ROL**: `User::scopeEsCliente()` y `User::scopeEsPersonal()` son el filtro oficial. No usar "tiene ficha de `customers`" como criterio de si alguien es cliente: son dos cosas distintas y se mezclan.
- **Datos personales**: el email de un cliente solo se muestra a quien gestiona las fichas (`$user->isAdmin()`: Jefe o Administrador). Para el resto del personal, nombre y telefono alcanzan. Para el Cliente, ademas, no se muestra el personal en su compra: ni la columna "Empleado", ni el campo "Empleado", ni el "Atendido por" del comprobante. Al agregar una pantalla que muestre clientes o ventas, usar los flags `$esCliente` y `$veEmails` que pasan los controladores.
- **Alta de cuentas**: hay dos caminos. **Publico**: cualquiera se registra solo en `/register` (el login tiene el boton "Crear una Cuenta") y queda como Cliente. **Interno**: `Usuarios y Roles -> Nuevo usuario` (`/usuarios/create`), donde el Administrador elige el rol. Los dos caminos crean la ficha de `customers` cuando el rol es Cliente. Para eso usar siempre `User::asegurarFichaCliente()`, que crea la ficha si falta: sin ella el cliente no puede comprar y el carrito entra en un bucle de redirecciones.
- **Verificar fugas**: despues de tocar algo que muestre datos de terceros, correr `php scripts/buscar-fugas.php`. Cliente, Vendedor y Deposito tienen que dar 0 fugas.
- Registrar cada cambio nuevo en este archivo (sección 4, 5, 6) y crear el plan siguiente en `ai_workspace/NNN_plan_<tema>.md` siguiendo `PLANtemplate.md`.

## 9. Planes de `ai_workspace`

| Archivo | Descripción | Estado |
|---|---|---|
| `PLANtemplate.md` | Plantilla ExecPlan (openai-codex-exec-plan) | Referencia. **No está en el repositorio** (ver deuda técnica 11) |
| `PLAN.md` | Ejemplo de ExecPlan completado (del repo origen, no pertenece a este proyecto) | Referencia |
| `001_plan_carrito_compras.md` | Scaffolding del Carrito de Compras | **Ejecutado** (commits `eb8fed6`, `2c57837`, `f23f1b7`). Los checkboxes de "Progress" siguen sin marcar |
