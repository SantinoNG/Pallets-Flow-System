<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $primaryKey = 'id_cart_item';
    protected $fillable = ['id_cart', 'id_article', 'quantity', 'unit_price'];
 
    public function cart() {
        return $this->belongsTo(Cart::class, 'id_cart');
    }
    public function article() {
        return $this->belongsTo(Article::class, 'id_article');
    }
}
