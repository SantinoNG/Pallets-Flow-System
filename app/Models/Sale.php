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
     * ANTES era un hasManyThrough con:
     *
     *     hasManyThrough(CartItem::class, Cart::class, 'id_cart', 'id_cart', 'id_sale', 'id_cart')
     *
     * y eso generaba `where cart.id_cart = ?` con el id_sale en el binding, en
     * vez del id_cart. Es decir: buscaba el carrito cuyo id_cart fuera igual
     * al id_sale de la venta.
     *
     * Parecia funcionar porque en los datos sembrados id_sale e id_cart
     * coinciden (el seeder crea las ventas y sus carritos en el mismo
     * orden). Pero en una venta real no coinciden: la venta 39 tiene el
     * carrito 40, y la relacion devolvia los items del carrito 39. Los
     * totales del listado de ventas salian mal.
     *
     * La relacion correcta es un hasMany directo: la venta tiene la
     * columna id_cart, y de ahi sale el id_cart de los items.
     */
    public function items()
    {
        return $this->hasMany(CartItem::class, 'id_cart', 'id_cart');
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
