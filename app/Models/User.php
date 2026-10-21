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
     * Este es el catalogo de roles de NEGOCIO del proyecto: define que roles
     * existen. Lo usan RoleSeeder, RoleController y employees.id_role.
     *
     * Spatie (HasRoles) lee la tabla `spatie_roles`, que es el ESPEJO del
     * catalogo para la capa de autorizacion (@hasrole, middleware role:).
     * config/permission.php los separa a proposito:
     *
     *     'roles' => 'spatie_roles',        // antes: 'roles'
     *
     * Los dos sistemas se mantienen sincronizados con asignarRol(), que
     * escribe en los dos lados de una sola vez.
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'id_role', 'id_role');
    }

    /**
     * Asigna un rol al usuario, en LOS DOS sistemas a la vez.
     *
     * - `users.id_role`  -> FK de negocio (un solo rol por usuario)
     * - `model_has_roles` -> capa Spatie (lo que leen @hasrole y el
     *   middleware role:), usada para decidir si alguien ve o no una seccion
     *
     * Hacerlo siempre por aca es lo que evita que se desincronicen: antes
     * `spatie_roles` estaba vacia, asi que @hasanyrole(...) devolvia false
     * para todo el mundo y el menu mostraba links que luego daban 403.
     *
     * @param  string  $nombreRol  Nombre del rol del catalogo (ej: 'Administrador')
     */
    public function asignarRol(string $nombreRol): void
    {
        $rol = Role::where('role_name', $nombreRol)->first();

        if ($rol === null) {
            throw new \InvalidArgumentException(
                "El rol '{$nombreRol}' no existe en el catalogo de roles."
            );
        }

        // id_role NO esta en $fillable a proposito (para que nadie se lo
        // asigne con un mass assignment), asi que va con forceFill.
        $this->forceFill(['id_role' => $rol->id_role])->save();

        // Espejo en Spatie. findOrCreate se asegura de que el rol exista en
        // `spatie_roles` con el guard 'web' (asi funciona aunque se haya
        // creado un rol nuevo sin volver a correr el RoleSeeder) y despues
        // syncRoles() deja al usuario con ese rol y solo ese.
        \Spatie\Permission\Models\Role::findOrCreate($nombreRol, 'web');

        $this->syncRoles([$nombreRol]);
    }

    /**
     * Roles que pueden administrar usuarios y roles (Jefe o Administrador).
     *
     * Se consulta por Spatie, que es la capa que usan las directivas de
     * Blade y el middleware, para que haya una sola fuente de verdad.
     */
    public function isAdmin(): bool
    {
        return $this->hasAnyRole(['Jefe', 'Administrador']);
    }

    /**
     * El usuario autenticado, como Cliente.
     *
     * Faltaba: no habia forma directa de obtener el cliente del usuario
     * logueado, que es lo que necesita el modulo de carrito.
     */
    public function customer()
    {
        return $this->hasOne(Customer::class, 'id_user');
    }

    /**
     * Id del rol 'Cliente' en el catalogo de roles.
     *
     * Se consulta por nombre y no por id fijo, porque los ids se autogeneran
     * y cambian al sembrar la base.
     */
    public static function idRolCliente(): ?int
    {
        $id = Role::where('role_name', 'Cliente')->value('id_role');

        return $id === null ? null : (int) $id;
    }

    /**
     * Usuarios que son CLIENTES: su rol es 'Cliente'.
     *
     * El criterio es el ROL y no "tiene ficha de cliente". Son dos cosas
     * distintas: un usuario puede tener ficha y ser del personal (le
     * compro a la empresa y despues trabajo en ella), y al reves, un
     * cliente recien creado todavia no tiene ficha cargada.
     */
    public function scopeEsCliente($query)
    {
        return $query->where('id_role', self::idRolCliente());
    }

    /**
     * Usuarios que son PERSONAL: su rol NO es 'Cliente'.
     *
     * Incluye a los que todavia no son empleados, porque el rol de
     * personal es lo que los habilita a entrar al mostrador y al deposito.
     */
    public function scopeEsPersonal($query)
    {
        $idCliente = self::idRolCliente();

        return $query->where(function ($q) use ($idCliente) {
            $q->whereNull('id_role');

            if ($idCliente !== null) {
                $q->orWhere('id_role', '!=', $idCliente);
            }
        });
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
