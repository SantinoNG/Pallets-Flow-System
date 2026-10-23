<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Un pago de una venta.
 *
 * Una venta puede tener varios pagos (pago combinado). Cada fila es un
 * medio con su importe. La suma de los importes es el total de la venta.
 */
class SalePayment extends Model
{
    protected $primaryKey = 'id_payment';

    protected $fillable = ['id_sale', 'payment_method', 'amount', 'installments'];

    protected $casts = [
        'amount'       => 'float',
        'installments' => 'integer',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'id_sale');
    }

    /**
     * Texto para mostrar el pago.
     *
     * Con tarjeta de credito en cuotas: "Tarjeta de crédito (6 cuotas de
     * $18,333.33)". El importe guardado es el total; lo que se divide es
     * solo para mostrar cuanto sale cada cuota.
     */
    public function etiqueta(): string
    {
        if ($this->installments > 1) {
            $porCuota = $this->amount / $this->installments;

            return $this->payment_method . ' (' . $this->installments . ' cuotas de $'
                . number_format($porCuota, 2) . ')';
        }

        return $this->payment_method;
    }

    /**
     * ¿Se pago en cuotas?
     */
    public function enCuotas(): bool
    {
        return $this->installments > 1;
    }
}
