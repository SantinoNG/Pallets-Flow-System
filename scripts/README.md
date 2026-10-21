# Scripts de mantenimiento y testeo

Utilidades de linea de comandos para revisar, mantener y **testear** el
sistema. Se ejecutan con PHP desde la raiz del proyecto.

## Mantenimiento de la interfaz

| Script | Para que sirve |
|---|---|
| `auditar-paleta.php` | Recorre las paginas renderizadas y lista que familias de color usa cada una. Sirve para detectar que se colaron palettes viejos. |
| `auditar-modo-oscuro.php` | Busca clases de superficie claras (`bg-white`, `text-gray-700`, ...) que **no** tienen su variante `dark:`. Cada hallazgo es un elemento que se ve mal en modo oscuro. |
| `agregar-modo-oscuro.php` | Agrega automaticamente la variante `dark:` que falta en las vistas Blade. Es idempotente: si ya esta, no hace nada. |
| `generar-imagenes-demo.php` | Genera siluetas SVG de los artículos del catalogo y las guarda en `storage/app/public/articles/`. Sirven para ver la interfaz con imagenes; cuando subas las fotos reales desde *Articulos > Editar*, reemplazan a estas. |

## Testeo

| Script | Para que sirve |
|---|---|
| `testear-rutas.php` | Barrido de **todas** las rutas GET con la sesion de un usuario. Reporta el codigo HTTP de cada una. Sirve para verificar de un golpe que no hay 404 ni 500, y para ver que rutas bloquea el control de acceso de cada rol (403). |
| `testear-flujo-venta.php` | Prueba funcional del flujo de venta sobre HTTP real: crear carrito, agregar item, checkout, confirmar y generar el comprobante en PDF. |

## Uso

```bash
# Mantenimiento
php scripts/auditar-paleta.php
php scripts/auditar-modo-oscuro.php
php scripts/agregar-modo-oscuro.php
php scripts/generar-imagenes-demo.php

# Testeo (necesitan la aplicacion levantada en Apache de Laragon)
php scripts/testear-rutas.php                                   # admin
php scripts/testear-rutas.php cliente@gmail.com cliente1234    # otro rol
php scripts/testear-flujo-venta.php
```

> **Nota sobre las cookies:** los scripts manejan las cookies en memoria,
> leyendo las cabeceras `Set-Cookie` de la respuesta. No usan el cookie jar
> de curl porque en PHP 8 ese archivo no se escribe hasta que el handle se
> destruye, y dentro de un script eso pasa al final: el servidor se
> recibiria sin la cookie de sesion y todo daria 403.

## Cuando se agregan vistas nuevas

1. Escribir las clases de la vista.
2. Correr `php scripts/agregar-modo-oscuro.php`.
3. Correr `php scripts/auditar-modo-oscuro.php` para confirmar que no queden
   huecos.
4. `npm run build` para que Tailwind genere las utilidades nuevas.
5. Correr `php scripts/testear-rutas.php` para confirmar que la vista nueva
   responde 200.
