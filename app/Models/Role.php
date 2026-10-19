<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    /**
     * @use HasFactory<\Database\Factories\RoleFactory>
     *
     * HasFactory es lo que habilita Role::factory(). Sin este trait el
     * metodo no existe y cualquier factory que lo use falla con:
     *
     *     Call to undefined method App\Models\Role::factory()
     *
     * (El commit del 17/10 que agrego RoleFactory se olvido de ponerlo.)
     */
    use HasFactory;

    protected $primaryKey = 'id_role';

    /**
     * La ruta debe usar la PK real de la tabla.
     * Sin esto, Route::modelBinding() buscaria por 'id',
     * columna que no existe en esta tabla.
     */
    public function getRouteKeyName(): string
    {
        return 'id_role';
    }

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
