<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $primaryKey = 'id_article';
    protected $fillable = ['article_name', 'description', 'unit_price'];
 
    public function stock() {
        return $this->hasOne(ProductStock::class, 'id_article');
    }
    public function rawMaterials() {
        return $this->belongsToMany(RawMaterial::class, 'article_materials', 'id_article', 'id_raw_material')
                    ->withPivot('quantity_required');
    }
    public function cartItems() {
        return $this->hasMany(CartItem::class, 'id_article');
    }
}
