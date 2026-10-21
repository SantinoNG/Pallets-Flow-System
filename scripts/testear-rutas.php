<?php

// Barrido de todas las rutas del proyecto, con la sesion de admin.
//
// Las cookies se manejan EN MEMORIA (con CURLOPT_COOKIELIST) y se mandan a
// mano en el header Cookie. No se usa el cookie jar de curl porque en PHP 8
// el archivo no se escribe hasta que el handle se destruye, y dentro de un
// script eso pasa al final: el servidor se recibia sin la cookie de sesion.
//
// Uso: php scripts/testear-rutas.php [email]

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

$email = $argv[1] ?? 'admin@ecommerce.com';
$clave = $argv[2] ?? 'admin1234';
$base  = 'http://127.0.0.1/Pallets-Flow-System/public';

/**
 * Cliente HTTP minimo con las cookies en memoria.
 */
class Cliente
{
    private array $cookies = [];

    public function pedir(string $url, ?array $post = null): array
    {
        $ch = curl_init($url);

        $opciones = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HEADER => true,
        ];

        if ($this->cookies) {
            $header = [];
            foreach ($this->cookies as $nombre => $valor) {
                $header[] = $nombre . '=' . $valor;
            }
            $opciones[CURLOPT_HTTPHEADER] = ['Cookie: ' . implode('; ', $header)];
        }

        if ($post !== null) {
            $opciones[CURLOPT_POST] = true;
            $opciones[CURLOPT_POSTFIELDS] = $post;
        }

        curl_setopt_array($ch, $opciones);
        $respuesta = (string) curl_exec($ch);
        $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $destino = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        $tamano = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $cabeceras = substr($respuesta, 0, $tamano);
        $cuerpo = substr($respuesta, $tamano);

        // Las cookies se leen de las cabeceras Set-Cookie de la respuesta.
        // No se usa el motor de cookies de curl ni su cookie jar: en PHP 8
        // el archivo del jar no se escribe hasta que el handle se destruye,
        // y dentro de un script eso pasa al final.
        if (preg_match_all('/^Set-Cookie:\s*([^=]+)=([^;]*)/mi', $cabeceras, $m)) {
            foreach ($m[1] as $i => $nombre) {
                $this->cookies[trim($nombre)] = trim($m[2][$i]);
            }
        }

        return [
            'html'     => $cuerpo,
            'codigo'   => $codigo,
            'destino'  => $destino,
            'cookies'  => $this->cookies,
        ];
    }
}

$cliente = new Cliente();

// --- Login ---------------------------------------------------------------
$r = $cliente->pedir("$base/login");
preg_match('/name="_token"\s+value="([^"]+)"/', $r['html'], $m);

$r = $cliente->pedir("$base/login", [
    '_token'   => $m[1] ?? '',
    'email'    => $email,
    'password' => $clave,
]);

if ($r['codigo'] != 302) {
    echo "  ERROR: el login devolvio {$r['codigo']}\n";
    exit(1);
}
echo "  sesion iniciada como {$email} (redirige a " . basename($r['destino']) . ")\n\n";

// --- Ids reales para las rutas parametricas ------------------------------
$ids = [
    'articles'    => DB::table('articles')->value('id_article'),
    'proveedores' => DB::table('suppliers')->value('id_supplier'),
    'employees'   => DB::table('employees')->value('id_employee'),
    'roles'       => DB::table('roles')->value('id_role'),
    'usuarios'    => DB::table('users')->value('id_user'),
    'sales'       => DB::table('sales')->value('id_sale'),
    'cart'        => DB::table('cart')->value('id_cart'),
];

/*
 * El Cliente solo puede ver SUS ventas: si se prueba con la venta de otro,
 * el 404 es la respuesta correcta y no un problema. Se busca entonces una
 * venta que sea suya. Si no tiene ninguna, el 404 de las rutas de venta es
 * lo esperado.
 */
$ventas404Esperado = false;
if ($email === 'cliente@gmail.com' || DB::table('users')->where('email', $email)->value('id_role')) {
    $idRoleCliente = DB::table('roles')->where('role_name', 'Cliente')->value('id_role');
    $esCliente = DB::table('users')->where('email', $email)->value('id_role') == $idRoleCliente;

    if ($esCliente) {
        $idCustomer = DB::table('customers')
            ->join('users', 'users.id_user', '=', 'customers.id_user')
            ->where('users.email', $email)
            ->value('customers.id_customer');

        $ventaPropia = DB::table('sales')->where('id_customer', $idCustomer)->value('id_sale');

        if ($ventaPropia) {
            $ids['sales'] = $ventaPropia;
        } else {
            // No tiene compras: las rutas de venta tienen que dar 404, y eso
            // es lo correcto, no un problema.
            $ventas404Esperado = true;
        }
    }
}

// --- Rutas ----------------------------------------------------------------
$rutas = [];
foreach (Route::getRoutes() as $ruta) {
    if (! in_array('GET', $ruta->methods(), true)) {
        continue;
    }

    $uri = $ruta->uri();

    if (str_contains($uri, 'storage/') || str_contains($uri, 'sanctum')) {
        continue;
    }
    if (str_contains($uri, '{')) {
        continue;
    }
    if (str_starts_with($uri, 'livewire-') || $uri === 'up') {
        continue;
    }

    $rutas[$uri] = $ruta->getName() ?? '-';
}

// Las parametricas, con ids que existen de verdad.
// Ojo: el modulo de proveedores usa /suppliers/... para todo menos el PDF,
// que va en /proveedores/pdf (asi lo escribio el profesor).
$rutas["articles/{$ids['articles']}/edit"]      = 'articles.edit';
$rutas["suppliers/{$ids['proveedores']}/edit"]  = 'suppliers.edit';
$rutas["employees/{$ids['employees']}/edit"]    = 'employees.edit';
$rutas["roles/{$ids['roles']}/edit"]            = 'roles.edit';
$rutas["usuarios/{$ids['usuarios']}/roles"]     = 'users.roles.edit';
$rutas["sales/{$ids['sales']}"]                 = 'sales.show';
$rutas["sales/{$ids['sales']}/pdf"]             = 'sales.pdf';
$rutas["sales/{$ids['sales']}/receipt"]         = 'cart.receipt';
$rutas["cart/{$ids['cart']}"]                   = 'cart.show';
$rutas["cart/{$ids['cart']}/checkout"]          = 'cart.checkout';

ksort($rutas);

$malos = 0;
$bloqueadas = 0;
foreach ($rutas as $uri => $nombre) {
    $r = $cliente->pedir("$base/$uri");

    // Un 403 NO es un problema: significa que el control de acceso por rol
    // esta funcionando. Un 404, 419 o 500 si lo son.
    $marca = match ($r['codigo']) {
        200 => '  ok  ',
        302 => 'redir ',
        403 => ' bloq ',
        404 => ' 404  ',
        419 => ' 419  ',
        500 => ' 500  ',
        default => ' ???  ',
    };

    $problema = '';

    // Las rutas de venta de un cliente sin compras tienen que dar 404: es
    // la respuesta correcta (no puede ver las ventas de otro).
    $esRutaDeVenta = str_contains($uri, 'sales/');
    $esperado404 = $ventas404Esperado && $esRutaDeVenta && $r['codigo'] == 404;

    if (in_array($r['codigo'], [404, 419, 500], true) && ! $esperado404) {
        $problema = '  <-- PROBLEMA';
        $malos++;
    } elseif ($esperado404) {
        $problema = '  (404 esperado: no tiene compras)';
    }
    if ($r['codigo'] == 403) {
        $bloqueadas++;
    }

    // Se busca una excepcion de Laravel o un error de debug en la respuesta.
    if (preg_match('/<title>(.*?)<\/title>/s', $r['html'], $t)) {
        $titulo = trim(preg_replace('/\s+/', ' ', $t[1]));
        if (stripos($titulo, 'error') !== false || stripos($titulo, 'exception') !== false) {
            $problema = '  <-- EXCEPCION: ' . $titulo;
            $malos++;
        }
    }

    printf("  %s %-36s %-24s HTTP %s%s\n", $marca, $uri, $nombre, $r['codigo'], $problema);
}

echo "\n  " . count($rutas) . " rutas | $bloqueadas bloqueadas por rol (403, correcto) | $malos con problema\n";
