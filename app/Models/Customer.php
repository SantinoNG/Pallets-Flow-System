<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $primaryKey = 'id_customer';
    protected $fillable = ['id_user', 'first_name', 'last_name', 'address', 'phone'];
 
    public function user() {
        return $this->belongsTo(User::class, 'id_user');
    }
    public function carts() {
        return $this->hasMany(Cart::class, 'id_customer');
    }
    public function sales() {
        return $this->hasMany(Sale::class, 'id_customer');
    }
}
