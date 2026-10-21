<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Support\IlustracionArticulo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Genera la imagen de cada artículo del catálogo.
 *
 * Son ilustraciones de la marca PALLETS FLOW: pergamino crema, vineta
 * circular y el mueble dibujado en madera, con plantas. Se hacen en SVG para
 * que se vean nitidas a cualquier tamaño.
 *
 * Sirven para que el catálogo no quede vacío con una base recién sembrada.
 * Cuando se suban las fotos reales desde Articulos > Editar, reemplazan a
 * estas: el seeder no toca los artículos que ya tienen imagen.
 */
class ImagenesArticuloSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Article::all() as $articulo) {
            // No se pisa una foto real.
            if ($articulo->imagen_url !== null) {
                continue;
            }

            $nombreArchivo = \Illuminate\Support\Str::slug($articulo->article_name) . '.svg';

            Storage::disk('public')->put(
                'articles/' . $nombreArchivo,
                IlustracionArticulo::ficha($articulo->article_name)
            );

            $articulo->image = 'articles/' . $nombreArchivo;
            $articulo->save();
        }
    }
}
