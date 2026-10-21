<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrige el nombre del rol de depósito.
 *
 * El rol se creo como "Depósito" (con tilde), pero las rutas y las
 * directivas de Blade usan "Deposito" (sin tilde):
 *
 *     routes/web.php           role:Jefe|Administrador|Encargado|Deposito
 *     layouts/app.blade.php    @role('Jefe|Administrador|Encargado|Deposito')
 *
 * MySQL no distingue tildes al comparar (collation *_ci), asi que la base
 * de datos SI encuentra "Deposito" y no habia error visible. Pero Spatie
 * compara los nombres de rol como cadenas de PHP, y ahi si es exacto:
 *
 *     'Depósito' === 'Deposito'   ->   false
 *
 * Resultado: el usuario de depósito caia en HTTP 403 en el modulo de
 * articulos, que es justo el modulo donde su rol deberia poder entrar, y
 * el link del sidebar tampoco le aparecia.
 *
 * Se renombra la fila a "Deposito" para que coincida con el codigo.
 */
return new class extends Migration {
    public function up(): void
    {
        // La comparacion se hace en PHP a proposito: MySQL ignoraria la
        // tilde y no encontraria la fila que hay que renombrar.
        foreach (DB::table('roles')->get() as $rol) {
            if ($rol->role_name === 'Depósito') {
                DB::table('roles')
                    ->where('id_role', $rol->id_role)
                    ->update(['role_name' => 'Deposito']);
            }
        }

        foreach (DB::table('spatie_roles')->get() as $rol) {
            if ($rol->name === 'Depósito') {
                DB::table('spatie_roles')
                    ->where('id', $rol->id)
                    ->update(['name' => 'Deposito']);
            }
        }
    }

    public function down(): void
    {
        DB::table('roles')->where('role_name', 'Deposito')->update(['role_name' => 'Depósito']);
        DB::table('spatie_roles')->where('name', 'Deposito')->update(['name' => 'Depósito']);
    }
};
