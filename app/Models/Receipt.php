<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    protected $fillable = ['id_sale', 'issue_date', 'due_date'];
 
    public function sale() {
        return $this->belongsTo(Sale::class, 'id_sale');
    }
}
