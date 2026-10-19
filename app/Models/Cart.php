<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $primaryKey = 'id_cart';

    /**
     * La ruta debe usar la PK real de la tabla.
     * Sin esto, Route::modelBinding() buscaria por 'id',
     * columna que no existe en estas tablas.
     */
    public function getRouteKeyName(): string
    {
        return 'id_cart';
    }
    protected $fillable = ['id_customer', 'status'];
 
    public function customer() {
        return $this->belongsTo(Customer::class, 'id_customer');
    }
    public function items() {
        return $this->hasMany(CartItem::class, 'id_cart');
    }
    public function sale() {
        return $this->hasOne(Sale::class, 'id_cart');
    }
}
