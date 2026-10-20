<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role as SpatieRole;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * Catalogo de roles del proyecto.

         * Los 4 originales (Jefe, Administrador, Encargado, Cliente) se
         * respetan tal cual. Se AGREGAN "Vendedor" y "Depósito" porque
         * UserSeeder necesita un rol para el usuario vendedor@ y otro para
         * deposito@, y antes no existian: el seeder les ponia id_role=2 y
         * id_role=3 "a ojo", lo que hacia que:
         *
         *   admin@ecommerce.com     -> Jefe            (no Administrador)
         *   vendedor@ecommerce.com  -> Administrador   <-- escalada de privilegios
         *   deposito@ecommerce.com  -> Encargado       (no Depósito)
         *
         * Los ids se generan por auto_increment, por lo que el orden de
         * insercion ya no importa: UserSeeder busca los roles por nombre.
         */
        $roles = [
            ['role_name' => 'Jefe',          'description' => 'Gestión total del sistema'],
            ['role_name' => 'Administrador', 'description' => 'Gestión de inventario y proveedores'],
            ['role_name' => 'Encargado',     'description' => 'Registros de clientes y ventas'],
            ['role_name' => 'Cliente',       'description' => 'Acceso a compras y seguimiento de pedidos'],
            ['role_name' => 'Vendedor',      'description' => 'Registro y gestión de ventas'],
            ['role_name' => 'Depósito',      'description' => 'Ingreso y control de mercadería'],
        ];

        foreach ($roles as $rol) {
            // updateOrInsert evita duplicados si se corre db:seed mas de una vez.
            // OJO: firstOrCreate() es metodo de Eloquent, no del query builder
            // de DB::table(), por eso se usa updateOrInsert.
            DB::table('roles')->updateOrInsert(
                ['role_name' => $rol['role_name']],
                ['description' => $rol['description']]
            );
        }

        /*
         * Espejo del catalogo en `spatie_roles`.
         *
         * config/permission.php separa a proposito las dos tablas:
         *
         *     'roles' => 'spatie_roles',        // antes: 'roles'
         *
         * - `roles`       -> catalogo de negocio (id_role, role_name)
         * - `spatie_roles` -> capa de autorizacion (name, guard_name)
         *
         * Spatie NO puede leer la tabla `roles` porque espera una columna
         * `name` y la custom se llama `role_name`. Por eso son dos tablas.
         *
         * Este bucle copia el catalogo a `spatie_roles` para que las
         * directivas de Blade (@hasrole, @hasanyrole) y el middleware
         * `role:` puedan usarse. Sin esto, spatie_roles queda vacia y
         * cualquier chequeo de rol devuelve false para todo el mundo.
         */
        foreach ($roles as $rol) {
            SpatieRole::findOrCreate($rol['role_name'], 'web');
        }
    }
}
