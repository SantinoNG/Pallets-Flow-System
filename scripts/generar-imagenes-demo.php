<?php

// Genera imagenes de demostracion para los articulos del catalogo.
//
// Son las ilustraciones de la marca PALLETS FLOW en SVG. Sirven para ver la
// interfaz con imagenes; cuando subas las fotos reales desde Articulos >
// Editar, reemplazan a estas.
//
// La logica de dibujo vive en App\Support\IlustracionArticulo, que tambien
// corre con `php artisan migrate:fresh --seed` (seeder ImagenesArticuloSeeder).
//
// Uso: php scripts/generar-imagenes-demo.php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Article;

(new \Database\Seeders\ImagenesArticuloSeeder())->run();

foreach (Article::all() as $articulo) {
    printf("  %-3s %-40s -> %s\n", $articulo->id_article, mb_substr($articulo->article_name, 0, 40), $articulo->image);
}

$conFoto = Article::whereNotNull('image')->count();

echo "\n  {$conFoto} de " . Article::count() . " artículos con imagen\n";
