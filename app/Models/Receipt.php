<?php

namespace App\Models;

use App\Support\Iva;
use Illuminate\Database\Eloquent\Model;

/**
 * Comprobante de una venta.
 *
 * Guarda los datos fiscales del momento de la emision (numero, punto de
 * venta y desglose de IVA). No se recalculan al mostrarlos: un comprobante
 * emitido no cambia aunque despues cambien los precios.
 */
class Receipt extends Model
{
    protected $primaryKey = 'id_receipt';

    /**
     * La ruta debe usar la PK real de la tabla.
     * Sin esto, Route::modelBinding() buscaria por 'id',
     * columna que no existe en estas tablas.
     */
    public function getRouteKeyName(): string
    {
        return 'id_receipt';
    }

    protected $fillable = [
        'id_sale',
        'receipt_number',
        'point_of_sale',
        'issue_date',
        'due_date',
        'tax_rate',
        'net_amount',
        'tax_amount',
        'total_amount',
    ];

    protected $casts = [
        'issue_date'     => 'date',
        'due_date'       => 'date',
        'receipt_number' => 'integer',
        'tax_rate'       => 'float',
        'net_amount'     => 'float',
        'tax_amount'     => 'float',
        'total_amount'   => 'float',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'id_sale');
    }

    /**
     * Numero completo, como se escribe en Argentina:
     *
     *     0001-00000003
     *     ^^^^ ^^^^^^^^
     *     |    +-- correlativo del cliente (su 3ra compra)
     *     +------- punto de venta
     */
    public function numeroComprobante(): string
    {
        $puntoVenta = $this->point_of_sale ?: '0001';

        return $puntoVenta . '-' . str_pad((string) ($this->receipt_number ?? 0), 8, '0', STR_PAD_LEFT);
    }

    /**
     * Etiqueta lista para mostrar en pantalla o en el PDF.
     */
    public function etiqueta(): string
    {
        return 'Comprobante N° ' . $this->numeroComprobante();
    }

    /**
     * Neto, IVA y total del comprobante.
     *
     * Si el comprobante es viejo y no tiene el desglose guardado, se
     * calcula al vuelo desde el total de la venta. Es solo una red de
     * seguridad: los comprobantes nuevos siempre lo traen guardado.
     *
     * @return array{tasa: float, neto: float, iva: float, total: float}
     */
    public function desglose(): array
    {
        if ($this->total_amount !== null) {
            return [
                'tasa'  => (float) ($this->tax_rate ?? Iva::TASA_GENERAL),
                'neto'  => (float) $this->net_amount,
                'iva'   => (float) $this->tax_amount,
                'total' => (float) $this->total_amount,
            ];
        }

        return Iva::desglosar($this->totalDeLaVenta(), (float) ($this->tax_rate ?? Iva::TASA_GENERAL));
    }

    /**
     * Total de la venta segun los items del carrito.
     */
    private function totalDeLaVenta(): float
    {
        $this->loadMissing('sale.cart.items');

        return (float) $this->sale->cart->items
            ->sum(fn ($item) => $item->unit_price * $item->quantity);
    }
}
