<?php

// Genera imagenes de demostracion para los articulos del catalogo.
//
// Son siluetas simples dibujadas con SVG, no fotos: sirven para ver como
// queda la interfaz con imagenes. Cuando tengas las fotos, subilas desde
// Articulos > Editar y la imagen generada se reemplaza sola.
//
// La logica de dibujo vive en el seeder ImagenesArticuloSeeder, que tambien
// corre con `php artisan migrate:fresh --seed`. Este script solo lo invoca.
//
// Uso: php scripts/generar-imagenes-demo.php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Article;
use Database\Seeders\ImagenesArticuloSeeder;

(new ImagenesArticuloSeeder())->run();

foreach (Article::all() as $articulo) {
    printf("  %-3s %-40s -> %s\n", $articulo->id_article, mb_substr($articulo->article_name, 0, 40), $articulo->image);
}

$conFoto = Article::whereNotNull('image')->count();

echo "\n  {$conFoto} de " . Article::count() . " artículos con imagen\n";
