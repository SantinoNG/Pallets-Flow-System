<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),

            /*
             * users.id_role es NOT NULL con Foreign Key a roles.id_role, asi que
             * la factory tiene que dar un valor valido o los tests de Breeze
             * fallan con "NOT NULL constraint failed: users.id_role".
             *
             * Se usa el rol "Cliente" porque es el de menor privilegio: para
             * probar autenticacion no hace falta que el usuario sea admin.
             * Si el test necesita permisos, usar ->withRol('Administrador').
             */
            'id_role' => $this->rolPorDefecto(),
        ];
    }

    /**
     * Devuelve (creando si hace falta) el id del rol "Cliente".
     *
     * NO se cachea en un statico a proposito: los tests usan RefreshDatabase
     * sobre una base SQLite en memoria, y lo que se inserta en un test se
     * revierte al terminar. Un id cacheado apuntaria a una fila que ya no
     * existe y el INSERT fallaria con "FOREIGN KEY constraint failed".
     *
     * Se busca el rol del catalogo real del proyecto (Cliente, Vendedor,
     * Deposito, Administrador, Encargado, Jefe) y no uno generado al azar,
     * para que los tests usen los mismos roles que la aplicacion.
     */
    private function rolPorDefecto(): int
    {
        $id = DB::table('roles')->where('role_name', 'Cliente')->value('id_role');

        if ($id === null) {
            // La tabla roles puede estar vacia en una base recien migrada.
            // Se inserta para no romper la Foreign Key de users.id_role.
            DB::table('roles')->insert([
                'role_name'   => 'Cliente',
                'description' => 'Acceso a compras y seguimiento de pedidos',
            ]);

            $id = DB::table('roles')->where('role_name', 'Cliente')->value('id_role');
        }

        return (int) $id;
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Asigna un rol especifico al usuario que se este creando.
     *
     * Uso:  User::factory()->withRol('Administrador')->create();
     */
    public function withRol(string $nombreRol): static
    {
        return $this->state(fn (array $attributes) => [
            'id_role' => DB::table('roles')->where('role_name', $nombreRol)->value('id_role'),
        ]);
    }
}
