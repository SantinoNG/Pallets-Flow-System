<?php

namespace Database\Seeders;

use App\Models\User;
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

            /*
             * Las marcas de tiempo solo se ponen al crear. Sin esto la
             * columna `created_at` queda en NULL y el listado de usuarios,
             * que ordena por `latest()`, manda esos usuarios al final: el
             * admin no aparecia ni en la primera pagina.
             */
            if (! DB::table('users')->where('email', $u['email'])->exists()) {
                $fila['created_at'] = now();
                $fila['updated_at'] = now();
            }

            DB::table('users')->updateOrInsert(['email' => $u['email']], $fila);

            // se recupera el id real por si el usuario ya existia
            $userIds[$u['email']] = (int) DB::table('users')
                ->where('email', $u['email'])
                ->value('id_user');

            /*
             * Espejo en Spatie.
             *
             * DB::table()->insert() escribe en la base pero NO dispara los
             * eventos de Eloquent, asi que el trait HasRoles no se entera
             * y `model_has_roles` queda vacia. Por eso, despues de insertar,
             * se carga el modelo y se le asigna el rol con asignarRol(), que
             * escribe en users.id_role y en la capa de Spatie a la vez.
             *
             * Sin esto, spatie_roles queda vacia y cualquier chequeo de rol
             * (@hasrole, middleware role:) devuelve false para todo el mundo.
             */
            $user = User::where('email', $u['email'])->first();

            if ($user !== null) {
                $user->asignarRol($u['rol']);
            }
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
                'last_name'  => 'Gómez',
                'phone'      => '3764-100200',
            ],
            [
                'email'      => 'vendedor@ecommerce.com',
                'id_role'    => 'Vendedor',
                'first_name' => 'Lucía',
                'last_name'  => 'Ramírez',
                'phone'      => '3764-200300',
            ],
            [
                'email'      => 'deposito@ecommerce.com',
                'id_role'    => 'Deposito',
                'first_name' => 'Martín',
                'last_name'  => 'López',
                'phone'      => '3764-300400',
            ],
        ];

        foreach ($empleados as $e) {
            $ficha = [
                'id_role'    => $this->roleId($e['id_role']),
                'first_name' => $e['first_name'],
                'last_name'  => $e['last_name'],
                'phone'      => $e['phone'],
            ];

            // Igual que arriba: marcas de tiempo solo al crear.
            if (! DB::table('employees')->where('id_user', $userIds[$e['email']])->exists()) {
                $ficha['created_at'] = now();
                $ficha['updated_at'] = now();
            }

            DB::table('employees')->updateOrInsert(
                ['id_user' => $userIds[$e['email']]],
                $ficha
            );
        }

        // Cliente (el usuario 4)
        $fichaCliente = [
            'first_name' => 'Ana',
            'last_name'  => 'Torres',
            'address'    => 'San Martin 450, Posadas',
            'phone'      => '3764-500600',
        ];

        if (! DB::table('customers')->where('id_user', $userIds['cliente@gmail.com'])->exists()) {
            $fichaCliente['created_at'] = now();
            $fichaCliente['updated_at'] = now();
        }

        DB::table('customers')->updateOrInsert(
            ['id_user' => $userIds['cliente@gmail.com']],
            $fichaCliente
        );
    }
}
