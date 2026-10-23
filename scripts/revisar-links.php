<?php

// Revisa TODOS los links de las paginas (descargas, exportaciones, PDF) y
// comprueba que cada uno responde bien.
//
// Uso: php scripts/revisar-links.php [email]

require __DIR__ . '/http-cliente.php';

$base = 'http://127.0.0.1/Pallets-Flow-System/public';
$email = $argv[1] ?? 'admin@ecommerce.com';
$clave = $argv[2] ?? 'admin1234';

$c = new ClienteHttp();
$c->login($base, $email, $clave);

$paginas = [
    '/dashboard', '/articles', '/articles/create', '/articles/1/edit',
    '/suppliers', '/suppliers/create', '/suppliers/1/edit',
    '/materias-primas', '/materias-primas/create', '/materias-primas/1/edit',
    '/employees', '/employees/create', '/employees/1/edit',
    '/roles', '/roles/create', '/roles/1/edit',
    '/usuarios', '/usuarios/1/roles',
    '/cart', '/sales', '/profile', '/tutorial',
];

$links = [];

foreach ($paginas as $p) {
    $html = $c->pedir("$base$p")['html'];

    if ($html === '') {
        echo "  (no se pudo leer $p)\n";
        continue;
    }

    // Todos los <a href> de la pagina
    preg_match_all('~<a\b[^>]*href="([^"]+)"~i', $html, $m);

    foreach ($m[1] as $href) {
        if (!str_starts_with($href, $base)) {
            continue;
        }
        $ruta = str_replace($base, '', $href);
        $links[$ruta] = $p;
    }
}

// Se prueban todos
ksort($links);

$malos = 0;
$ok = 0;

echo "  Probando " . count($links) . " links unicos\n\n";

foreach ($links as $ruta => $origen) {
    // Los PDF se piden en binario para poder chequear la firma %PDF-
    $r = $c->pedir($base . $ruta, null, true);

    $esPdf = str_contains($r['tipo'] ?? '', 'pdf');
    $mal = false;

    if ($r['codigo'] >= 400) {
        $mal = true;
    }

    if ($esPdf) {
        // Un PDF tiene que empezar con %PDF-
        $cuerpo = $r['binario'] !== '' ? $r['binario'] : $r['html'];
        if (! str_starts_with(ltrim($cuerpo), '%PDF-')) {
            $mal = true;
            $r['tipo'] .= ' (sin firma %PDF-)';
        }
    }

    if ($mal) {
        $malos++;
        printf("  [FALLA] %-46s HTTP %s  %-26s (en %s)\n", $ruta, $r['codigo'], $r['tipo'], $origen);
    } else {
        $ok++;
        if ($esPdf || str_contains($ruta, 'pdf')) {
            printf("  [ ok  ] %-46s HTTP %s  %-26s %s bytes\n", $ruta, $r['codigo'], $r['tipo'], $r['codigo'] == 200 ? strlen($r['binario'] ?: $r['html']) : 0);
        }
    }
}

echo "\n  {$ok} links ok, {$malos} con problema\n";
