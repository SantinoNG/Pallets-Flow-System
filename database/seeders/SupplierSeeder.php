<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
 
class SupplierSeeder extends Seeder {
    public function run(): void {
        DB::table('suppliers')->insert([
            [
                'company_name'   => 'Distribuidora Norte S.A.',
                'supplier_email' => 'contacto@dnorte.com',
                'supplier_phone' => '3764-111222',
            ],
            [
                'company_name'   => 'Materiales del Sur',
                'supplier_email' => 'ventas@matsur.com',
                'supplier_phone' => '3764-333444',
            ],
        ]);
    }
}