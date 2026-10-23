<?php

// Busca fugas de informacion: entra como un rol y revisa TODAS las
// paginas que ese rol puede ver, buscando datos que no le corresponden.
//
// Uso: php scripts/buscar-fugas.php [email] [clave]

require __DIR__ . '/http-cliente.php';

$base = 'http://127.0.0.1/Pallets-Flow-System/public';
$email = $argv[1] ?? 'cliente@gmail.com';
$clave = $argv[2] ?? 'cliente1234';

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Datos que NO le pertenecen a un cliente
$secretos = [];

foreach (DB::table('users')->where('email', '!=', $email)->get() as $u) {
    $secretos[] = ['etiqueta' => 'email de ' . $u->email, 'valor' => $u->email];
}

$c = new ClienteHttp();
$c->login($base, $email, $clave);

$paginas = [
    '/dashboard', '/articles', '/articles/1/edit', '/articles/create',
    '/suppliers', '/suppliers/1/edit', '/suppliers/create',
    '/materias-primas', '/materias-primas/1/edit', '/materias-primas/create',
    '/employees', '/employees/1/edit', '/employees/create', '/employees/1/report',
    '/roles', '/roles/1/edit', '/roles/create',
    '/usuarios', '/usuarios/1/roles',
    '/cart', '/sales', '/sales/1', '/sales/1/receipt', '/profile', '/tutorial',
    '/articles/pdf', '/employees/pdf', '/proveedores/pdf', '/materias-primas/pdf', '/usuarios/pdf', '/sales/1/pdf',
];

echo "  Rol: {$email}\n";
echo "  Se buscan datos de " . count($secretos) . " usuarios ajenos\n\n";

$fugas = [];

foreach ($paginas as $p) {
    $r = $c->pedir($base . $p);
    $html = $r['html'];

    if ($r['codigo'] >= 400) {
        continue;
    }

    foreach ($secretos as $s) {
        if (str_contains($html, $s['valor'])) {
            $fugas[] = ['pagina' => $p, 'dato' => $s['etiqueta']];
        }
    }
}

if ($fugas) {
    echo "  !!! FUGAS ENCONTRADAS:\n";
    foreach ($fugas as $f) {
        echo "      {$f['pagina']}  ->  {$f['dato']}\n";
    }
} else {
    echo "  Sin fugas: ninguna pagina muestra datos de otros usuarios.\n";
}

// Y que paginas puede ver cada rol, con un resumen
echo "\n  Resumen por rol:\n";
foreach ([
    'admin@ecommerce.com'   => 'admin1234',
    'vendedor@ecommerce.com' => 'vendedor1234',
    'deposito@ecommerce.com' => 'deposito1234',
    'cliente@gmail.com'     => 'cliente1234',
] as $usuario => $clave) {
    $x = new ClienteHttp();
    $x->login($base, $usuario, $clave);

    $puede = 0;
    $bloqueadas = 0;
    $fugasRol = 0;

    foreach ($paginas as $p) {
        $r = $x->pedir($base . $p);

        if ($r['codigo'] == 403) {
            $bloqueadas++;
            continue;
        }
        if ($r['codigo'] >= 400) {
            continue;
        }
        $puede++;

        foreach ($secretos as $s) {
            if ($s['valor'] === $usuario) {
                continue;   // su propio email no es fuga
            }
            if (str_contains($r['html'], $s['valor'])) {
                $fugasRol++;
            }
        }
    }

    if ($fugasRol > 0) {
        foreach ($paginas as $p) {
            $r = $x->pedir($base . $p);
            if ($r['codigo'] == 403 || $r['codigo'] >= 400) {
                continue;
            }
            $cuáles = [];
            foreach ($secretos as $s) {
                if ($s['valor'] === $usuario) {
                    continue;
                }
                if (str_contains($r['html'], $s['valor'])) {
                    $cuáles[] = basename(str_replace('@', '', $s['valor']));
                }
            }
            if ($cuáles) {
                echo '        ' . str_pad($p, 22) . ' ve: ' . implode(', ', array_slice($cuáles, 0, 6)) . PHP_EOL;
            }
        }
    }

    echo '    ' . str_pad($usuario, 28)
        . ' ' . str_pad($puede . ' paginas', 13)
        . ' ' . str_pad($bloqueadas . ' bloqueadas', 15)
        . ' ' . $fugasRol . ' fugas'
        . ($fugasRol > 0 ? '  <-- REVISAR' : '') . PHP_EOL;
}
