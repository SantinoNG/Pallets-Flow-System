<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Ventas de ejemplo.
 *
 * Antes este seeder creaba UNA venta, sin fechas (created_at quedaba NULL) y
 * con un solo medio de pago. Eso dejaba al dashboard sin nada que mostrar:
 * los charts iban con numeros hardcodeados y los filtros por fecha no
 * tenian con que trabajar.
 *
 * Ahora se crean varias ventas repartidas en los ultimos meses, con distintos
 * medios de pago y distintos empleados, para que los agregaciones del
 * dashboard (ventas por mes, medios de pago, top productos) tengan datos
 * reales.
 *
 * Aparte, las referencias (cliente, empleados, articulos) se resuelven por
 * nombre y no por id fijo: los ids son auto_increment y no se puede asumir
 * que coincidan con los de los otros seeders.
 */
class SaleSeeder extends Seeder
{
    public function run(): void
    {
        // ------------------------------------------------------------------
        //  Referencias: se buscan por nombre, no por id
        // ------------------------------------------------------------------
        $idCustomer = DB::table('customers')
            ->join('users', 'users.id_user', '=', 'customers.id_user')
            ->where('users.email', 'cliente@gmail.com')
            ->value('customers.id_customer');

        if ($idCustomer === null) {
            throw new \RuntimeException(
                'No existe el cliente. Corré UserSeeder antes que SaleSeeder '
                . '(php artisan db:seed lo hace en orden).'
            );
        }

        $empleadoId = function (string $email): ?int {
            return DB::table('employees')
                ->join('users', 'users.id_user', '=', 'employees.id_user')
                ->where('users.email', $email)
                ->value('employees.id_employee');
        };

        $articuloId = function (string $nombre): int {
            $id = DB::table('articles')->where('article_name', $nombre)->value('id_article');

            if ($id === null) {
                throw new \RuntimeException(
                    "No existe el artículo '{$nombre}'. Corré ArticleSeeder antes que SaleSeeder."
                );
            }

            return (int) $id;
        };

        $vendedor = $empleadoId('vendedor@ecommerce.com');
        $deposito = $empleadoId('deposito@ecommerce.com');

        // ------------------------------------------------------------------
        //  Las ventas
        // ------------------------------------------------------------------
        $ventas = [
            [
                'hace'     => 5,
                'empleado' => $vendedor,
                'pago'     => 'Tarjeta de crédito',
                'items'    => [
                    ['articulo' => 'Sillón individual de pallet', 'quantity' => 2, 'unit_price' => 5500.00],
                    ['articulo' => 'Mesa ratona de pallet',      'quantity' => 1, 'unit_price' => 18500.00],
                ],
            ],
            [
                'hace'     => 38,
                'empleado' => $vendedor,
                'pago'     => 'Efectivo',
                'items'    => [
                    ['articulo' => 'Sillón doble de pallet',    'quantity' => 1, 'unit_price' => 27500.00],
                    ['articulo' => 'Mesa de comedor de pallet', 'quantity' => 1, 'unit_price' => 37500.00],
                ],
            ],
            [
                'hace'     => 72,
                'empleado' => $deposito,
                'pago'     => 'Transferencia',
                'items'    => [
                    ['articulo' => 'Estantería de pallet', 'quantity' => 3, 'unit_price' => 11000.00],
                ],
            ],
            [
                'hace'     => 104,
                'empleado' => $vendedor,
                'pago'     => 'Tarjeta de debito',
                'items'    => [
                    ['articulo' => 'Mueble TV de pallet', 'quantity' => 2, 'unit_price' => 21000.00],
                    ['articulo' => 'Macetero de pallet',  'quantity' => 4, 'unit_price' => 7500.00],
                ],
            ],
            [
                'hace'     => 131,
                'empleado' => $deposito,
                'pago'     => 'Efectivo',
                'items'    => [
                    ['articulo' => 'Cama de pallet 2 plazas', 'quantity' => 1, 'unit_price' => 45000.00],
                ],
            ],
            [
                'hace'     => 158,
                'empleado' => $vendedor,
                'pago'     => 'Transferencia',
                'items'    => [
                    ['articulo' => 'Mesa ratona de pallet',      'quantity' => 2, 'unit_price' => 18500.00],
                    ['articulo' => 'Mesa de comedor de pallet', 'quantity' => 1, 'unit_price' => 37500.00],
                ],
            ],
        ];

        // ------------------------------------------------------------------
        //  Se escriben
        // ------------------------------------------------------------------
        foreach ($ventas as $v) {
            $fecha = now()->subDays($v['hace']);

            // Carrito de la venta
            $idCart = DB::table('cart')->insertGetId([
                'id_customer' => $idCustomer,
                'status'      => 'completed',
                'created_at'  => $fecha,
                'updated_at'  => $fecha,
            ]);

            // Items del carrito
            foreach ($v['items'] as $item) {
                DB::table('cart_items')->insert([
                    'id_cart'    => $idCart,
                    'id_article' => $articuloId($item['articulo']),
                    'quantity'   => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'created_at' => $fecha,
                    'updated_at' => $fecha,
                ]);
            }

            // Venta
            $idVenta = DB::table('sales')->insertGetId([
                'id_cart'        => $idCart,
                'id_customer'    => $idCustomer,
                'id_employee'    => $v['empleado'],
                'payment_method' => $v['pago'],
                'created_at'     => $fecha,
                'updated_at'     => $fecha,
            ]);

            // Comprobante
            DB::table('receipts')->insert([
                'id_sale'    => $idVenta,
                'issue_date' => $fecha->toDateString(),
                'due_date'   => $fecha->copy()->addDays(30)->toDateString(),
            ]);
        }
    }
}
