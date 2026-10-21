<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Diez clientes mas, con su usuario y su ficha.
 *
 * El sistema tenia UN solo cliente (Ana Torres) y TODAS sus ventas. Eso
 * dejaba el dashboard sin movimiento: los graficos de ventas por mes y de
 * medios de pago tenian un unico cliente y el filtro por cliente no
 * podia comparar nada.
 *
 * Con estos diez, el catalogo muestra compras repartidas en los ultimos
 * cuatro meses y se puede entrar como cualquiera de ellos para ver su
 * propio historial de compras.
 *
 * Cada cliente tiene:
 *   - un usuario con rol Cliente (para poder entrar a la aplicacion),
 *   - una fila en `customers` que lo vincula con ese usuario.
 *
 * La vinculacion va por `customers.id_user`: la tabla `users` no tiene
 * columna `id_customer` (la relacion es de una sola direccion).
 */
class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $idRolCliente = DB::table('roles')->where('role_name', 'Cliente')->value('id_role');

        if ($idRolCliente === null) {
            throw new \RuntimeException(
                'No existe el rol Cliente. Corre RoleSeeder antes que CustomerSeeder.'
            );
        }

        $clientes = [
            [
                'email'      => 'bruno.ferrari@ecommerce.com',
                'first_name' => 'Bruno',
                'last_name'  => 'Ferrari',
                'address'    => 'Av. Brasil 1420, Posadas',
                'phone'      => '3764-510101',
            ],
            [
                'email'      => 'carla.mendez@ecommerce.com',
                'first_name' => 'Carla',
                'last_name'  => 'Méndez',
                'address'    => 'San Lorenzo 1780, Posadas',
                'phone'      => '3764-510202',
            ],
            [
                'email'      => 'diego.sosa@ecommerce.com',
                'first_name' => 'Diego',
                'last_name'  => 'Sosa',
                'address'    => 'Urquiza 655, Posadas',
                'phone'      => '3764-510303',
            ],
            [
                'email'      => 'elena.duarte@ecommerce.com',
                'first_name' => 'Elena',
                'last_name'  => 'Duarte',
                'address'    => '25 de Mayo 903, Posadas',
                'phone'      => '3764-510404',
            ],
            [
                'email'      => 'facundo.rios@ecommerce.com',
                'first_name' => 'Facundo',
                'last_name'  => 'Ríos',
                'address'    => 'Colón 2210, Posadas',
                'phone'      => '3764-510505',
            ],
            [
                'email'      => 'gabriela.paz@ecommerce.com',
                'first_name' => 'Gabriela',
                'last_name'  => 'Paz',
                'address'    => 'Aristóbulo del Valle 130, Posadas',
                'phone'      => '3764-510606',
            ],
            [
                'email'      => 'hernan.cabrera@ecommerce.com',
                'first_name' => 'Hernán',
                'last_name'  => 'Cabrera',
                'address'    => 'Belgrano 3450, Posadas',
                'phone'      => '3764-510707',
            ],
            [
                'email'      => 'irina.lopez@ecommerce.com',
                'first_name' => 'Irina',
                'last_name'  => 'López',
                'address'    => 'Junín 780, Posadas',
                'phone'      => '3764-510808',
            ],
            [
                'email'      => 'julian.vargas@ecommerce.com',
                'first_name' => 'Julián',
                'last_name'  => 'Vargas',
                'address'    => 'Almirante Brown 1875, Posadas',
                'phone'      => '3764-510909',
            ],
            [
                'email'      => 'karen.aquino@ecommerce.com',
                'first_name' => 'Karen',
                'last_name'  => 'Aquino',
                'address'    => 'Lafinur 1150, Posadas',
                'phone'      => '3764-511010',
            ],
        ];

        foreach ($clientes as $c) {
            /*
             * El usuario se crea con updateOrInsert para que la siembra sea
             * idempotente: correr db:seed dos veces no duplica clientes.
             * Las marcas de tiempo solo se ponen al crear: si el usuario ya
             * existia, no se tocan.
             */
            $existe = DB::table('users')->where('email', $c['email'])->exists();

            $fila = [
                'name'     => $c['first_name'] . ' ' . $c['last_name'],
                'password' => Hash::make('cliente1234'),
                'id_role'  => $idRolCliente,
                'session'  => null,
            ];

            if (! $existe) {
                $fila['created_at'] = now();
                $fila['updated_at'] = now();
            }

            DB::table('users')->updateOrInsert(['email' => $c['email']], $fila);

            $idUser = (int) DB::table('users')
                ->where('email', $c['email'])
                ->value('id_user');

            // Ficha de cliente.
            $ficha = [
                'first_name' => $c['first_name'],
                'last_name'  => $c['last_name'],
                'address'    => $c['address'],
                'phone'      => $c['phone'],
            ];

            // Marcas de tiempo solo al crear, como en el UserSeeder.
            if (! DB::table('customers')->where('id_user', $idUser)->exists()) {
                $ficha['created_at'] = now();
                $ficha['updated_at'] = now();
            }

            DB::table('customers')->updateOrInsert(
                ['id_user' => $idUser],
                $ficha
            );

            /*
             * Espejo en Spatie.
             *
             * DB::table()->insert() escribe en la base pero NO dispara los
             * eventos de Eloquent, asi que el trait HasRoles no se entera
             * y `model_has_roles` queda vacia. Por eso despues de insertar
             * se carga el modelo y se le asigna el rol con asignarRol(),
             * que escribe users.id_role y la capa de Spatie a la vez.
             */
            $user = User::where('email', $c['email'])->first();

            if ($user !== null) {
                $user->asignarRol('Cliente');
            }
        }
    }
}
