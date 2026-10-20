# Scripts de mantenimiento de la interfaz

Utilidades de linea de comandos para revisar y mantener el sistema visual de
la aplicacion (paleta industrial + modo oscuro). Se ejecutan con PHP desde la
raiz del proyecto.

| Script | Para que sirve |
|---|---|
| `auditar-paleta.php` | Recorre las paginas renderizadas y lista que familias de color usa cada una. Sirve para detectar que se colaron palettes viejos. |
| `auditar-modo-oscuro.php` | Busca clases de superficie claras (`bg-white`, `text-gray-700`, ...) que **no** tienen su variante `dark:`. Cada hallazgo es un elemento que se ve mal en modo oscuro. |
| `agregar-modo-oscuro.php` | Agrega automaticamente la variante `dark:` que falta en las vistas Blade. Es idempotente: si ya esta, no hace nada. |

## Uso

```bash
php scripts/auditar-paleta.php
php scripts/auditar-modo-oscuro.php
php scripts/agregar-modo-oscuro.php
```

Los dos auditores necesitan la aplicacion levantada (Apache de Laragon en
`http://127.0.0.1/Pallets-Flow-System/public`) y se autentican con
`admin@ecommerce.com` / `admin1234`.

## Cuando se agregan vistas nuevas

1. Escribir las clases de la vista.
2. Correr `php scripts/agregar-modo-oscuro.php`.
3. Correr `php scripts/auditar-modo-oscuro.php` para confirmar que no queden
   huecos.
4. `npm run build` para que Tailwind genere las utilidades nuevas.
