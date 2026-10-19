<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Spatie\Permission\Traits\HasRoles;
use OwenIt\Auditing\Contracts\Auditable;
use App\Models\Employee;

class User extends Authenticatable implements Auditable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;
    use \OwenIt\Auditing\Auditable;

    protected $primaryKey = 'id_user';
    public $rememberTokenName = null;

    /**
     * La ruta debe usar la PK real de la tabla.
     * Sin esto, Route::modelBinding() buscaria por 'id',
     * columna que no existe en la tabla users.
     */
    public function getRouteKeyName(): string
    {
        return 'id_user';
    }

    /**
     * Alias de `id` -> `id_user`.
     *
     * Laravel da por hecho que el atributo de la clave primaria se llama `id`.
     * En este proyecto la PK real es `id_user`, asi que sin este accessor
     * cosas del framework fallan con "Missing parameter: [id]", por ejemplo
     * las URLs de verificacion de email que genera Breeze:
     *
     *     URL::temporarySignedRoute('verification.verify', ..., ['id' => $user->id])
     *
     * Al leer `id` devuelve el mismo valor que `id_user`, que es lo que
     * espera el framework. No se agrega a $appends, asi que no cambia la
     * serializacion del modelo.
     */
    public function getIdAttribute(): int
    {
        return (int) $this->getKey();
    }

    public function employee()
{
    return $this->hasOne(Employee::class, 'id_user');
}

    /**
     * Rol propio del proyecto (tabla `roles`, PK `id_role`, columna `role_name`).
     *
     * OJO: este es el sistema de roles REAL del proyecto, el que usan
     * RoleSeeder, RoleController y employees.id_role.
     *
     * Spatie (HasRoles) tambien esta instalado y lee la tabla `spatie_roles`,
     * que esta vacia. Los dos sistemas conviven pero NO son lo mismo:
     * users.id_role es una FK simple (un solo rol por usuario), mientras que
     * Spatie usa una tabla puente y permite varios roles.
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'id_role', 'id_role');
    }

    /**
     * Un usuario es administrador si su rol (propio) es "Administrador".
     * Se usa para proteger /usuarios y /roles.
     */
    public function isAdmin(): bool
    {
        return $this->role?->role_name === 'Administrador';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
