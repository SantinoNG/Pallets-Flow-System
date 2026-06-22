<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $primaryKey = 'id_supplier';
    protected $fillable = ['company_name', 'supplier_email', 'supplier_phone'];
 
    public function rawMaterials() {
        return $this->hasMany(RawMaterial::class, 'id_supplier');
    }
}
