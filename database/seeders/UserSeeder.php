<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Creamos un usuario administrador por defecto
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('1234')
        ]);
        $admin->assignRole('Administrador');

        $usuario1 = User::create([
            'name' => 'Carlos VIllalba',
            'email' => 'carlos@example.com',
            'password' => bcrypt('1234')
        ]);
        $usuario1->assignRole('Empleado');

        $usuario2 = User::create([
            'name' => 'Juan Perez',
            'email' => 'juanperez@example.com',
            'password' => bcrypt('1234')
        ]);
        $usuario2->assignRole('Cliente');


    }
}
