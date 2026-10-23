<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cuotas de la tarjeta de credito.
 *
 * El importe del pago sigue siendo el TOTAL cobrado con ese medio: las
 * cuotas son la forma de pago ("6 cuotas de $18.333,33"), no otro importe.
 * Se guardan para que el comprobante lo diga sin tener que adivinarlo
 * despues, y para poder listar cuantas cuotas se ofrecieron.
 *
 * En los medios que no son tarjeta de credito queda en 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_payments', function (Blueprint $table) {
            $table->unsignedTinyInteger('installments')->default(1)->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('sale_payments', function (Blueprint $table) {
            $table->dropColumn('installments');
        });
    }
};
