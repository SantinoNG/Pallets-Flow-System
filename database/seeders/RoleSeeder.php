<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * Catalogo de roles del proyecto.

         * Los 4 originales (Jefe, Administrador, Encargado, Cliente) se
         * respetan tal cual. Se AGREGAN "Vendedor" y "Deposito" porque
         * UserSeeder necesita un rol para el usuario vendedor@ y otro para
         * deposito@, y antes no existian: el seeder les ponia id_role=2 y
         * id_role=3 "a ojo", lo que hacia que:
         *
         *   admin@ecommerce.com     -> Jefe            (no Administrador)
         *   vendedor@ecommerce.com  -> Administrador   <-- escalada de privilegios
         *   deposito@ecommerce.com  -> Encargado       (no Deposito)
         *
         * Los ids se generan por auto_increment, por lo que el orden de
         * insercion ya no importa: UserSeeder busca los roles por nombre.
         */
        $roles = [
            ['role_name' => 'Jefe',          'description' => 'Gestion total del sistema'],
            ['role_name' => 'Administrador', 'description' => 'Gestion de inventario y proveedores'],
            ['role_name' => 'Encargado',     'description' => 'Registros de clientes y ventas'],
            ['role_name' => 'Cliente',       'description' => 'Acceso a compras y seguimiento de pedidos'],
            ['role_name' => 'Vendedor',      'description' => 'Registro y gestion de ventas'],
            ['role_name' => 'Deposito',      'description' => 'Ingreso y control de mercaderia'],
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
    }
}
