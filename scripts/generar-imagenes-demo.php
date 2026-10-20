<?php

// Genera imagenes SVG de demonstracion para los articulos del catalogo.
//
// Son siluetas simples dibujadas con SVG, no fotos: sirven para ver como
// queda la interfaz con imagenes reales y de paso dejan el catalogo
// completo. Cuando tengas las fotos, subilas desde Articulos > Editar y la
// imagen generada se reemplaza sola.
//
// Uso: php scripts/generar-imagenes-demo.php
//
// Los archivos se guardan en storage/app/public/articles/ y se anotan en
// la columna `image` de la tabla `articles`.

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Article;
use Illuminate\Support\Facades\Storage;

/**
 * Devuelve el cuerpo del SVG segun el tipo de mueble que dice el nombre.
 *
 * Cada silueta esta dibujada sobre un lienzo de 200x200, con la misma
 * paleta que la interfaz: grafito con detalles en naranja de seguridad.
 */
function silueta(string $nombre): string
{
    $grafito = '#3F3F46';
    $gris    = '#D4D4D8';
    $naranja = '#EA580C';

    $n = mb_strtolower($nombre);

    // Sillon / silla
    if (str_contains($n, 'sill')) {
        return <<<SVG
    <rect x="55" y="28" width="90" height="58" rx="9" fill="$grafito"/>
    <rect x="47" y="86" width="106" height="24" rx="9" fill="$naranja"/>
    <rect x="56" y="110" width="12" height="52" rx="3" fill="$grafito"/>
    <rect x="132" y="110" width="12" height="52" rx="3" fill="$grafito"/>
    <rect x="70" y="44" width="60" height="10" rx="5" fill="$gris" opacity=".35"/>
SVG;
    }

    // Macetero / mesa de noche
    if (str_contains($n, 'macetero') || str_contains($n, 'noche')) {
        return <<<SVG
    <rect x="62" y="62" width="76" height="86" rx="7" fill="$grafito"/>
    <rect x="72" y="74" width="56" height="30" rx="4" fill="$gris"/>
    <rect x="92" y="86" width="16" height="6" rx="3" fill="$naranja"/>
    <rect x="72" y="112" width="56" height="26" rx="4" fill="$gris"/>
    <rect x="92" y="122" width="16" height="6" rx="3" fill="$naranja"/>
    <rect x="70" y="148" width="10" height="16" rx="2" fill="$grafito"/>
    <rect x="120" y="148" width="10" height="16" rx="2" fill="$grafito"/>
SVG;
    }

    // Estanteria
    if (str_contains($n, 'estanter')) {
        return <<<SVG
    <rect x="48" y="22" width="104" height="156" rx="7" fill="$grafito"/>
    <rect x="58" y="34" width="84" height="34" rx="3" fill="$gris"/>
    <rect x="58" y="80" width="84" height="34" rx="3" fill="$gris"/>
    <rect x="58" y="126" width="84" height="34" rx="3" fill="$gris"/>
    <rect x="96" y="44" width="8" height="14" rx="2" fill="$naranja"/>
    <rect x="112" y="90" width="8" height="14" rx="2" fill="$naranja"/>
    <rect x="86" y="136" width="8" height="14" rx="2" fill="$naranja"/>
SVG;
    }

    // Mueble de TV
    if (str_contains($n, 'tv')) {
        return <<<SVG
    <rect x="58" y="34" width="84" height="58" rx="5" fill="$grafito"/>
    <rect x="66" y="42" width="68" height="42" rx="2" fill="$gris"/>
    <rect x="92" y="92" width="16" height="12" fill="$grafito"/>
    <rect x="76" y="104" width="48" height="8" rx="3" fill="$grafito"/>
    <rect x="28" y="112" width="144" height="46" rx="7" fill="$grafito"/>
    <rect x="40" y="124" width="56" height="22" rx="3" fill="$gris"/>
    <rect x="104" y="124" width="56" height="22" rx="3" fill="$gris"/>
    <rect x="38" y="158" width="12" height="14" rx="2" fill="$naranja"/>
    <rect x="150" y="158" width="12" height="14" rx="2" fill="$naranja"/>
SVG;
    }

    // Cama
    if (str_contains($n, 'cama')) {
        return <<<SVG
    <rect x="28" y="38" width="144" height="50" rx="7" fill="$grafito"/>
    <rect x="28" y="88" width="144" height="48" rx="7" fill="$naranja"/>
    <rect x="42" y="100" width="50" height="24" rx="7" fill="$gris"/>
    <rect x="100" y="100" width="50" height="24" rx="7" fill="$gris"/>
    <rect x="36" y="136" width="12" height="34" rx="3" fill="$grafito"/>
    <rect x="152" y="136" width="12" height="34" rx="3" fill="$grafito"/>
SVG;
    }

    // Mesa (de comedor o ratona)
    if (str_contains($n, 'mesa')) {
        $alto = str_contains($n, 'ratona') ? 96 : 66;

        return <<<SVG
    <rect x="24" y="$alto" width="152" height="17" rx="4" fill="$grafito"/>
    <rect x="24" y="$alto" width="152" height="6" rx="3" fill="$naranja"/>
    <rect x="40" y="{$alto}" width="13" height="72" rx="3" fill="$grafito"/>
    <rect x="147" y="{$alto}" width="13" height="72" rx="3" fill="$grafito"/>
    <rect x="40" y="{$alto}" width="120" height="8" rx="3" fill="$grafito" opacity=".45"/>
SVG;
    }

    // Cualquier otro: caja generica
    return <<<SVG
    <rect x="50" y="60" width="100" height="80" rx="8" fill="$grafito"/>
    <rect x="50" y="60" width="100" height="14" rx="7" fill="$naranja"/>
    <rect x="66" y="90" width="68" height="10" rx="5" fill="$gris"/>
SVG;
}

/**
 * Envuelve la silueta en el SVG completo, con el fondo tramado de la
 * interfaz y el nombre del producto.
 */
function svg(string $nombre, string $cuerpo): string
{
    $nombre = htmlspecialchars($nombre, ENT_XML1 | ENT_QUOTES, 'UTF-8');

    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="600" height="600" role="img" aria-label="$nombre">
  <defs>
    <pattern id="h" width="10" height="10" patternTransform="rotate(45)" patternUnits="userSpaceOnUse">
      <line x1="0" y1="0" x2="0" y2="10" stroke="#000" stroke-opacity=".05" stroke-width="4"/>
    </pattern>
  </defs>
  <rect width="200" height="200" fill="#F4F4F5"/>
  <rect width="200" height="200" fill="url(#h)"/>
  <rect x="6" y="6" width="188" height="188" rx="6" fill="none" stroke="#D4D4D8" stroke-width="2"/>
  <g>
$cuerpo
  </g>
</svg>
SVG;
}

$carpeta = 'articles';
$conteo = 0;

foreach (Article::all() as $articulo) {
    $nombreArchivo = Str::slug($articulo->article_name) . '.svg';

    Storage::disk('public')->put($carpeta . '/' . $nombreArchivo, svg($articulo->article_name, silueta($articulo->article_name)));

    $articulo->image = $carpeta . '/' . $nombreArchivo;
    $articulo->save();

    $conteo++;
    echo sprintf("  %-3s %-40s -> %s\n", $articulo->id_article, mb_substr($articulo->article_name, 0, 40), $articulo->image);
}

echo "\n  $conteo imagenes generadas en storage/app/public/$carpeta\n";
