<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Datos fiscales del comprobante.
 *
 * Se agregan tres cosas que el comprobante no tenia:
 *
 *  1. Numero de comprobante correlativo POR CLIENTE. La primera compra de un
 *     cliente es la 1, la segunda la 2, y asi. No es el id de la venta: el
 *     id es interno y no dice nada. El numero fiscal es el que se ve.
 *
 *  2. Punto de venta, como en los comprobantes argentinos ("0001").
 *
 *  3. Desglose de IVA: neto + IVA = total. Los precios del catalogo ya
 *     incluyen IVA (como en la calle), asi que el neto se calcula hacia
 *     atras y el IVA sale por diferencia.
 *
 * El desglose se GUARDA y no se calcula al mostrar: si mañana cambia una
 * alicuota o un precio, el comprobante ya emitido no puede cambiar. Es un
 * documento, no una vista.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->unsignedInteger('receipt_number')->nullable()->after('id_sale');
            $table->string('point_of_sale', 4)->default('0001')->after('receipt_number');
            $table->decimal('tax_rate', 5, 2)->default(21.00)->after('due_date');
            $table->decimal('net_amount', 12, 2)->nullable()->after('tax_rate');
            $table->decimal('tax_amount', 12, 2)->nullable()->after('net_amount');
            $table->decimal('total_amount', 12, 2)->nullable()->after('tax_amount');
        });

        $this->numerarComprobantesExistentes();
    }

    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropColumn([
                'receipt_number',
                'point_of_sale',
                'tax_rate',
                'net_amount',
                'tax_amount',
                'total_amount',
            ]);
        });
    }

    /**
     * Numera y desglosa los comprobantes que ya existen.
     *
     * Se hace con el query builder y no con los modelos a proposito: una
     * migracion no puede depender de codigo de la aplicacion, que cambia
     * con el tiempo. El dia que se corra esta migracion en otra base, los
     * modelos van a ser otros.
     *
     * En una base recien creada no hay nada que numerar: el seeder se
     * encarga de dejar los datos completos.
     */
    private function numerarComprobantesExistentes(): void
    {
        $ventas = DB::table('sales')
            ->orderBy('id_customer')
            ->orderBy('id_sale')
            ->get(['id_sale', 'id_customer', 'id_cart', 'payment_method']);

        if ($ventas->isEmpty()) {
            return;
        }

        // Correlativo por cliente, en memoria: la consulta ya viene ordenada.
        $correlativo = [];

        foreach ($ventas as $venta) {
            $idCliente = $venta->id_customer;
            $correlativo[$idCliente] = ($correlativo[$idCliente] ?? 0) + 1;

            $total = (float) DB::table('cart_items')
                ->where('id_cart', $venta->id_cart)
                ->sum(DB::raw('quantity * unit_price'));

            $neto = round($total / 1.21, 2);

            DB::table('receipts')
                ->where('id_sale', $venta->id_sale)
                ->update([
                    'receipt_number' => $correlativo[$idCliente],
                    'point_of_sale'  => '0001',
                    'tax_rate'       => 21.00,
                    'net_amount'     => $neto,
                    'tax_amount'     => round($total - $neto, 2),
                    'total_amount'   => round($total, 2),
                ]);

            // El detalle de pagos: las ventas viejas tenian un solo medio.
            // Se crea la fila para que el comprobante pueda listar pagos
            // sin preguntarse si la venta es vieja o nueva.
            $yaTienePagos = DB::table('sale_payments')
                ->where('id_sale', $venta->id_sale)
                ->exists();

            if (! $yaTienePagos) {
                DB::table('sale_payments')->insert([
                    'id_sale'        => $venta->id_sale,
                    'payment_method' => $venta->payment_method,
                    'amount'         => round($total, 2),
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        }
    }
};
