<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Detalle de los pagos de una venta.
 *
 * Antes cada venta tenia UN medio de pago: `sales.payment_method`, un texto
 * suelto. Con el pago combinado una venta puede pagarse con varios medios a
 * la vez (parte en efectivo, parte con transferencia), asi que hace falta una
 * fila por medio con su importe.
 *
 * `sales.payment_method` se conserva como RESUMEN ("Efectivo" o "Pago
 * combinado (Efectivo + Transferencia)"), para el listado y los filtros.
 * El detalle con importes vive aca.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id('id_payment');
            $table->foreignId('id_sale')->constrained('sales', 'id_sale')->cascadeOnDelete();
            $table->string('payment_method');
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            // El comprobante y los totales leen los pagos por venta.
            $table->index('id_sale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
    }
};
