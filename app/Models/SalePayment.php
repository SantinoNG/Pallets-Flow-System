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

    protected $fillable = ['id_sale', 'payment_method', 'amount'];

    protected $casts = [
        'amount' => 'float',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'id_sale');
    }
}
