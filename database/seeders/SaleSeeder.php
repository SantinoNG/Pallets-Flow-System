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
        //  Ventas de los clientes que agregado CustomerSeeder
        // ------------------------------------------------------------------
        /*
         * Cada cliente nuevo hace TRES compras, separadas y repartidas en
         * los ultimos cuatro meses (120 dias). Las fechas se calculan con
         * un indice fijo, no al azar: la siembra siempre da el mismo
         * resultado y el dashboard se puede comparar entre corridas.
         *
         * Con los 10 clientes nuevos son 30 compras mas, que se suman a las
         * 6 de Ana Torres. Todas dentro de la ventana que usa el filtro
         * "6 meses" del dashboard, asi que el grafico de ventas por mes
         * muestra movimiento real y no una sola barra.
         */
        $clientesNuevos = DB::table('customers')
            ->join('users', 'users.id_user', '=', 'customers.id_user')
            ->where('users.email', '!=', 'cliente@gmail.com')
            ->orderBy('customers.id_customer')
            ->get([
                'customers.id_customer',
                'customers.first_name',
                'customers.last_name',
                'users.email',
            ]);

        $mediosDePago = ['Efectivo', 'Tarjeta de crédito', 'Transferencia', 'Tarjeta de débito'];

        // Los artículos, con su precio actual del catálogo.
        $articulos = DB::table('articles')
            ->orderBy('id_article')
            ->get(['id_article', 'article_name', 'unit_price']);

        $i = 0;

        foreach ($clientesNuevos as $cliente) {
            // Tres compras por cliente, a 37 dias de distancia una de otra.
            $base = 2 + $i * 4;

            foreach ([$base, $base + 37, $base + 74] as $n => $hace) {
                // Cada compra lleva entre 1 y 3 artículos del catálogo.
                $cuantos = 1 + (($i + $n) % 3);
                $items = [];

                for ($k = 0; $k < $cuantos; $k++) {
                    // Se va recorriendo el catálogo, empezando en un punto
                    // distinto para cada cliente, para que no todos
                    // compren siempre lo mismo.
                    $posicion = ($i * 3 + $n * 2 + $k) % $articulos->count();
                    $articulo = $articulos[$posicion];

                    $items[] = [
                        'id_article' => $articulo->id_article,
                        'quantity'   => 1 + (($i + $n + $k) % 3),
                        'unit_price' => $articulo->unit_price,
                    ];
                }

                $this->escribirVenta(
                    idCustomer: (int) $cliente->id_customer,
                    idEmpleado: ($i + $n) % 2 === 0 ? $vendedor : $deposito,
                    pago: $mediosDePago[($i + $n) % count($mediosDePago)],
                    items: $items,
                    fecha: now()->subDays($hace),
                );
            }

            $i++;
        }

        // ------------------------------------------------------------------
        //  Se escriben
        // ------------------------------------------------------------------
        foreach ($ventas as $v) {
            $this->escribirVenta(
                idCustomer: $idCustomer,
                idEmpleado: $v['empleado'],
                pago: $v['pago'],
                items: array_map(fn ($item) => [
                    'id_article' => $articuloId($item['articulo']),
                    'quantity'   => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ], $v['items']),
                fecha: now()->subDays($v['hace']),
            );
        }
    }

    /**
     * Escribe una venta completa: carrito, items, venta y comprobante.
     *
     * Se separo en un metodo porque ahora las ventas se generan de dos
     * maneras (la lista fija de Ana Torres y las de los clientes nuevos)
     * y las dos terminan igual.
     */
    private function escribirVenta(
        int $idCustomer,
        ?int $idEmpleado,
        string $pago,
        array $items,
        \Illuminate\Support\Carbon $fecha
    ): void {
        // Carrito de la venta
        $idCart = DB::table('cart')->insertGetId([
            'id_customer' => $idCustomer,
            'status'      => 'completed',
            'created_at'  => $fecha,
            'updated_at'  => $fecha,
        ]);

        // Items del carrito
        foreach ($items as $item) {
            DB::table('cart_items')->insert([
                'id_cart'    => $idCart,
                'id_article' => $item['id_article'],
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
            'id_employee'    => $idEmpleado,
            'payment_method' => $pago,
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
