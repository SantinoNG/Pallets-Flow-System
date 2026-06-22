<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
 
class RawMaterialSeeder extends Seeder {
    public function run(): void {
        DB::table('raw_materials')->insert([
            ['description' => 'Tela algodón 100%',  'id_supplier' => 1],
            ['description' => 'Hilo de costura',    'id_supplier' => 1],
            ['description' => 'Botones plásticos',  'id_supplier' => 2],
            ['description' => 'Cierre metálico',    'id_supplier' => 2],
        ]);
 
        DB::table('raw_material_stock')->insert([
            ['id_raw_material' => 1, 'remaining_quantity' => 200, 'minimum_stock' => 50],
            ['id_raw_material' => 2, 'remaining_quantity' => 500, 'minimum_stock' => 100],
            ['id_raw_material' => 3, 'remaining_quantity' => 300, 'minimum_stock' => 80],
            ['id_raw_material' => 4, 'remaining_quantity' => 150, 'minimum_stock' => 40],
        ]);
    }
}
