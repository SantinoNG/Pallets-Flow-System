<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $primaryKey = 'id_supplier';

    /**
     * La ruta debe usar la PK real de la tabla.
     * Sin esto, Route::modelBinding() buscaria por 'id',
     * columna que no existe en estas tablas.
     */
    public function getRouteKeyName(): string
    {
        return 'id_supplier';
    }
    protected $fillable = ['company_name', 'supplier_email', 'supplier_phone'];
 
    public function rawMaterials() {
        return $this->hasMany(RawMaterial::class, 'id_supplier');
    }
}
