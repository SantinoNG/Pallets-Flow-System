<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $primaryKey = 'id_role';
    protected $fillable = ['role_name', 'description'];

    public function users()
    {
        return $this->hasMany(User::class, 'id_role');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'id_role');
    }
}
