<?php

/**
 * Genera el favicon de la aplicacion a partir del glifo del pallet.
 *
 * El icono de la marca esta en public/images/icono-pallet.svg, pero el
 * favicon necesita un formato binario: el navegador pide /favicon.ico por
 * su cuenta y un SVG no le sirve para eso.
 *
 * Como GD no dibuja SVG, el pallet se dibuja aqui con las MISMAS medidas
 * que tiene el SVG (las de un lienzo de 64x64). Si se cambia el SVG,
 * hay que cambiar las medidas de este archivo tambien.
 *
 * Uso: php scripts/generar-favicon.php
 */

$saliendo = dirname(__DIR__);

if (! function_exists('imagecreatetruecolor')) {
    fwrite(STDERR, "  La extension GD no esta habilitada en este PHP.\n");
    exit(1);
}

// Lienzo de 64x64: las medidas del SVG.
$lienzo = 64;

$colores = [
    'madera'  => [0xC0, 0x95, 0x66],
    'medio'   => [0xB9, 0x8F, 0x5E],
    'oscuro'  => [0xA9, 0x7F, 0x55],
    'sombra'  => [0x8E, 0x68, 0x43],
    'borde'   => [0x4A, 0x36, 0x26],
    'hoja'    => [0x6E, 0x91, 0x52],
];

/**
 * Dibuja el pallet en un lienzo de $tam pixels.
 */
function dibujarPallet(int $tam): GdImage
{
    global $colores;

    $img = imagecreatetruecolor($tam, $tam);

    imagealphablending($img, true);
    imagesavealpha($img, true);

    // Fondo transparente.
    $vacio = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefilledrectangle($img, 0, 0, $tam - 1, $tam - 1, $vacio);

    $u = $tam / 64.0;   // una unidad del lienzo de 64 en pixels

    $rgb = function (string $nombre) use ($colores, $img) {
        [$r, $g, $b] = $colores[$nombre];

        return imagecolorallocate($img, $r, $g, $b);
    };

    // Contorno de 3 unidades.
    imagesetthickness($img, max(1, (int) round(3 * $u)));

    // Tablas del piso superior, tacos del medio y tablas de abajo.
    $formas = [
        [5,  12, 54, 8, 'madera'],
        [5,  23, 54, 8, 'medio'],
        [9,  35, 10, 14, 'oscuro'],
        [27, 35, 10, 14, 'sombra'],
        [45, 35, 10, 14, 'oscuro'],
        [5,  52, 54, 8, 'madera'],
    ];

    foreach ($formas as [$x, $y, $w, $h, $color]) {
        $px = (int) round($x * $u);
        $py = (int) round($y * $u);
        $pw = (int) round($w * $u);
        $ph = (int) round($h * $u);

        imagefilledrectangle($img, $px, $py, $px + $pw - 1, $py + $ph - 1, $rgb($color));
        imagerectangle($img, $px, $py, $px + $pw - 1, $py + $ph - 1, $rgb('borde'));
    }

    // La hoja: el detalle "diseno natural" de la marca.
    imagesetthickness($img, max(1, (int) round(2 * $u)));

    $hoja = $rgb('hoja');

    imagefilledellipse(
        $img,
        (int) round(50 * $u),
        (int) round(8 * $u),
        (int) round(16 * $u),
        (int) round(12 * $u),
        $hoja
    );

    imageellipse(
        $img,
        (int) round(50 * $u),
        (int) round(8 * $u),
        (int) round(16 * $u),
        (int) round(12 * $u),
        $rgb('borde')
    );

    return $img;
}

/**
 * Escribe un archivo .ico con un PNG adentro.
 *
 * Formato ICO de Windows Vista: el directorio va al principio y el PNG
 * se embebe tal cual. Todos los navegadores actuales lo leen bien.
 */
function escribirIco(string $ruta, string $png, int $ancho): void
{
    $cabecera = pack('vvv', 0, 1, 1)          // reservado, tipo, cantidad
        . pack(
            'CCCCvvII',
            $ancho,
            $ancho,
            0,                                   // colores de la paleta
            0,                                   // reservado
            1,                                   // planos
            32,                                  // bits por pixel
            strlen($png),                       // tamano de la imagen
            22                                   // offset de la imagen
        );

    file_put_contents($ruta, $cabecera . $png);
}

// --- Generacion -------------------------------------------------------------

$carpeta = $saliendo . '/public/images';

/**
 * Devuelve el PNG de un lienzo como cadena.
 */
function pngDe(GdImage $img): string
{
    ob_start();
    imagepng($img, null, 9);

    return (string) ob_get_clean();
}

$png32 = pngDe(dibujarPallet(32));
file_put_contents($carpeta . '/favicon-32.png', $png32);

// El .ico se arma con el PNG de 64, que se ve mejor en pantallas grandes.
$png64 = pngDe(dibujarPallet(64));

escribirIco($saliendo . '/public/favicon.ico', $png64, 64);

echo PHP_EOL . "  Favicon generado:" . PHP_EOL;
echo '    public/favicon.ico            ' . filesize($saliendo . '/public/favicon.ico') . " bytes" . PHP_EOL;
echo '    public/images/favicon-32.png  ' . filesize($carpeta . '/favicon-32.png') . " bytes" . PHP_EOL;
echo PHP_EOL;
