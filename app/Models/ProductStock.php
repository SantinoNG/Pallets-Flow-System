<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductStock extends Model
{
    protected $table = 'product_stock';
    protected $primaryKey = 'id_article';
    public $incrementing = false;
    protected $fillable = ['id_article', 'remaining_quantity', 'minimum_stock'];
 
    /**
     * La ruta debe usar la PK real de la tabla.
     * Sin esto, Route::modelBinding() buscaria por 'id',
     * columna que no existe en esta tabla.
     */
    public function getRouteKeyName(): string
    {
        return 'id_article';
    }

    public function article() {
        return $this->belongsTo(Article::class, 'id_article');
    }
}
