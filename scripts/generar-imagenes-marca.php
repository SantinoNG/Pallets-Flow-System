<?php

// Genera las imagenes de la marca PALLETS FLOW.
//
// Son ilustraciones en SVG (no fotos): rehacen el lenguaje de la marca en
// pergamino, madera y plantas. Se ven nitidas a cualquier tamaño.
//
// Cuando lleguen las fotos reales, se suben desde Articulos > Editar y
// reemplazan a estas.
//
// Uso: php scripts/generar-imagenes-marca.php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Article;
use App\Support\IlustracionArticulo;
use Illuminate\Support\Facades\Storage;

$carpeta = 'articles';
$asignadas = 0;

// ---------------------------------------------------------------- artículos
foreach (Article::all() as $articulo) {
    $nombreArchivo = \Illuminate\Support\Str::slug($articulo->article_name) . '.svg';

    Storage::disk('public')->put(
        $carpeta . '/' . $nombreArchivo,
        IlustracionArticulo::ficha($articulo->article_name)
    );

    // La imagen anterior se borra del disco: no se acumulan.
    if ($articulo->image && $articulo->image !== $carpeta . '/' . $nombreArchivo) {
        Storage::disk('public')->delete($articulo->image);
    }

    $articulo->image = $carpeta . '/' . $nombreArchivo;
    $articulo->save();

    $asignadas++;
    echo '  ' . str_pad($articulo->article_name, 32) . ' -> ' . $nombreArchivo . "\n";
}

echo "\n  {$asignadas} artículos con imagen\n";

// ---------------------------------------------------------------- logo
file_put_contents(
    __DIR__ . '/../public/images/logo.svg',
    IlustracionArticulo::logo()
);

echo "  Logo de la marca -> public/images/logo.svg\n";
