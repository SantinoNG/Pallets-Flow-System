<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    protected $primaryKey = 'id_receipt';
    protected $fillable = ['id_sale', 'issue_date', 'due_date'];
 
    public function sale() {
        return $this->belongsTo(Sale::class, 'id_sale');
    }
}
