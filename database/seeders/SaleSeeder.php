<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
 
class SaleSeeder extends Seeder {
    public function run(): void {
        // Carrito del cliente 1
        DB::table('cart')->insert([
            ['id_customer' => 1, 'status' => 'completed'],
        ]);
 
        DB::table('cart_items')->insert([
            ['id_cart' => 1, 'id_article' => 1, 'quantity' => 2, 'unit_price' => 5500.00],
            ['id_cart' => 1, 'id_article' => 3, 'quantity' => 1, 'unit_price' => 18500.00],
        ]);
 
        // Venta generada desde el carrito
        DB::table('sales')->insert([
            [
                'id_cart'        => 1,
                'id_customer'    => 1,
                'id_employee'    => 2, // vendedor que procesó
                'payment_method' => 'Tarjeta de crédito',
            ],
        ]);
 
        // Comprobante de la venta
        DB::table('receipts')->insert([
            [
                'id_sale'    => 1,
                'issue_date' => now()->toDateString(),
                'due_date'   => now()->addDays(30)->toDateString(),
            ],
        ]);
    }
}
