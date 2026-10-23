# Resumen de modificaciones — Pallets Flow System

Proyecto: `C:\laragon\www\Pallets-Flow-System`
Fecha: 22 de octubre de 2026

**11 tandas de trabajo · 5 archivos tocados en esta última (editar el pedido desde el checkout)**

Nada fue commiteado a GitHub. Todo el trabajo está en commits locales.

> El resumen está ordenado de lo más nuevo a lo más viejo. La **sección 0**
> documenta la última tanda: corregir el pedido desde el checkout (cambiar de
> producto, cambiar la cantidad, quitar un renglón) sin volver al carrito. La
> **sección 0.1**, el pago combinado, el IVA y la numeración de los
> comprobantes. La sección 1 documenta la separación entre clientes y
> personal, y el cierre de las fugas de información. La sección 2, el
> rediseño de la interfaz con paleta industrial y modo oscuro.

---

## 0. Editar el pedido desde el checkout

Antes, si el cliente o el vendedor se daba cuenta en la pantalla de
confirmación de que se había equivocado de producto (o de cantidad), tenía que
volver al carrito, corregir y entrar de nuevo a confirmar. En el mostrador eso
es perder el pedido que se estaba armando.

Ahora el **checkout es editable**: cada renglón tiene

- un **desplegable** con todo el catálogo, para cambiar de producto;
- un campo de **cantidad**;
- un botón **Quitar**;
- y abajo, un **“+ Agregar”** para sumar otro producto sin salir.

Todo se resuelve en la misma pantalla: cada acción recarga el checkout ya con
el total nuevo, el IVA y la primera fila de pago actualizadas.

### Cómo se guarda

| Acción | Qué hace el servidor |
| --- | --- |
| Elegir otro producto | `PATCH /cart/{carrito}/items/{item}` (`CartController@updateItem`). Cambia el artículo **y toma el precio del catálogo del producto nuevo**. |
| Cambiar la cantidad | El mismo `updateItem`. **No toca el precio**: se respeta el precio con el que el artículo entró al carrito (si el catálogo subió después, la venta no se re-precifica sola). |
| Elegir un producto que ya está en el pedido | Los dos renglones se **fusionan** en uno, sumando las cantidades. No queda el mismo producto dos veces. |
| Quitar | `DELETE /cart/{carrito}/items/{item}` (el `removeItem` de siempre). Si era el último artículo, vuelve al carrito: un checkout vacío no tiene nada que confirmar. |

Reglas que se mantienen: el **stock** se controla contra la cantidad final
(incluida la suma al fusionar), y el artículo tiene que pertenecer **a ese
carrito** — si no, 404 (no se puede editar el renglón de otro cambiando el id
de la URL).

### Detalles de la pantalla

- La cantidad se guarda **al salir del campo** (`change`), no solo con el
  botón ↻: así no se puede escribir una cantidad nueva y apretar “Finalizar
  compra” perdiendo el cambio.
- Los productos **sin stock** aparecen deshabilitados en los desplegables (el
  que ya está en el pedido sigue seleccionable, para poder corregirlo).
- Cada renglón muestra el **precio unitario** y el **stock disponible**.

### Tests

`tests/Feature/CheckoutEdicionTest.php`, 12 casos: cambiar cantidad, que el
precio no se mueva al cambiar solo la cantidad, cambiar producto (toma el
precio nuevo), fusión de renglones repetidos, tope de stock, cantidad mínima
1, quitar un renglón (queda en el checkout), quitar el último (vuelve al
carrito), ítem de otro carrito (404), el Cliente puede editar el suyo, la
pantalla tiene los controles, y el checkout vacío redirige al carrito.

De paso, `scripts/testear-flujo-venta.php` ya no usa el artículo 1 fijo: elige
el primero **con stock** leyendo el desplegable. Cada corrida registra una
venta y descuenta stock, así que antes fallaba por datos y no por código.

### Modo oscuro: el “Quitar” y los avisos

Revisando la pantalla del carrito apareció un detalle: el botón **Quitar**
tenía `bg-red-50` **sin variante `dark:`**, así que en modo oscuro se veía como
un recuadro casi blanco sobre la tabla zinc (y lo mismo pasaba con los avisos
de éxito/error y con los botones de borrar de los listados).

Se corrigió el patrón en **13 vistas** (25 reemplazos): los avisos, los chips de
stock y los botones destructivos ahora usan `dark:bg-*-950/40` con texto
`dark:text-*-300`/`-400`, el mismo criterio que el resto del sistema.

Además, el botón “Quitar” del carrito quedó **igual que el del checkout**, y
`scripts/auditar-modo-oscuro.php` ahora también revisa las familias de color de
aviso (`bg-red-50`, `bg-red-100`, `bg-green-100`, `text-red-600/700/800`,
`text-green-700/800`, `border-red-200`, `border-green-400`, …). Antes solo
miraba los grises, por eso el hueco pasó desapercibido: hoy la auditoría da
**0 clases claras sin equivalente oscuro**.

---

## 0.1 Cobro: pago combinado, IVA y numeración del comprobante

Tres cosas del circuito de venta que no cerraban con la realidad:

1. Una venta se cobraba con **un solo medio de pago**. En el mostrador es
   normal cobrar una parte en efectivo y el resto con transferencia.
2. El comprobante mostraba el **total sin desglosar el IVA**. Un comprobante
   de venta en Argentina tiene que mostrar neto + IVA = total.
3. El comprobante mostraba **el id de la venta** (`#39`). Ese número es
   interno y no le dice nada al cliente.

### Pago combinado

Se agregó la tabla **`sale_payments`**: una fila por cada medio usado, con su
importe. La venta (`sales.payment_method`) guarda un **resumen** para el
listado y los filtros:

- un solo medio → `Efectivo`
- varios medios → `Pago combinado (Efectivo + Transferencia)`

El checkout ahora arma una **lista de pagos**:

```php
'payments' => [
    ['method' => 'Efectivo',      'amount' => 30000],
    ['method' => 'Transferencia', 'amount' => 40000],
]
```

En pantalla hay una fila por medio, un botón **"+ Agregar otro medio"** y un
resumen en vivo que muestra *Pagado / Falta / Sobra / Pago completo*. Al
agregar una fila, el importe se precarga con lo que falta.

**El servidor valida que los pagos sumen exactamente el total** (tolerancia de
un centavo por los redondeos). Si no cierra, no se registra la venta y el
carrito sigue activo para reintentar. También se ignoran las filas vacías que
quedan en el formulario.

El catálogo de medios vive en un solo lugar: `App\Support\MediosPago`
(Efectivo, Tarjeta de débito, Tarjeta de crédito, Transferencia, Mercado Pago).
Lo usan el formulario, la validación, el filtro del listado y el comprobante.

El gráfico "Medios de pago" del dashboard ahora suma **`sale_payments`**, no el
resumen: si una venta se pagó mitad y mitad, cada peso cae en su medio. El
filtro `/sales?pago=Efectivo` también encuentra las ventas combinadas.

### IVA

Los precios del catálogo **ya incluyen IVA** (como en la calle). El comprobante
lo desglosa hacia atrás:

```
Subtotal (neto)   $57.851,24
IVA 21%           $12.148,76
Total             $70.000,00
```

La alícuota y el cálculo están en `App\Support\Iva`. El neto se redondea y el
IVA sale por diferencia, así **neto + IVA da exactamente el total** (probado con
varios importes).

El desglose **se guarda en el comprobante** (`tax_rate`, `net_amount`,
`tax_amount`, `total_amount`), no se calcula al mostrarlo: un comprobante
emitido no puede cambiar si después cambia un precio. Hay un test que lo fija.

> **Alcance:** no se emite CAE ni se habla con AFIP. El sistema arma la
> numeración y el desglose; la autorización fiscal electrónica es de otro
> sistema.

### Numeración del comprobante

El comprobante ya no muestra el id de la venta. Muestra un número con el
formato argentino:

```
Comprobante N° 0001-00000004
               ^^^^ ^^^^^^^^
               |    +-- 4ta compra del cliente
               +------- punto de venta
```

El correlativo es **por cliente**: su primera compra es la 1, la segunda la 2.
Se guarda en `receipts.receipt_number` al emitir y se calcula con
`max(receipt_number) + 1` para ese cliente, dentro de la misma transacción del
cobro.

Debajo del número, el comprobante aclara **"Compra N.º 4 del cliente"**, para
que se vea la relación sin tener que explicarla.

El número también reemplazó al id en:

- el listado de ventas (columna "Comprobante")
- el detalle de la venta (título y bloque "Comprobante")
- el panel del cliente (columna "Compra")
- el nombre del PDF: `comprobante-venta-0001-00000004.pdf`

Las rutas siguen usando `id_sale` por dentro: es la clave, no un número que se
muestre.

### Migraciones y datos existentes

| Migración | Qué hace |
|---|---|
| `2026_10_22_000001_create_sale_payments_table` | Crea el detalle de pagos |
| `2026_10_22_000002_add_fiscal_fields_to_receipts_table` | Agrega número, punto de venta y desglose de IVA |

La segunda **numera y desglosa los comprobantes que ya existen**: recorre las
ventas ordenadas por cliente, les asigna 1, 2, 3… y calcula neto/IVA desde los
items del carrito. También crea la fila de pago que falta en las ventas viejas.

Se hizo con el query builder y no con los modelos: una migración no puede
depender de código de la aplicación, que cambia con el tiempo.

En la base de desarrollo, después de la migración: **0 comprobantes sin número,
0 sin total y 0 ventas sin detalle de pago**; la suma de los pagos coincidió con
la de las ventas.

`SaleSeeder` también quedó actualizado: numera por cliente, desglosa el IVA y
deja **una venta combinada sembrada** (Ana, mitad efectivo y mitad tarjeta)
para que el pago combinado se vea en la demo.

### Tests

`tests/Feature/PagoCombinadoTest.php` — **18 casos**:

- pago combinado guarda un renglón por medio y el resumen correcto
- pago simple guarda un solo renglón
- pagos que suman de menos o de más se rechazan y no crean la venta
- medio desconocido y lista vacía se rechazan
- las filas vacías del formulario se ignoran
- numeración correlativa por cliente (1, 2) e independiente entre clientes
- IVA 21%: 12.100 → neto 10.000 + IVA 2.100
- el IVA guardado no cambia si después cambia el precio del artículo
- el comprobante muestra el número y **no** el id de la venta
- el comprobante lista los dos medios del pago combinado
- el PDF se descarga con el número en el nombre
- el listado filtra por un medio de un pago combinado
- la venta combinada sembrada tiene su detalle
- el desglose cierra para varios importes

**Total del proyecto: 153 tests, 1131 assertions, todos en verde.**

### Lo que se tocó

| Archivo | Cambio |
|---|---|
| `app/Support/Iva.php` | **nuevo** — alícuotas y desglose |
| `app/Support/MediosPago.php` | **nuevo** — catálogo y resumen |
| `app/Models/SalePayment.php` | **nuevo** — detalle de un pago |
| `app/Models/Receipt.php` | número, etiqueta y desglose |
| `app/Models/Sale.php` | `payments()`, `esPagoCombinado()`, `numeroComprobante()` |
| `app/Http/Controllers/CartController.php` | cobro con lista de pagos, numeración y IVA |
| `app/Http/Controllers/SaleController.php` | filtro por detalle, PDF con número, `payments` |
| `app/Http/Controllers/DashboardController.php` | `receipt` y `payments` en el panel del cliente |
| `app/Livewire/Dashboard.php` | gráfico de medios sobre `sale_payments` |
| `resources/views/cart/checkout.blade.php` | filas de pago + resumen en vivo |
| `resources/views/cart/receipt.blade.php` | número, IVA y detalle de pagos |
| `resources/views/sales/pdf.blade.php` | número, IVA y detalle de pagos |
| `resources/views/sales/show.blade.php` | número y desglose |
| `resources/views/sales/index.blade.php` | columna "Comprobante" |
| `resources/views/dashboard/cliente.blade.php` | columna "Compra" con el número |
| `database/seeders/SaleSeeder.php` | numeración, IVA y venta combinada |
| `scripts/testear-flujo-venta.php` | prueba el cobro combinado por HTTP real |

---

## 1. Roles: clientes y personal bien separados, y sin fugas de información

En el sistema **un usuario es O empleado O cliente**, pero la aplicación los
trataba como si fueran la misma cosa. Eso producía dos problemas: en el
apartado de usuarios aparecían mezclados los empleados y los clientes, y la
información personal de unos se le escapaba a los demás.

### Los tres focos de fuga que había

| Dónde | Qué se escapaba |
|---|---|
| `/employees/create` y `/employees/{id}/edit` | El desplegable **ofrecía a los clientes** como candidatos a empleados, con su email |
| `/cart` (el mostrador) | El selector de clientes mostraba **el email de los 12 clientes** |
| `/sales` | La columna "Cliente" mostraba **nombre + email** de cada comprador, y la columna "Empleado" mostraba el personal al Cliente |

El más grave era el primero: el `orWhere` del `EmployeeController` iba **sin
agrupar**, así que en SQL quedaba `WHERE NOT EXISTS(empleado) OR id_user = ?`
y el AND/OR se mezclaba. Y el criterio estaba mal: se ofrecía "todo usuario
sin ficha de empleado", que es literalmente **todos los clientes**.

### Qué se hizo

**El criterio pasa a ser el ROL, no "tiene ficha".** Son dos cosas distintas:
alguien puede tener ficha de cliente y ser del personal (le compro a la
empresa y después entró a trabajar), y al revés, un cliente recién creado
todavía no tiene la ficha cargada. Se agregó a `App\Models\User`:

- `scopeEsCliente()` → usuarios cuyo rol es `Cliente`
- `scopeEsPersonal()` → usuarios cuyo rol **no** es `Cliente`
- `idRolCliente()` → busca el id del rol por nombre, nunca fijo

**El formulario de empleados** ahora ofrece solo usuarios con rol de
personal que todavía no son empleados, con el `orWhere` agrupado
correctamente.

**El listado de usuarios** (`/usuarios`) se puede filtrar por:

- **Tipo**: `Todos` / `Solo personal` / `Solo clientes`
- **Rol**: cualquiera de los 6 del catálogo
- **Texto**: busca por nombre o por email

Con los filtros vacíos no se recorta nada.

**El email de un cliente solo lo ve quien gestiona las fichas** (Jefe y
Administrador):

| Quién | Listado de ventas | Detalle / comprobante |
|---|---|---|
| Jefe / Administrador | Nombre + email | Email + teléfono + dirección |
| Vendedor / Encargado / Depósito | Nombre + **teléfono** | Sin email |
| Cliente | Su nombre y su teléfono | Su nombre, su teléfono, su dirección |

**El Cliente no ve al personal en su compra**: se quitó la columna "Empleado"
del listado, el campo "Empleado" del detalle, el "Atendido por" del
comprobante en HTML y el "Atendido por" del PDF. En la cabecera del PDF y en
el nombre del archivo, una venta del Cliente es una **compra**
(`comprobante-compra-39.pdf`).

### Alta de usuario: faltaba, y dejaba el alta de personal en banda muerta

El formulario de empleados solo ofrece cuentas libres. Como **todas** las
cuentas existentes ya eran empleados o clientes, el desplegable salía
**vacío** y no había ninguna forma de crear un usuario nuevo: el alta de
personal era un callejón sin salida.

Se agregó la pantalla **Usuarios y Roles → Nuevo usuario** (`/usuarios/create`):

- Nombre, email, contraseña (+ confirmación) y rol.
- Si el rol es **Cliente**, pide también la ficha (nombre, apellido, teléfono
  y dirección) y la crea en `customers`. Sin esa fila el usuario entra al
  sistema pero no puede comprar: no tiene carrito ni historial.
- Si el rol es de personal, la cuenta queda disponible en el formulario de
  empleados.

Cuando no quedan cuentas libres, el formulario de empleados lo dice y lleva
directo a la pantalla de alta, en vez de mostrar un desplegable vacío sin
explicación.

### Un bug de orden que escondía las cuentas

El `UserSeeder` inserta los cuatro usuarios base **sin `created_at`**. Como el
listado ordena por `latest()` (`created_at DESC`), esos usuarios quedaban al
final de la lista: el administrador no aparecía ni en la primera página.
Ahora los seeders ponen las marcas de tiempo al crear (y solo al crear, para
que la siembra siga siendo idempotente).

### Cómo se verificó

Se escribió `scripts/buscar-fugas.php`, que entra con cada rol y revisa todas
las páginas que ese rol puede ver, buscando el email de **cualquier otro
usuario**:

```
admin@ecommerce.com          25 paginas    0 bloqueadas    22 fugas   <- es su trabajo
vendedor@ecommerce.com       8 paginas    17 bloqueadas    0 fugas
deposito@ecommerce.com      11 paginas    14 bloqueadas    0 fugas
cliente@gmail.com            6 paginas    17 bloqueadas    0 fugas
```

Las 22 "fugas" del administrador están en `/usuarios` (gestionar cuentas) y en
`/sales` (el email de los clientes que gestiona): **es exactamente lo que le
corresponde ver**. Los otros tres roles, en cero.

Además se agregaron **26 tests** (`tests/Feature/FugasTest.php` y
`tests/Feature/UsuariosTest.php`) que fijan estas reglas para que no se
vuelvan a romper.

### El icono de la aplicación: un pallet que se lee

El sidebar y el login usaban el **logo de marca entero** (`logo-marca.svg`): un
cuadrado de 1024 px con el monograma "PF" y el texto PALLETS FLOW debajo. En
el sidebar el espacio es de **32 px** y en el login de **44 px**: a ese tamaño
el texto no se lee, queda una mancha y lo único que se distingue es el
cuadrado marrón.

Se hizo un **glifo aparte**, `public/images/icono-pallet.svg`: un pallet de
madera (dos tablas de arriba, tres tacos, una tabla de abajo) con la hoja
verde de la marca en la esquina. Usa los mismos tonos de madera y el mismo
contorno `#4A3626` del logo, así que se reconoce como la misma marca, pero se
lee de un vistazo.

| Archivo | Donde va |
|---|---|
| `public/images/icono-pallet.svg` | Sidebar y login (32 y 44 px) |
| `public/images/logo-marca.svg` | Comprobantes en PDF y marca de agua |

De paso, `public/favicon.ico` estaba en **0 bytes** (el navegador pedía un
archivo vacío y mostraba su icono por defecto). Ahora lo genera
`scripts/generar-favicon.php`, que dibuja el pallet con GD y arma el ICO con
el PNG adentro, y los dos layouts anuncian el SVG y un PNG de 32 px.

### Registro público: faltaba el botón, y el alta dejaba al cliente sin ficha

La ruta `/register` existía desde Breeze, pero **no había ningún enlace que
llevara a ella**: el login no la ofrecía. Y la pantalla era la de Breeze por
defecto, en inglés y sin el diseño del sistema.

El botón ya está en el login (`Crear una Cuenta`) y la pantalla se rehizo con
el mismo split-screen del login: nombre, apellido, correo, contraseña y
repetir contraseña.

Pero el problema de fondo era otro. El registro creaba la cuenta en `users`
**y nada más**. La ficha en `customers` no se creaba, y toda la lógica de
compras se apoya en ella (`cart.id_customer`, `sales.id_customer`). Con el
cliente recién registrado pasaba esto:

1. Entraba al panel y veía todo en cero (su panel no encontraba sus compras).
2. Tocaba "Mi carrito" y `/mi-carrito` lo mandaba a `/cart` porque no tenía
   ficha.
3. `/cart` **lo volvía a mandar a `/mi-carrito`** (esa página es del
   mostrador, y al rol Cliente se lo deriva a su carrito).
4. El navegador cortaba con **ERR_TOO_MANY_REDIRECTS**.

O sea: la cuenta se guardaba, pero quedaba inutilizable, y por eso parecía
que "no se guardaba".

**Qué se hizo**

- El registro crea ahora las dos filas: la cuenta y la ficha, con el nombre y
  el apellido del formulario. El rol sigue fijo en `Cliente` (no viene del
  request: si no, cualquiera se registraría como Administrador).
- El correo se guarda **en minúsculas**, para que `Pepe@Mail.com` y
  `pepe@mail.com` sean la misma cuenta. (La regla `lowercase` de Laravel no
  servía: rechaza el alta en vez de normalizar.)
- Nuevo `User::asegurarFichaCliente()`: devuelve la ficha y **la crea si
  falta**. Lo usan el registro, el cambio de rol a Cliente, el carrito y el
  panel del cliente. Sirve de red de seguridad: los usuarios que ya habían
  quedado sin ficha se arreglan solos al entrar.
- `miCarrito` ya no puede rebotar al mostrador: si algo falla, vuelve al panel
  (nunca a `/cart`, que es el otro extremo del bucle).
- Se completaron las fichas de los clientes que ya estaban sin ella (PEPE).

**Cómo se verificó**: `RegistrationTest` tiene 10 casos — el alta crea la
ficha, el email se normaliza, un email repetido se rechaza, el cliente nuevo
abre `/mi-carrito` sin bucle y puede agregar artículos a su carrito, y un
cliente viejo sin ficha se arregla solo.

---

## 2. Interfaz: paleta industrial + modo oscuro

La interfaz tenía **tres acentos conviviendo** y ningún modo oscuro:

| Pantalla | Acento |
|---|---|
| `/dashboard`, `/cart`, `/sales` | `amber` (naranja) |
| `/articles`, `/roles`, `/profile` | `indigo` |
| `/suppliers`, `/employees`, `/usuarios/1/roles` | `blue` |
| Sidebar y login (split-screen) | `amber` |
| Componentes de Breeze (los que se usan en **todas** las páginas) | `indigo` |

El resultado era visible: el login mostraba el logo naranja junto a un botón
indigo.

### Qué se hizo

**Paleta.** Se unificó todo a un único color de acento, `hazard` (naranja de
seguridad `#F97316`), sobre superficies `zinc` (grafito neutro, sin el tinte
azul de `slate`). `zinc` y `hazard` se definen en `tailwind.config.js`: para
cambiar el color de marca de la aplicación hay que tocar **un solo archivo**.

En el CSS compilado quedaron **0 ocurrencias** de `indigo`, `blue`, `amber` y
`slate` (antes: 194 clases renombradas en 32 archivos).

**Modo oscuro.** Se agregó el interruptor, la preferencia persistida en
`localStorage` y las variantes `dark:` en todas las vistas:

- `darkMode: 'class'` en `tailwind.config.js`: la clase `dark` en `<html>` la
  decide el interruptor, no el sistema operativo.
- El script del `<head>` aplica la clase **antes de pintar**: al recargar en
  modo oscuro no hay destello blanco.
- Componente `x-theme-toggle` (botón sol/luna), en el login y en el panel.
- La auditoría de las **15 páginas** (13 autenticadas + login y registro) da
  **0 clases claras sin equivalente oscuro**. Las vistas `*/pdf.blade.php` se
  excluyen a propósito: se imprimen en blanco.

**Tipografía.** `Figtree` figuraba en la configuración pero **nunca se cargaba**
(no había `@font-face` ni link a Google Fonts), así que el navegador usaba la
fuente del sistema. Ahora se cargan **Barlow** (grotesca técnica) para la
interfaz e **IBM Plex Mono** para los datos, y las celdas de dato de las
tablas (ID, precios, cantidades, stock) usan `font-mono tabular-nums` para que
las columnas de decimales alineen.

**Detalles.** El botón principal pasó de `bg-gray-800` a `bg-hazard-600` (la
acción primaria es del color de marca); los radios de esquina se achicaron
(`rounded-lg` = 0.25rem); la paginación de Laravel, que usaba su azul, se
publicó al proyecto y se reestiló.

### PDF siempre en rojo

El botón de PDF no era consistente: artículos, empleados y proveedores usaban
`bg-red-600`, pero el link "PDF" del listado de ventas era gris y el
"Descargar comprobante" del detalle era naranja de marca.

Ahora **los seis son rojos**. Es la única acción de la interfaz que conserva
el rojo de Tailwind, y es a propósito: el rojo queda reservado para lo que
genera un documento o saca datos del sistema, y lo separa del naranja de
marca.

De paso: la ruta `users.pdf` existía y funcionaba, pero **ninguna vista tenía
un botón** que llegara a ella. Se agregó el "Exportar PDF" en
`users/index.blade.php`.

### Rótulos en blanco

En modo oscuro quedaban textos apagados sobre el fondo grafito. Los rótulos
en mayúsculas de las tarjetas del dashboard ("Ventas del Periodo", "Artículos
Activos"...) y los encabezados de la tabla de stock pasaron a **blanco puro**
(`zinc-100`): son el texto que define cada tarjeta. El texto de apoyo
(subtítulos, contadores) se queda en gris claro (`zinc-300`) para no competir
con los datos.

Verificado en el navegador: **0 elementos** con el gris apagado `zinc-500` en
el dashboard.

### Archivos

| Archivo | Cambio |
|---|---|
| `tailwind.config.js` | `darkMode: 'class'`, paleta `hazard`, fuentes Barlow + IBM Plex Mono, radios achicados, sombras duras |
| `resources/css/app.css` | Variables de superficie para el modo oscuro, scrollbars oscuros, campos de formulario |
| `resources/views/components/theme-toggle.blade.php` | **Nuevo** — el interruptor sol/luna |
| `resources/views/layouts/app.blade.php` | Script anti-destello, fuentes reales, interruptor, superficies `zinc` |
| `resources/views/layouts/guest.blade.php` | Ídem para el login |
| `resources/views/components/*.blade.php` | Variantes `dark:` y acento `hazard` en los 11 componentes de Breeze |
| `resources/views/**` (52 vistas) | Renombre de paleta y variantes `dark:` |
| `resources/views/vendor/pagination/` | **Nuevo** — paginación de Laravel reestilada |
| `tests/Feature/InterfazTest.php` | **Nuevo** — 5 casos, 354 aserciones |
| `scripts/` | **Nuevo** — 3 scripts de mantenimiento + README |

### Cómo se verificó

```cmd
php scripts/auditar-modo-oscuro.php   REM 0 huecos en 15 páginas
php scripts/auditar-paleta.php         REM un solo acento en toda la app
php artisan test --filter=InterfazTest REM 5 passed (354 assertions)
```

`InterfazTest` deja fijo el comportamiento: el script del tema va antes del
`<body>`, el interruptor está en el login y en el panel, ninguna página deja
superficies sin variante oscura, la paleta vieja no sobrevive y el acento
sigue siendo `hazard`.

### Una decisión que conviene que revise el profesor

El **naranja de seguridad** es una lectura de "industrial" (señalización de
bodega, ITV, construcción). Si el profesor prefiere otra lectura —acero
azulado, gris con un único acento, o monochrome— el cambio es de **una línea**
en `tailwind.config.js` más el renombre correspondiente. Todo el modo oscuro
queda igual.

---

## Resultado

| Indicador | Antes | Después |
|---|---|---|
| Tests | 5 pasan / 20 fallan | **135 pasan / 0 fallan** |
| Rutas que devuelven HTTP 500 | 4+ | **0** |
| Tablas en la base | 21 (3 a mano) | **28 (todas por migración)** |
| `php artisan migrate:fresh` | Rompía la base | **Deja la base completa y funcional** |
| Asignar roles a un usuario | Renderizaba 0 opciones | **6 opciones, funciona** |
| Registro de usuario nuevo | HTTP 500 | **Funciona** |
| Editar el propio perfil | Error "email ya registrado" | **Funciona** |
| Usuario no-admin cambia roles | **Se lo podía** | **403 Forbidden** |
| Colores de acento en la interfaz | 3 (`amber`, `indigo`, `blue`) | **1 (`hazard`)** |
| Páginas con modo oscuro completo | 0 | **15 de 15** |
| Archivos con encoding roto | 1 | **0** |
| Rol "depósito" entrando a Artículos | **HTTP 403** | **HTTP 200** |
| Rutas que devuelven 404 o 500 | ? | **0** (35 rutas × 5 roles) |
| Cliente viendo el catálogo | **HTTP 403** | **HTTP 200** (modo lectura) |
| Clientes con historial de compras | 1 | **11** |
| Ventas en los últimos 4 meses | 6 | **34** |
| Emails ajenos que ve el Cliente | **Todos** | **0** |
| Emails ajenos que ve el Vendedor | **Todos** | **0** |
| Clientes ofrecidos como empleados | **Todos** | **0** |
| Desplegable del alta de empleado | **Vacío, sin explicación** | **Avisa y lleva al alta de usuario** |

---

## El Cliente como usuario que compra

El rol Cliente no podia **ver el catálogo** (403) y el carrito le mostraba el
flujo de caja del mostrador ("seleccionar cliente"), que es para el personal.
Ahora compra como usuario final.

### Catálogo

`/articles` pasó a ser visible para todo usuario autenticado. Lo que el
Cliente **no** puede es gestionarlo: el alta, la edición, el borrado y el
reporte PDF siguen detrás de `role:Jefe|Administrador|Encargado|Deposito`.

La vista usa un flag `$esCliente` que pasa el controlador, para ocultar los
controles de administración. **Las dos capas hacen falta**: la ruta es la
seguridad, la vista es la usanza.

### Carrito propio

Se agregaron dos rutas y un controlador:

| Ruta | Qué hace |
|---|---|
| `GET /mi-carrito` | El carrito del Cliente (se le crea si no tiene) |
| `POST /mi-carrito/items` | Agrega un artículo a SU carrito |

El carrito se resuelve **desde su ficha de cliente**, no desde un parámetro:
un Cliente no puede meter cosas en el carrito de otro. El mostrador
(`/cart`) sigue siendo del personal, con su selector de clientes.

Y para que no pueda ni mirar el carrito ajeno, se agregó el middleware
`carrito.propio`, que devuelve **403** si el rol Cliente pide un carrito que
no es suyo.

### Diez clientes más y cuatro meses de compras

| Seeder | Qué hace |
|---|---|
| `CustomerSeeder` | 10 clientes más, cada uno con su usuario y su ficha |
| `SaleSeeder` | 36 ventas repartidas entre los 11 clientes, en los últimos 4 meses |
| `ImagenesArticuloSeeder` | Silueta SVG para cada artículo sin foto |

El filtro **"Últimos 90 días"** del dashboard ahora muestra 4 meses con
ventas (jul → oct, $4.581.500) y el de "Últimos 6 meses", 7 meses. Antes
había un solo cliente con 6 ventas.

---

## El Cliente ya no ve el panel de la empresa

El rol Cliente entraba a `/dashboard` y veía **el panel del negocio**: la venta
total de la empresa, el estado del inventario, los proveedores, las materias
primas y la facturación por medio de pago. Con once clientes en la base, eso
le mostraba los datos de los demás.

Ahora hay **dos paneles**:

| | Del negocio | Del Cliente |
|---|---|---|
| **Quién lo ve** | Jefe, Administrador, Encargado, Vendedor, Depósito | Solo el Cliente |
| **Qué muestra** | KPIs de la empresa, 4 gráficos, stock, proveedores | Sus compras: cuántas hizo, cuánto gastó, qué compró |
| **Ruta** | `/dashboard` | `/dashboard` (misma ruta, según el rol) |
| **Menú** | "Panel de control" | "Mi panel" |

Además, el componente Livewire del panel de negocio **aborta con 403** si
quien lo pide es un Cliente: sin eso se lo podía pedir por su endpoint y
llevarse los KPIs de la empresa aunque la vista no los mostrara.

---

## Testeo de la aplicación

Barrido de las 34 rutas GET con la sesión de cada uno de los 4 roles, sobre
HTTP real:

| Rol | Rutas | Bloqueadas por control de acceso | Con problema |
|---|---|---|---|
| Administrador | 34 | 0 | **0** |
| Deposito | 34 | 14 | **0** |
| Vendedor | 34 | 18 | **0** |
| Cliente | 34 | 18 | **0** |

Además, el flujo de venta completo (crear carrito → agregar ítem → checkout →
confirmar → comprobante en PDF) pasa **11 de 11 pasos**.

### Bug que encontró el testeo: el rol de depósito no podía entrar a Artículos

El `RoleSeeder` creaba el rol como **"Depósito"** (con tilde), pero el código
del profesor lo escribe **"Deposito"** (sin tilde):

```php
// routes/web.php
Route::middleware(['auth', 'role:Jefe|Administrador|Encargado|Deposito'])->group(...)

// layouts/app.blade.php
@role('Jefe|Administrador|Encargado|Deposito')
```

MySQL no distingue tildes al comparar (collation `*_ci`), así que la base de
datos **sí** encontraba el rol y no había error visible. Pero Spatie compara
los nombres de rol como cadenas de PHP, y ahí la comparación es exacta:

```php
'Depósito' === 'Deposito'   //   false
```

Resultado: el usuario de depósito caía en **HTTP 403** en todo el módulo de
artículos —justo donde su rol debería poder entrar— y el link del sidebar
tampoco le aparecía.

Se unificó la grafía en "Deposito" (la que usa el código del profesor) y se
agregó `tests/Feature/RolesTest.php`, que lee los roles de las rutas, de las
directivas de Blade y de los seeders, y exige que existan **exactamente** en
el catálogo. También se agregó la migración que renombra la fila existente.

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

`RoleController`, en cambio, sí usaba `App\Models\Role` — por eso el CRUD de
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

- `sessions` — para `SESSION_DRIVER=database`
- `jobs`, `job_batches`, `failed_jobs` — para `QUEUE_CONNECTION=database`
- `password_reset_tokens` — la usa el "olvidé mi contraseña" de Breeze

Todas tienen guarda `if (!Schema::hasTable(...))` para que no choquen si ya
existieran.

> **Nota**: antes de esta migración esas tablas se habían creado a mano con SQL
> sobre MySQL. Eso era frágil: un `migrate:fresh` las borraba y rompía la app.
> Ahora forman parte del esquema versionado.

---

## `getRouteKeyName()` en 11 modelos

Diez modelos ya tienen `$primaryKey = 'id_x'` (porque el profesorcreó las
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
`Missing parameter: [id]` — por ejemplo en las URLs firmadas de verificación de
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
`role_name` — `name`. Es un cambio de mayor alcance y probablemente deserve una
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

REM 1. Tests (debe dar 153 passed)
php artisan test

REM 2. La base completa desde cero
php artisan migrate:fresh --seed

REM 3. Sintaxis de todo
php -l app\Models\User.php

REM 4. Flujo de venta completo por HTTP real (incluye cobro combinado)
php scripts\testear-flujo-venta.php
```

Para ver el módulo de roles funcionando, entrar como administrador en
`http://localhost/Pallets-Flow-System/public/` y abrir *Usuarios* — asignar rol.

Con `vendedor@ecommerce.com / vendedor1234` se puede comprobar que
`/usuarios` devuelve **403**.

Para ver el **pago combinado y el comprobante**: entrar como vendedor, abrir
*Carrito*, agregar artículos, ir al checkout, **+ Agregar otro medio**, repartir
los importes hasta que diga *Pago completo* y confirmar. El comprobante muestra
el número `0001-XXXXXXXX` (la compra N.º del cliente), el neto, el IVA y cada
medio con su importe. En `/ventas` hay una venta combinada sembrada (Ana).
