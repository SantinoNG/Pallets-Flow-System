<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $primaryKey = 'id_sale';

    /**
     * La ruta debe usar la PK real de la tabla.
     * Sin esto, Route::modelBinding() buscaria por 'id',
     * columna que no existe en estas tablas.
     */
    public function getRouteKeyName(): string
    {
        return 'id_sale';
    }
    protected $fillable = ['id_cart', 'id_customer', 'id_employee', 'payment_method'];

    public function cart() {
        return $this->belongsTo(Cart::class, 'id_cart');
    }
    public function customer() {
        return $this->belongsTo(Customer::class, 'id_customer');
    }
    public function employee() {
        return $this->belongsTo(Employee::class, 'id_employee');
    }
    public function receipt() {
        return $this->hasOne(Receipt::class, 'id_sale');
    }

    /**
     * Items del carrito de esta venta.
     *
     * Atajo a cart.items, que es donde estan los articulos vendidos.
     *
     * Es un hasManyThrough con: related = cart_items, through = cart.
     *   - firstKey        = id_cart   (FK de cart hacia sales)
     *   - secondKey       = id_cart   (FK de cart_items hacia cart)
     *   - localKey        = id_sale   (PK de sales)
     *   - secondLocalKey  = id_cart   (PK de cart)
     */
    public function items()
    {
        return $this->hasManyThrough(
            CartItem::class,
            Cart::class,
            'id_cart',
            'id_cart',
            'id_sale',
            'id_cart'
        );
    }

    /**
     * Monto total de la venta.
     *
     * La tabla `sales` no tiene columna de monto: sale de los items del
     * carrito (quantity * unit_price).
     */
    public function total(): float
    {
        return (float) $this->items->sum(fn ($item) => $item->quantity * $item->unit_price);
    }

    /**
     * Cantidad total de articulos de la venta.
     */
    public function unidades(): int
    {
        return (int) $this->items->sum('quantity');
    }
}
