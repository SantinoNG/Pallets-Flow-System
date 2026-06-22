<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ArticleSeeder extends Seeder {
    public function run(): void {
        DB::table('articles')->insert([
            [
                'article_name' => 'Sillón individual de pallet',
                'description'  => 'Sillón rústico fabricado con pallets reciclados, incluye almohadón',
                'unit_price'   => 35000.00,
            ],
            [
                'article_name' => 'Sillón doble de pallet',
                'description'  => 'Sillón para dos personas fabricado con pallets, estilo industrial',
                'unit_price'   => 55000.00,
            ],
            [
                'article_name' => 'Mesa ratona de pallet',
                'description'  => 'Mesa baja para living fabricada con pallets reciclados',
                'unit_price'   => 28000.00,
            ],
            [
                'article_name' => 'Mesa de comedor de pallet',
                'description'  => 'Mesa grande para comedor fabricada con pallets, 6 personas',
                'unit_price'   => 75000.00,
            ],
            [
                'article_name' => 'Estantería de pallet',
                'description'  => 'Estante de pared fabricado con pallets, 3 niveles',
                'unit_price'   => 22000.00,
            ],
            [
                'article_name' => 'Mueble TV de pallet',
                'description'  => 'Rack para televisor fabricado con pallets reciclados',
                'unit_price'   => 42000.00,
            ],
            [
                'article_name' => 'Cama de pallet 2 plazas',
                'description'  => 'Base de cama fabricada con pallets, incluye cabecera',
                'unit_price'   => 90000.00,
            ],
            [
                'article_name' => 'Macetero de pallet',
                'description'  => 'Macetero vertical de jardín fabricado con pallets',
                'unit_price'   => 15000.00,
            ],
        ]);

        DB::table('product_stock')->insert([
            ['id_article' => 1, 'remaining_quantity' => 8,  'minimum_stock' => 2],
            ['id_article' => 2, 'remaining_quantity' => 5,  'minimum_stock' => 2],
            ['id_article' => 3, 'remaining_quantity' => 12, 'minimum_stock' => 3],
            ['id_article' => 4, 'remaining_quantity' => 4,  'minimum_stock' => 1],
            ['id_article' => 5, 'remaining_quantity' => 10, 'minimum_stock' => 3],
            ['id_article' => 6, 'remaining_quantity' => 6,  'minimum_stock' => 2],
            ['id_article' => 7, 'remaining_quantity' => 3,  'minimum_stock' => 1],
            ['id_article' => 8, 'remaining_quantity' => 15, 'minimum_stock' => 5],
        ]);

        DB::table('article_materials')->insert([
            // Sillón individual
            ['id_article' => 1, 'id_raw_material' => 1, 'quantity_required' => 2],
            ['id_article' => 1, 'id_raw_material' => 2, 'quantity_required' => 12],
            ['id_article' => 1, 'id_raw_material' => 3, 'quantity_required' => 1],
            // Sillón doble
            ['id_article' => 2, 'id_raw_material' => 1, 'quantity_required' => 3],
            ['id_article' => 2, 'id_raw_material' => 2, 'quantity_required' => 20],
            ['id_article' => 2, 'id_raw_material' => 3, 'quantity_required' => 1],
            // Mesa ratona
            ['id_article' => 3, 'id_raw_material' => 1, 'quantity_required' => 1],
            ['id_article' => 3, 'id_raw_material' => 2, 'quantity_required' => 8],
            // Mesa comedor
            ['id_article' => 4, 'id_raw_material' => 1, 'quantity_required' => 4],
            ['id_article' => 4, 'id_raw_material' => 2, 'quantity_required' => 30],
            ['id_article' => 4, 'id_raw_material' => 3, 'quantity_required' => 2],
            // Estantería
            ['id_article' => 5, 'id_raw_material' => 1, 'quantity_required' => 1],
            ['id_article' => 5, 'id_raw_material' => 2, 'quantity_required' => 10],
            ['id_article' => 5, 'id_raw_material' => 4, 'quantity_required' => 4],
            // Mueble TV
            ['id_article' => 6, 'id_raw_material' => 1, 'quantity_required' => 2],
            ['id_article' => 6, 'id_raw_material' => 2, 'quantity_required' => 15],
            ['id_article' => 6, 'id_raw_material' => 4, 'quantity_required' => 6],
            // Cama 2 plazas
            ['id_article' => 7, 'id_raw_material' => 1, 'quantity_required' => 6],
            ['id_article' => 7, 'id_raw_material' => 2, 'quantity_required' => 40],
            ['id_article' => 7, 'id_raw_material' => 3, 'quantity_required' => 2],
            // Macetero
            ['id_article' => 8, 'id_raw_material' => 1, 'quantity_required' => 1],
            ['id_article' => 8, 'id_raw_material' => 2, 'quantity_required' => 6],
        ]);
    }
}