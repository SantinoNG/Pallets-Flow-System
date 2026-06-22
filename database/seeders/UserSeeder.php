<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
 
class UserSeeder extends Seeder {
    public function run(): void {
        // Usuarios base (roles: 1=Admin, 2=Vendedor, 3=Depósito, 4=Cliente)
        DB::table('users')->insert([
            [
                'email'    => 'admin@ecommerce.com',
                'password' => Hash::make('admin1234'),
                'id_role'  => 1,
                'session'  => null,
            ],
            [
                'email'    => 'vendedor@ecommerce.com',
                'password' => Hash::make('vendedor1234'),
                'id_role'  => 2,
                'session'  => null,
            ],
            [
                'email'    => 'deposito@ecommerce.com',
                'password' => Hash::make('deposito1234'),
                'id_role'  => 3,
                'session'  => null,
            ],
            [
                'email'    => 'cliente@gmail.com',
                'password' => Hash::make('cliente1234'),
                'id_role'  => 4,
                'session'  => null,
            ],
        ]);
 
        // Empleados (usuarios 1, 2 y 3)
        DB::table('employees')->insert([
            ['id_user' => 1, 'id_role' => 1, 'first_name' => 'Carlos',  'last_name' => 'Gómez',   'phone' => '3764-100200'],
            ['id_user' => 2, 'id_role' => 2, 'first_name' => 'Lucía',   'last_name' => 'Ramírez', 'phone' => '3764-200300'],
            ['id_user' => 3, 'id_role' => 3, 'first_name' => 'Martín',  'last_name' => 'López',   'phone' => '3764-300400'],
        ]);
 
        // Cliente (usuario 4)
        DB::table('customers')->insert([
            [
                'id_user'    => 4,
                'first_name' => 'Ana',
                'last_name'  => 'Torres',
                'address'    => 'San Martín 450, Posadas',
                'phone'      => '3764-500600',
            ],
        ]);
    }
}