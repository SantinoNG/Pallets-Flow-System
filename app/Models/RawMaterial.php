<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RawMaterial extends Model
{
    protected $primaryKey = 'id_raw_material';
    protected $fillable = ['description', 'id_supplier'];
 
    public function supplier() {
        return $this->belongsTo(Supplier::class, 'id_supplier');
    }
    public function stock() {
        return $this->hasOne(RawMaterialStock::class, 'id_raw_material');
    }
    public function articles() {
        return $this->belongsToMany(Article::class, 'article_materials', 'id_raw_material', 'id_article')
                    ->withPivot('quantity_required');
    }
}
