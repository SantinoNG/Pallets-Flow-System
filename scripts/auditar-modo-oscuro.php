<?php

// Auditoria del modo oscuro: busca clases de superficie claras que NO
// tienen su equivalente `dark:`.
//
// Sin esto, en modo oscuro quedan tarjetas blancas, texto gris claro sobre
// fondo oscuro, y bordes invisibles.
//
// Uso: php auditar-modo-oscuro.php [url-base]

$base = rtrim($argv[1] ?? 'http://127.0.0.1/Pallets-Flow-System/public', '/');

$jar = tempnam(sys_get_temp_dir(), 'ck');
curl_setopt_array($ch = curl_init("$base/login"), [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar,
]);
$html = curl_exec($ch); curl_close($ch);
preg_match('/name="_token"\s+value="([^"]+)"/', $html, $m);

curl_setopt_array($ch = curl_init("$base/login"), [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar,
    CURLOPT_POSTFIELDS => http_build_query([
        '_token' => $m[1] ?? '', 'email' => 'admin@ecommerce.com', 'password' => 'admin1234',
    ]),
]);
curl_exec($ch); curl_close($ch);

$paginas = [
    '/dashboard', '/articles', '/articles/create', '/articles/1', '/suppliers', '/suppliers/create', '/materias-primas', '/materias-primas/create', '/employees',
    '/roles', '/roles/create', '/usuarios', '/usuarios/create', '/usuarios/1/roles',
    '/cart', '/sales', '/profile', '/tutorial',
];

// Paginas de invitado: se piden SIN sesion (siEstasConectado seria
// verdadero, /login redirige al dashboard).
$paginasInvitado = ['/login', '/register'];

// clase clara => contrapartes oscuras que DEBERIAN existir
//
// Se aceptan varias porque no todas las etiquetas apagadas se oscurecen
// igual: los rotulos de las tarjetas del dashboard van en blanco puro
// (zinc-100), mientras que el texto de apoyo se deja en gris claro.
$deberia = [
    'bg-white'        => ['dark:bg-zinc-900', 'dark:bg-zinc-950'],
    'bg-gray-50'      => ['dark:bg-zinc-950'],
    'bg-gray-100'     => ['dark:bg-zinc-950'],
    'text-gray-900'   => ['dark:text-zinc-100'],
    'text-gray-800'   => ['dark:text-zinc-100'],
    'text-gray-700'   => ['dark:text-zinc-200', 'dark:text-zinc-100'],
    'text-gray-600'   => ['dark:text-zinc-300', 'dark:text-zinc-200'],
    'text-gray-500'   => ['dark:text-zinc-400', 'dark:text-zinc-300', 'dark:text-zinc-100'],
    'border-gray-100' => ['dark:border-zinc-800'],
    'border-gray-200' => ['dark:border-zinc-800'],
    'border-gray-300' => ['dark:border-zinc-700'],
    'border-zinc-200' => ['dark:border-zinc-800'],
    'border-zinc-300' => ['dark:border-zinc-700', 'dark:border-zinc-600'],

    /*
     * Colores de aviso y de acciones destructivas.
     *
     * Faltaban: el boton "Quitar" del carrito quedo con bg-red-50 sin
     * variante y en modo oscuro se veia como un recuadro casi blanco sobre
     * la tabla zinc. Se agregan para que no vuelva a pasar.
     */
    'bg-red-50'        => ['dark:bg-red-950/40'],
    'bg-red-100'       => ['dark:bg-red-950/40'],
    'bg-green-100'     => ['dark:bg-green-950/40'],
    'bg-amber-50'      => ['dark:bg-amber-950/40'],
    'text-red-600'     => ['dark:text-red-400', 'dark:text-red-300'],
    'text-red-700'     => ['dark:text-red-300', 'dark:text-red-400'],
    'text-red-800'     => ['dark:text-red-300'],
    'text-green-700'   => ['dark:text-green-300', 'dark:text-green-400'],
    'text-green-800'   => ['dark:text-green-300'],
    'text-amber-600'   => ['dark:text-amber-400'],
    'border-red-200'   => ['dark:border-red-900'],
    'border-green-400' => ['dark:border-green-900'],
    'border-amber-200' => ['dark:border-amber-900'],
];

$totalHuecos = 0;

/**
 * Revisa una pagina ya descargada.
 */
function revisar(string $p, string $html, array $deberia, int &$totalHuecos): void
{
    preg_match_all('/class="([^"]*)"/', $html, $cm);

    $huecos = [];
    foreach ($cm[1] as $bloque) {
        $tokens = preg_split('/\s+/', trim($bloque));
        foreach ($tokens as $t) {
            if (! isset($deberia[$t])) continue;

            // Basta con que aparezca una de las variantes aceptadas.
            $tiene = false;
            foreach ($deberia[$t] as $oscura) {
                if (in_array($oscura, $tokens, true)) {
                    $tiene = true;
                    break;
                }
            }
            if ($tiene) continue;

            $huecos[$t] = ($huecos[$t] ?? 0) + 1;
        }
    }

    if ($huecos) {
        $totalHuecos += array_sum($huecos);
        echo "  " . str_pad($p, 26) . " " . count($huecos) . " clases sin variante dark:\n";
        foreach ($huecos as $clase => $n) {
            echo "      " . str_pad($clase, 22) . "x$n   (falta una de: "
                . implode(' | ', $deberia[$clase]) . ")\n";
        }
    } else {
        echo "  " . str_pad($p, 26) . " OK\n";
    }
}

foreach ($paginas as $p) {
    curl_setopt_array($ch = curl_init("$base$p"), [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar,
    ]);
    $html = curl_exec($ch); curl_close($ch);
    if (! $html) { continue; }

    revisar($p, $html, $deberia, $totalHuecos);
}

foreach ($paginasInvitado as $p) {
    $tmp = tempnam(sys_get_temp_dir(), 'inv');
    curl_setopt_array($ch = curl_init("$base$p"), [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $tmp, CURLOPT_COOKIEFILE => $tmp,
    ]);
    $html = curl_exec($ch); curl_close($ch);
    @unlink($tmp);
    if (! $html) { continue; }

    revisar($p, $html, $deberia, $totalHuecos);
}

echo "\n  $totalHuecos clases claras sin equivalente oscuro\n";
