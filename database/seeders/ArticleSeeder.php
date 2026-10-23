<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Catalogo de articulos, stock y RECETA de cada producto.
 *
 * La receta dice que materias primas y en que cantidad lleva cada mueble
 * (metros de madera y tela, bolsas de clavos, cajas de tornillos, litros de
 * barniz, planchas de espuma...). Con eso el detalle del articulo puede
 * calcular el costo de materiales y para cuantas unidades alcanza el stock.
 *
 * Es idempotente: actualiza por nombre, resetea el stock de demo y vuelve a
 * armar la receta. No toca las ventas ni los carritos.
 */
class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $articulos = [
            [
                'article_name' => 'Sillón individual de pallet',
                'description'  => 'Sillón rústico fabricado con pallets reciclados, incluye almohadón',
                'unit_price'   => 35000.00,
                'stock'        => 8, 'min' => 2,
                'receta'       => [
                    'Tabla de pallet reciclada'        => 2,
                    'Madera de pino 2x4'               => 3,
                    'Clavos para pallet (bolsa 1 kg)'  => 1,
                    'Tela de tapicería'                => 1.5,
                    'Espuma de alta densidad'          => 1,
                    'Hilo de coser'                    => 1,
                    'Barniz poliuretánico'             => 0.5,
                ],
            ],
            [
                'article_name' => 'Sillón doble de pallet',
                'description'  => 'Sillón para dos personas fabricado con pallets, estilo industrial',
                'unit_price'   => 55000.00,
                'stock'        => 5, 'min' => 2,
                'receta'       => [
                    'Tabla de pallet reciclada'        => 4,
                    'Madera de pino 2x4'               => 5,
                    'Clavos para pallet (bolsa 1 kg)'  => 1,
                    'Tela de tapicería'                => 2.5,
                    'Espuma de alta densidad'          => 2,
                    'Hilo de coser'                    => 1,
                    'Barniz poliuretánico'             => 0.75,
                ],
            ],
            [
                'article_name' => 'Mesa ratona de pallet',
                'description'  => 'Mesa baja para living fabricada con pallets reciclados',
                'unit_price'   => 28000.00,
                'stock'        => 12, 'min' => 3,
                'receta'       => [
                    'Tabla de pallet reciclada'        => 2,
                    'Madera de pino 2x4'               => 4,
                    'Clavos para pallet (bolsa 1 kg)'  => 1,
                    'Lija para madera (pliego)'        => 1,
                    'Barniz poliuretánico'             => 0.5,
                ],
            ],
            [
                'article_name' => 'Mesa de comedor de pallet',
                'description'  => 'Mesa grande para comedor fabricada con pallets, 6 personas',
                'unit_price'   => 75000.00,
                'stock'        => 4, 'min' => 1,
                'receta'       => [
                    'Tabla de pallet reciclada'          => 6,
                    'Madera de pino 2x4'                 => 8,
                    'Madera de pino 1x4'                 => 4,
                    'Tornillos autoperforantes (caja x100)' => 1,
                    'Cola de carpintero'                 => 0.5,
                    'Lija para madera (pliego)'          => 2,
                    'Barniz poliuretánico'               => 1,
                ],
            ],
            [
                'article_name' => 'Estantería de pallet',
                'description'  => 'Estante de pared fabricado con pallets, 3 niveles',
                'unit_price'   => 22000.00,
                'stock'        => 10, 'min' => 3,
                'receta'       => [
                    'Tabla de pallet reciclada'          => 2,
                    'Listón de madera 1x2'               => 6,
                    'Escuadras metálicas'                => 4,
                    'Tornillos autoperforantes (caja x100)' => 1,
                    'Barniz poliuretánico'               => 0.5,
                ],
            ],
            [
                'article_name' => 'Mueble TV de pallet',
                'description'  => 'Rack para televisor fabricado con pallets reciclados',
                'unit_price'   => 42000.00,
                'stock'        => 6, 'min' => 2,
                'receta'       => [
                    'Tabla de pallet reciclada'          => 3,
                    'Madera de pino 2x4'                 => 4,
                    'Bisagras metálicas'                 => 2,
                    'Tornillos autoperforantes (caja x100)' => 1,
                    'Lija para madera (pliego)'          => 1,
                    'Barniz poliuretánico'               => 0.75,
                ],
            ],
            [
                'article_name' => 'Cama de pallet 2 plazas',
                'description'  => 'Base de cama fabricada con pallets, incluye cabecera',
                'unit_price'   => 90000.00,
                'stock'        => 3, 'min' => 1,
                'receta'       => [
                    'Tabla de pallet reciclada'          => 8,
                    'Madera de pino 2x4'                 => 10,
                    'Madera de pino 1x4'                 => 4,
                    'Clavos para pallet (bolsa 1 kg)'    => 2,
                    'Tornillos autoperforantes (caja x100)' => 1,
                    'Cola de carpintero'                 => 1,
                    'Lija para madera (pliego)'          => 2,
                    'Barniz poliuretánico'               => 1.5,
                ],
            ],
            [
                'article_name' => 'Macetero de pallet',
                'description'  => 'Macetero vertical de jardín fabricado con pallets',
                'unit_price'   => 15000.00,
                'stock'        => 15, 'min' => 5,
                'receta'       => [
                    'Tabla de pallet reciclada'        => 1,
                    'Listón de madera 1x2'             => 2,
                    'Tachuelas (bolsa 500 g)'          => 1,
                    'Lija para madera (pliego)'        => 1,
                    'Sellador para madera'             => 0.25,
                ],
            ],
        ];

        // Descripcion -> id, para no depender de los ids autoincrementales.
        $materias = DB::table('raw_materials')->pluck('id_raw_material', 'description');

        foreach ($articulos as $datos) {
            DB::table('articles')->updateOrInsert(
                ['article_name' => $datos['article_name']],
                [
                    'description' => $datos['description'],
                    'unit_price'  => $datos['unit_price'],
                    'updated_at'  => now(),
                    'created_at'  => now(),
                ]
            );

            $idArticulo = DB::table('articles')
                ->where('article_name', $datos['article_name'])
                ->value('id_article');

            DB::table('product_stock')->updateOrInsert(
                ['id_article' => $idArticulo],
                [
                    'remaining_quantity' => $datos['stock'],
                    'minimum_stock'      => $datos['min'],
                    'updated_at'         => now(),
                    'created_at'         => now(),
                ]
            );

            // La receta se rehace entera: si un insumo se saco del catalogo,
            // no puede quedar colgado en el producto.
            DB::table('article_materials')->where('id_article', $idArticulo)->delete();

            foreach ($datos['receta'] as $descripcion => $cantidad) {
                $idMateria = $materias[$descripcion] ?? null;

                if (! $idMateria) {
                    continue; // RawMaterialSeeder tiene que correr antes
                }

                DB::table('article_materials')->insert([
                    'id_article'        => $idArticulo,
                    'id_raw_material'   => $idMateria,
                    'quantity_required' => $cantidad,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            }
        }
    }
}
