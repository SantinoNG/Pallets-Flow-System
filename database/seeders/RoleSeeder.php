<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role; 
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $permisoEditarRoles = Permission::create(['name' => 'editar_roles']);


        // Creamos los roles base del sistema
        $rol_admin=Role::create(['name' => 'Administrador']);
        $rol_empleado=Role::create(['name' => 'Empleado']);
        $rol_cliente=Role::create(['name' => 'Cliente']);
        $rol_supervisor=Role::create(['name' => 'Supervisor']);


        // Asignamos permisos a los roles
        $rol_admin->givePermissionTo($permisoEditarRoles);

    }
}
