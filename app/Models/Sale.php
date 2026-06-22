<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $primaryKey = 'id_sale';
    protected $fillable = ['id_cart', 'id_customer', 'id_employee', 'payment_method'];
 
    public function cart() {
        return $this->belongsTo(Cart::class, 'id_cart');
    }
    public function customer() {
        return $this->belongsTo(Customer::class, 'id_customer');
    }
    public function employee() {
        return $this->belongsTo(Employee::class, 'id_employee');
    }
    public function receipt() {
        return $this->hasOne(Receipt::class, 'id_sale');
    }
}
