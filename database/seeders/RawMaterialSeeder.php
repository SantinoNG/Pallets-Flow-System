<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Materias primas del taller de pallets.
 *
 * Cada insumo tiene su UNIDAD (metro, bolsa, caja, litro, plancha, unidad)
 * y su COSTO por unidad, para poder calcular cuanto material lleva cada
 * producto y cuanto cuesta armarlo.
 *
 * Es idempotente: actualiza por descripcion. Los insumos que ya no estan en
 * el catalogo (por ejemplo, los textiles viejos) se borran junto con su
 * stock y su receta, asi una base ya sembrada queda igual que una nueva.
 */
class RawMaterialSeeder extends Seeder
{
    public function run(): void
    {
        $proveedores = DB::table('suppliers')->pluck('id_supplier', 'company_name');

        /*
         * Catalogo. "stock" y "min" son las cantidades de arranque para la
         * demo; en unidades de la medida de cada insumo.
         */
        $materiales = [
            ['description' => 'Tabla de pallet reciclada',               'unit' => 'unidad',  'unit_cost' => 1500, 'stock' => 120, 'min' => 30, 'supplier' => 'Distribuidora Norte S.A.'],
            ['description' => 'Madera de pino 2x4',                      'unit' => 'metro',   'unit_cost' => 1200, 'stock' => 350, 'min' => 80, 'supplier' => 'Maderas del Litoral S.A.'],
            ['description' => 'Madera de pino 1x4',                      'unit' => 'metro',   'unit_cost' => 800,  'stock' => 240, 'min' => 60, 'supplier' => 'Materiales del Sur'],
            ['description' => 'Listón de madera 1x2',                    'unit' => 'metro',   'unit_cost' => 600,  'stock' => 180, 'min' => 50, 'supplier' => 'Aserradero San José'],
            ['description' => 'Clavos para pallet (bolsa 1 kg)',         'unit' => 'bolsa',   'unit_cost' => 1800, 'stock' => 80,  'min' => 20, 'supplier' => 'Clavos y Tachuelas SRL'],
            ['description' => 'Tachuelas (bolsa 500 g)',                 'unit' => 'bolsa',   'unit_cost' => 1200, 'stock' => 45,  'min' => 15, 'supplier' => 'Clavos y Tachuelas SRL'],
            ['description' => 'Tornillos autoperforantes (caja x100)',   'unit' => 'caja',    'unit_cost' => 3500, 'stock' => 35,  'min' => 10, 'supplier' => 'Ferretería Industrial Tornillos del Norte'],
            ['description' => 'Lija para madera (pliego)',               'unit' => 'pliego',  'unit_cost' => 600,  'stock' => 150, 'min' => 40, 'supplier' => 'Ferretería Industrial Tornillos del Norte'],
            ['description' => 'Barniz poliuretánico',                    'unit' => 'litro',   'unit_cost' => 5200, 'stock' => 60,  'min' => 15, 'supplier' => 'Pinturas y Barnices Delta'],
            ['description' => 'Sellador para madera',                    'unit' => 'litro',   'unit_cost' => 4200, 'stock' => 40,  'min' => 10, 'supplier' => 'Pinturas y Barnices Delta'],
            ['description' => 'Cola de carpintero',                      'unit' => 'litro',   'unit_cost' => 3000, 'stock' => 25,  'min' => 8,  'supplier' => 'Pinturas y Barnices Delta'],
            ['description' => 'Tela de tapicería',                       'unit' => 'metro',   'unit_cost' => 3800, 'stock' => 90,  'min' => 25, 'supplier' => 'Textiles Andinos'],
            ['description' => 'Hilo de coser',                           'unit' => 'rollo',   'unit_cost' => 900,  'stock' => 50,  'min' => 12, 'supplier' => 'Textiles Andinos'],
            ['description' => 'Espuma de alta densidad',                 'unit' => 'plancha', 'unit_cost' => 6500, 'stock' => 30,  'min' => 8,  'supplier' => 'Espumas y Rellenos NOA'],
            ['description' => 'Bisagras metálicas',                      'unit' => 'unidad',  'unit_cost' => 900,  'stock' => 100, 'min' => 25, 'supplier' => 'Herrajes y Accesorios Centro'],
            ['description' => 'Escuadras metálicas',                     'unit' => 'unidad',  'unit_cost' => 600,  'stock' => 120, 'min' => 30, 'supplier' => 'Herrajes y Accesorios Centro'],
        ];

        foreach ($materiales as $material) {
            $idProveedor = $proveedores[$material['supplier']] ?? null;

            if (! $idProveedor) {
                continue; // SupplierSeeder tiene que correr antes
            }

            DB::table('raw_materials')->updateOrInsert(
                ['description' => $material['description']],
                [
                    'id_supplier' => $idProveedor,
                    'unit'        => $material['unit'],
                    'unit_cost'   => $material['unit_cost'],
                    'updated_at'  => now(),
                    'created_at'  => now(),
                ]
            );
        }

        $ids = DB::table('raw_materials')
            ->whereIn('description', array_column($materiales, 'description'))
            ->pluck('id_raw_material')
            ->all();

        /*
         * Los insumos que ya no estan en el catalogo se van con su stock y
         * sus renglones de receta. Sin esto, una base vieja se quedaria con
         * las telas y botones de la primera version.
         */
        DB::table('article_materials')->whereNotIn('id_raw_material', $ids)->delete();
        DB::table('raw_material_stock')->whereNotIn('id_raw_material', $ids)->delete();
        DB::table('raw_materials')->whereNotIn('id_raw_material', $ids)->delete();

        foreach ($materiales as $material) {
            $id = DB::table('raw_materials')
                ->where('description', $material['description'])
                ->value('id_raw_material');

            DB::table('raw_material_stock')->updateOrInsert(
                ['id_raw_material' => $id],
                [
                    'remaining_quantity' => $material['stock'],
                    'minimum_stock'      => $material['min'],
                    'updated_at'         => now(),
                    'created_at'         => now(),
                ]
            );
        }
    }
}
