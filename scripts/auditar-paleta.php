<?php

// Auditoria visual: extrae la paleta y los componentes que usa cada pagina.

$base = rtrim($argv[1] ?? 'http://127.0.0.1/Pallets-Flow-System/public', '/');

$jar = tempnam(sys_get_temp_dir(), 'ck');
$ch = curl_init("$base/login");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar]);
$html = curl_exec($ch); curl_close($ch);
preg_match('/name="_token"\s+value="([^"]+)"/', $html, $m);

$ch = curl_init("$base/login");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar,
    CURLOPT_POSTFIELDS => http_build_query(['_token' => $m[1] ?? '', 'email' => 'admin@ecommerce.com', 'password' => 'admin1234']),
]);
curl_exec($ch); curl_close($ch);

$paginas = [
    '/login', '/register', '/dashboard', '/articles', '/articles/create',
    '/suppliers', '/materias-primas', '/employees', '/roles', '/roles/create', '/users',
    '/usuarios/1/roles', '/cart', '/sales', '/profile', '/tutorial',
];

// Acentos (colores) y su familia Tailwind
$familias = ['amber', 'indigo', 'blue', 'red', 'emerald', 'green', 'slate', 'gray', 'neutral', 'zinc', 'stone', 'sky', 'teal', 'purple'];

echo str_pad('pagina', 22) . "layout    " . str_pad('acento principal', 20) . "otros acentos\n";
echo str_repeat('-', 100) . "\n";

foreach ($paginas as $p) {
    $ch = curl_init("$base$p");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar]);
    $html = curl_exec($ch); curl_close($ch);
    if (!$html) { continue; }

    $layout = str_contains($html, 'bg-slate-900') ? 'sidebar' : (str_contains($html, 'bg-gray-100') ? 'guest' : '?');

    // Cuenta colores excluyendo los neutros (slate/gray/neutral/zinc/stone)
    $cuenta = [];
    foreach ($familias as $f) {
        if (in_array($f, ['slate', 'gray', 'neutral', 'zinc', 'stone'], true)) continue;
        $n = preg_match_all("/\b$f-\d{2,3}\b/", $html);
        if ($n > 0) $cuenta[$f] = $n;
    }
    arsort($cuenta);
    $principal = array_key_first($cuenta) ?: '-';
    $otros = [];
    foreach ($cuenta as $f => $n) { if ($f !== $principal) $otros[] = "$f($n)"; }

    printf("%-22s %-9s %-19s %s\n", $p, $layout, "$principal", implode(' ', $otros));
}

echo "\n=== Componentes Breeze: que color usan ===\n";
$dir = __DIR__ . '/../resources/views/components';
foreach (glob("$dir/*.blade.php") as $f) {
    $c = file_get_contents($f);
    preg_match_all('/\b(indigo|amber|blue|red|gray|slate)-(\d{2,3})\b/', $c, $m);
    $cuenta = array_count_values($m[1]);
    if ($cuenta) {
        arsort($cuenta);
        $top = array_slice(array_keys($cuenta), 0, 3);
        printf("  %-26s %s\n", basename($f), implode(', ', array_map(fn($k) => "$k(" . $cuenta[$k] . ")", $top)));
    }
}

echo "\n=== focus rings de los inputs (lo que se ve al escribir) ===\n";
foreach (glob("$dir/*.blade.php") as $f) {
    $c = file_get_contents($f);
    if (preg_match('/focus:ring-(\w+)-/', $c, $m)) {
        printf("  %-26s focus:ring-%s\n", basename($f), $m[1]);
    }
}
