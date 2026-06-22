<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductStock extends Model
{
    protected $table = 'product_stock';
    protected $primaryKey = 'id_article';
    public $incrementing = false;
    protected $fillable = ['id_article', 'remaining_quantity', 'minimum_stock'];
 
    public function article() {
        return $this->belongsTo(Article::class, 'id_article');
    }
}
