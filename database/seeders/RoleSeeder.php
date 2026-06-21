<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder {
    public function run(): void {
        DB::table('roles')->insert([
            ['role_name' => 'Jefe',    'description' => 'Gestión total del sistema'],
            ['role_name' => 'Administrador', 'description' => 'Gestión de inventario y proveedores'],
            ['role_name' => 'Encargado', 'description' => 'Registros de clientes y ventas'],
            ['role_name' => 'Cliente',  'description' => 'Acceso a compras y seguimiento de pedidos'],
        ]);
    }
}