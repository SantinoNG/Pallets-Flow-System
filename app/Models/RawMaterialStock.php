<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RawMaterialStock extends Model
{
      protected $table = 'raw_material_stock';
    protected $primaryKey = 'id_raw_material';
    public $incrementing = false;
    protected $fillable = ['id_raw_material', 'remaining_quantity', 'minimum_stock'];
 
    public function rawMaterial() {
        return $this->belongsTo(RawMaterial::class, 'id_raw_material');
    }
}
