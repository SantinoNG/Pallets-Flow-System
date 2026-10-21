<?php

// Crea un cliente nuevo (usuario con rol Cliente + su ficha) para poder
// probar la app como un usuario recien llegado.
//
// Uso:
//   php scripts/crear-cliente.php <email> <nombre> <apellido> [contrasena]
//
// Ejemplo:
//   php scripts/crear-cliente.php sandra.medina@ecommerce.com Sandra Medina
//
// La contrasena por defecto es "cliente1234" (la misma que el resto de los
// clientes de prueba).

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

$email = $argv[1] ?? 'sandra.medina@ecommerce.com';
$nombre = $argv[2] ?? 'Sandra';
$apellido = $argv[3] ?? 'Medina';
$clave = $argv[4] ?? 'cliente1234';

$idRol = DB::table('roles')->where('role_name', 'Cliente')->value('id_role');

if ($idRol === null) {
    fwrite(STDERR, "  ERROR: no existe el rol Cliente. Corre RoleSeeder primero.\n");
    exit(1);
}

// El usuario
DB::table('users')->updateOrInsert(
    ['email' => $email],
    [
        'name'       => $nombre . ' ' . $apellido,
        'password'   => Hash::make($clave),
        'id_role'    => $idRol,
        'session'    => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]
);

$idUser = (int) DB::table('users')->where('email', $email)->value('id_user');

// La ficha de cliente
DB::table('customers')->updateOrInsert(
    ['id_user' => $idUser],
    [
        'first_name' => $nombre,
        'last_name'  => $apellido,
        'address'    => 'Av. de los Palletistas 123, Posadas',
        'phone'      => '3764-55' . str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT),
    ]
);

// El espejo en Spatie: DB::table()->insert() no dispara los eventos de
// Eloquent, asi que el trait HasRoles no se entera y model_has_roles
// queda vacia. Por eso se carga el modelo y se usa asignarRol().
$user = User::where('email', $email)->first();

if ($user) {
    $user->asignarRol('Cliente');
}

$idCustomer = DB::table('customers')->where('id_user', $idUser)->value('id_customer');
$compras = DB::table('sales')->where('id_customer', $idCustomer)->count();

echo "  Cliente creado\n";
echo "    email     : {$email}\n";
echo "    contraseña: {$clave}\n";
echo "    usuario   : {$idUser}   ficha: {$idCustomer}\n";
echo "    rol       : Cliente (id {$idRol})\n";
echo "    compras   : {$compras}\n";
echo "\n  Entrar en: http://127.0.0.1/Pallets-Flow-System/public/login\n";
