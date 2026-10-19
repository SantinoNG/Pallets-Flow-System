<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Devuelve el id de un rol por su nombre.
     *
     * Si el rol no existe se corta con un mensaje claro, en vez de
     * adivinar un id y dejar el usuario con el rol equivocado (que es
     * exactamente el bug que tenia este seeder).
     */
    private function roleId(string $nombre): int
    {
        $id = DB::table('roles')->where('role_name', $nombre)->value('id_role');

        if ($id === null) {
            throw new \RuntimeException(
                "El rol '{$nombre}' no existe. Corré RoleSeeder antes que este seeder "
                . "(o usá 'php artisan db:seed' que los ejecuta en orden)."
            );
        }

        return (int) $id;
    }

    public function run(): void
    {
        /*
         * Usuarios base.
         *
         * El rol se busca POR NOMBRE, no se escribe un id fijo. Antes se
         * ponia id_role => 1,2,3,4 asumiendo que ese era el orden de
         * RoleSeeder, y como los ids se autogeneran eso no se puede
         * garantizar: el vendedor terminaba con el rol Administrador.
         */
        $usuarios = [
            [
                'email'    => 'admin@ecommerce.com',
                'name'     => 'Admin',
                'password' => Hash::make('admin1234'),
                'rol'      => 'Administrador',
            ],
            [
                'email'    => 'vendedor@ecommerce.com',
                'name'     => 'Vendedor',
                'password' => Hash::make('vendedor1234'),
                'rol'      => 'Vendedor',
            ],
            [
                'email'    => 'deposito@ecommerce.com',
                'name'     => 'Deposito',
                'password' => Hash::make('deposito1234'),
                'rol'      => 'Deposito',
            ],
            [
                'email'    => 'cliente@gmail.com',
                'name'     => 'Cliente',
                'password' => Hash::make('cliente1234'),
                'rol'      => 'Cliente',
            ],
        ];

        $userIds = [];

        foreach ($usuarios as $u) {
            $fila = [
                'email'    => $u['email'],
                'name'     => $u['name'],
                'password' => $u['password'],
                'id_role'  => $this->roleId($u['rol']),
                'session'  => null,
            ];

            DB::table('users')->updateOrInsert(['email' => $u['email']], $fila);

            // se recupera el id real por si el usuario ya existia
            $userIds[$u['email']] = (int) DB::table('users')
                ->where('email', $u['email'])
                ->value('id_user');
        }

        /*
         * Empleados: los usuarios 1, 2 y 3 (admin, vendedor, deposito).
         * Antes sePONIA id_user => 1,2,3 asumiendo que ese era el orden de
         * inserción. Ahora se busca el id real por email.
         */
        $empleados = [
            [
                'email'      => 'admin@ecommerce.com',
                'id_role'    => 'Administrador',
                'first_name' => 'Carlos',
                'last_name'  => 'Gomez',
                'phone'      => '3764-100200',
            ],
            [
                'email'      => 'vendedor@ecommerce.com',
                'id_role'    => 'Vendedor',
                'first_name' => 'Lucia',
                'last_name'  => 'Ramirez',
                'phone'      => '3764-200300',
            ],
            [
                'email'      => 'deposito@ecommerce.com',
                'id_role'    => 'Deposito',
                'first_name' => 'Martin',
                'last_name'  => 'Lopez',
                'phone'      => '3764-300400',
            ],
        ];

        foreach ($empleados as $e) {
            DB::table('employees')->updateOrInsert(
                ['id_user' => $userIds[$e['email']]],
                [
                    'id_role'    => $this->roleId($e['id_role']),
                    'first_name' => $e['first_name'],
                    'last_name'  => $e['last_name'],
                    'phone'      => $e['phone'],
                ]
            );
        }

        // Cliente (el usuario 4)
        DB::table('customers')->updateOrInsert(
            ['id_user' => $userIds['cliente@gmail.com']],
            [
                'first_name' => 'Ana',
                'last_name'  => 'Torres',
                'address'    => 'San Martin 450, Posadas',
                'phone'      => '3764-500600',
            ]
        );
    }
}
