<?php

// Recorta las fichas de producto de la imagen de la marca.
//
// La imagen es un mosaico de 6 fichas (2 filas x 3 columnas) sobre un fondo
// de madera:
//
//   Fila 1:  [logo de la marca]  [Macetero]  [Estantería]
//   Fila 2:  [Sillón individual] [Mesa ratona] [Mueble de TV]
//
// No hay coordenadas fijas: las fichas se DETECTAN por color (son crema
// claro sobre la madera oscura) y se recortan solas. Asi el script sirve
// para cualquier version de la imagen.
//
// De cada ficha se sacan dos cosas:
//   - la ficha entera (con el marco crema y el texto)
//   - solo la foto del mueble (el circulo del medio), que es la que se usa
//     como foto del articulo: a 48 px el texto de la ficha no se lee.
//
// Uso: php scripts/recortar-fichas.php <imagen> [--solo-fotos]

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Article;
use Illuminate\Support\Facades\Storage;

$entrada = $argv[1] ?? null;

if (! $entrada || ! is_file($entrada)) {
    fwrite(STDERR, "  ERROR: no se encontro la imagen: {$entrada}\n");
    fwrite(STDERR, "  Uso: php scripts/recortar-fichas.php <ruta-de-la-imagen>\n");
    exit(1);
}

$soloFotos = in_array('--solo-fotos', $argv, true);

/**
 * True si el pixel es del fondo crema de la ficha.
 */
function esCrema(int $r, int $g, int $b): bool
{
    // El crema de las fichas: alto, calido y poco saturado.
    return $r > 215 && $g > 195 && $b > 150 && ($r - $b) < 70 && ($r - $g) < 45;
}

/**
 * Componentes conexas de una mascara, por inundacion de 4 direcciones.
 *
 * @param  array<int, array<int, bool>>  $mascara
 * @return array<int, array{int, int, int, int, int}>  [x, y, ancho, alto, area]
 */
function componentes(array $mascara, int $ancho, int $alto, int $minArea): array
{
    $visto = [];
    $salida = [];

    for ($y = 0; $y < $alto; $y++) {
        for ($x = 0; $x < $ancho; $x++) {
            if (! $mascara[$y][$x] || isset($visto[$y][$x])) {
                continue;
            }

            // Inundacion
            $pila = [[$x, $y]];
            $visto[$y][$x] = true;
            $minX = $maxX = $x;
            $minY = $maxY = $y;
            $area = 0;

            while ($pila) {
                [$cx, $cy] = array_pop($pila);
                $area++;

                $minX = min($minX, $cx);
                $maxX = max($maxX, $cx);
                $minY = min($minY, $cy);
                $maxY = max($maxY, $cy);

                foreach ([[$cx + 1, $cy], [$cx - 1, $cy], [$cx, $cy + 1], [$cx, $cy - 1]] as $vecino) {
                    [$vx, $vy] = $vecino;

                    if ($vx < 0 || $vy < 0 || $vx >= $ancho || $vy >= $alto) {
                        continue;
                    }
                    if (! $mascara[$vy][$vx] || isset($visto[$vy][$vx])) {
                        continue;
                    }

                    $visto[$vy][$vx] = true;
                    $pila[] = [$vx, $vy];
                }
            }

            if ($area >= $minArea) {
                $salida[] = [$minX, $minY, $maxX - $minX + 1, $maxY - $minY + 1, $area];
            }
        }
    }

    return $salida;
}

// ---------------------------------------------------------------- cargar
$info = getimagesize($entrada);
$origen = imagecreatefromstring(file_get_contents($entrada));
$ancho = imagesx($origen);
$alto = imagesy($origen);

echo "  Imagen: " . basename($entrada) . " ({$ancho} x {$alto})\n";

// ---------------------------------------------------------------- detectar fichas
// Se trabaja sobre una copia reducida para que sea rapido.
$escala = max(1, (int) floor($ancho / 240));
$miniAncho = (int) floor($ancho / $escala);
$miniAlto = (int) floor($alto / $escala);

$mini = imagecreatetruecolor($miniAncho, $miniAlto);
imagecopyresampled($mini, $origen, 0, 0, 0, 0, $miniAncho, $miniAlto, $ancho, $alto);

$mascara = [];

for ($y = 0; $y < $miniAlto; $y++) {
    for ($x = 0; $x < $miniAncho; $x++) {
        $rgb = imagecolorat($mini, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        $mascara[$y][$x] = esCrema($r, $g, $b);
    }
}

$fichas = componentes($mascara, $miniAncho, $miniAlto, (int) ($miniAncho * $miniAlto * 0.01));

echo '  Fichas detectadas: ' . count($fichas) . "\n";

if (count($fichas) < 6) {
    fwrite(STDERR, "  ERROR: se esperaban 6 fichas y se detectaron " . count($fichas) . "\n");
    exit(1);
}

// Se ordenan por fila y luego por columna.
usort($fichas, function ($a, $b) use ($miniAlto) {
    if (abs($a[1] - $b[1]) > $miniAlto / 4) {
        return $a[1] <=> $b[1];
    }

    return $a[0] <=> $b[0];
});

// Cada ficha: la mayor componente de su fila puede partirse en varias
// (por ejemplo, si el crema del circulo queda separado). Se agrupan las
// que se toquen o solapen.
$grupos = [];

foreach ($fichas as $f) {
    $colocado = false;

    foreach ($grupos as $i => $grupo) {
        $g = $grupo[0];

        if ($f[0] < $g[0] + $g[2] && $g[0] < $f[0] + $f[2]
            && $f[1] < $g[1] + $g[3] && $g[1] < $f[1] + $f[3]) {
            // Se funden.
            // Se funden las dos cajas.
            $x = min($g[0], $f[0]);
            $y = min($g[1], $f[1]);
            $grupos[$i] = [[
                $x,
                $y,
                max($g[0] + $g[2], $f[0] + $f[2]) - $x,
                max($g[1] + $g[3], $f[1] + $f[3]) - $y,
            ], $f];
            $colocado = true;
            break;
        }
    }

    if (! $colocado) {
        $grupos[] = [$f];
    }
}

// Se toman las 6 fichas mas grandes y se ordenan en 2x3.
$grupos = array_map(fn ($g) => $g[0], $grupos);
usort($grupos, fn ($a, $b) => $b[4] <=> $a[4]);
$grupos = array_slice($grupos, 0, 6);
usort($grupos, function ($a, $b) use ($miniAlto) {
    if (abs($a[1] - $b[1]) > $miniAlto / 4) {
        return $a[1] <=> $b[1];
    }

    return $a[0] <=> $b[0];
});

// ---------------------------------------------------------------- recortar
/**
 * Recorta la foto circular del centro de una ficha.
 *
 * La foto es el circulo del medio. Se busca, dentro de la parte baja de la
 * ficha (donde esta el texto), el bloque de pixeles que NO son crema y que
 * forma el circulo.
 */
function fotoDeLaFicha($origen, array $caja): array
{
    [$x, $y, $ancho, $alto] = $caja;

    // Se mira solo el 25% de abajo para abajo: el titulo esta arriba.
    $y0 = (int) ($y + $alto * 0.22);
    $subAlto = (int) ($alto * 0.78);

    $mascara = [];

    for ($yy = 0; $yy < $subAlto; $yy++) {
        for ($xx = 0; $xx < $ancho; $xx++) {
            $rgb = imagecolorat($origen, $x + $xx, $y0 + $yy);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;

            // Foto = no crema. El circulo tambien tiene zonas crema
            // (paredes claras), asi que se usa el criterio laxo.
            $mascara[$yy][$xx] = ! esCrema($r, $g, $b);
        }
    }

    $componentes = componentes($mascara, $ancho, $subAlto, (int) ($ancho * $subAlto * 0.04));

    if (! $componentes) {
        return [$x, $y, $ancho, $alto];
    }

    // El bloque mas grande es el circulo de la foto.
    usort($componentes, fn ($a, $b) => $b[4] <=> $a[4]);
    $c = $componentes[0];

    return [$x + $c[0], $y0 + $c[1], $c[2], $c[3]];
}

// Que articulo es cada ficha, segun la posicion en el mosaico.
$orden = [
    0 => ['logo',  'Logo de la marca'],
    1 => ['Macetero de pallet', 'Macetero'],
    2 => ['Estantería de pallet', 'Estantería'],
    3 => ['Sillón individual de pallet', 'Sillón individual'],
    4 => ['Mesa ratona de pallet', 'Mesa ratona'],
    5 => ['Mueble TV de pallet', 'Mueble de TV'],
];

$carpetaArticulos = 'articles';
$carpetaLogo = 'images';

foreach ($grupos as $i => $g) {
    $caja = [
        $g[0] * $escala,
        $g[1] * $escala,
        $g[2] * $escala,
        $g[3] * $escala,
    ];

    [$nombre, $etiqueta] = $orden[$i] ?? ["ficha-$i", "Ficha $i"];

    // La ficha completa (el diseno tal cual).
    $ficha = imagecreatetruecolor($caja[2], $caja[3]);
    imagecopy($ficha, $origen, 0, 0, $caja[0], $caja[1], $caja[2], $caja[3]);
    imagejpeg($ficha, "$carpetaArticulos/recorte-$nombre.jpg", 88);
    imagedestroy($ficha);

    // La foto del mueble (el circulo del medio).
    $cajaFoto = fotoDeLaFicha($origen, $caja);
    $foto = imagecreatetruecolor($cajaFoto[2], $cajaFoto[3]);
    imagecopy($foto, $origen, 0, 0, $cajaFoto[0], $cajaFoto[1], $cajaFoto[2], $cajaFoto[3]);
    imagejpeg($foto, "$carpetaArticulos/foto-$nombre.jpg", 88);
    imagedestroy($foto);

    printf("  %-26s ficha %dx%d   foto %dx%d\n", $etiqueta, $caja[2], $caja[3], $cajaFoto[2], $cajaFoto[3]);
}

// El logo de la marca (la primera ficha) se guarda aparte.
if (! empty($grupos)) {
    $g = $grupos[0];
    $caja = [$g[0] * $escala, $g[1] * $escala, $g[2] * $escala, $g[3] * $escala];
    $logo = imagecreatetruecolor($caja[2], $caja[3]);
    imagecopy($logo, $origen, 0, 0, $caja[0], $caja[1], $caja[2], $caja[3]);
    imagejpeg($logo, "$carpetaLogo/logo-marca.jpg", 92);
    imagedestroy($logo);
    echo "  Logo de la marca guardado en images/logo-marca.jpg\n";
}

imagedestroy($origen);
imagedestroy($mini);

// ---------------------------------------------------------------- asignar
/*
 * Las fotos se cuelgan de los artículos del catálogo por el nombre.
 *
 * La foto que se usa es la del círculo: a 48 px (la miniatura del listado)
 * el texto de la ficha no se lee, y el círculo sí.
 */
$asignadas = [];

foreach ($orden as [$clave, $etiqueta]) {
    if ($clave === 'logo') {
        continue;
    }

    $articulo = Article::where('article_name', $etiqueta)->first();

    if (! $articulo) {
        echo "  AVISO: no existe el artículo '{$etiqueta}'\n";
        continue;
    }

    $ruta = "$carpetaArticulos/foto-$clave.jpg";

    // La imagen anterior se borra del disco: no se acumulan.
    if ($articulo->image && $articulo->image !== $ruta) {
        Storage::disk('public')->delete($articulo->image);
    }

    $articulo->image = $ruta;
    $articulo->save();

    $asignadas[] = $articulo->article_name;
}

echo "\n  Asignadas " . count($asignadas) . " fotos:\n";

foreach ($asignadas as $nombre) {
    echo "    - {$nombre}\n";
}

$sinFoto = Article::whereNull('image')->pluck('article_name');

if ($sinFoto->isNotEmpty()) {
    echo "\n  Sin foto (quedan con el placeholder):\n";

    foreach ($sinFoto as $nombre) {
        echo "    - {$nombre}\n";
    }
}

echo "\n  Recortes en storage/app/public/\n";

