<?php

namespace App\Models;

use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $primaryKey = 'id_employee';
    protected $fillable = ['id_user', 'id_role', 'first_name', 'last_name', 'phone'];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'id_role');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'id_employee');
    }
}
