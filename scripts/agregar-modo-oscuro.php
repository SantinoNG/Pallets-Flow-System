<?php

// Agrega las variantes `dark:` a las clases de las vistas Blade.
//
// Cubre las dos formas en que aparecen las clases:
//   1. class="..."                        (HTML normal)
//   2. 'class' => '...'  /  "class" => "..."   (arrays PHP, tipico de Breeze)
//
// Es idempotente: si la variante oscura ya esta, no la agrega de nuevo.
//
// Las vistas de PDF se saltan: se imprimen en blanco.
//
// Uso: php agregar-modo-oscuro.php

$raiz = __DIR__ . '/../resources/views';

// clase clara  =>  clase oscura (una sola)
$mapa = [
    // superficies
    'bg-white'             => 'dark:bg-zinc-900',
    'bg-gray-50'           => 'dark:bg-zinc-950',
    'bg-gray-100'          => 'dark:bg-zinc-950',
    'bg-gray-200'          => 'dark:bg-zinc-800',
    'bg-zinc-50'           => 'dark:bg-zinc-950',
    'bg-zinc-100'          => 'dark:bg-zinc-950',
    'bg-zinc-200'          => 'dark:bg-zinc-800',

    // texto
    'text-gray-900'        => 'dark:text-zinc-100',
    'text-gray-800'        => 'dark:text-zinc-100',
    'text-gray-700'        => 'dark:text-zinc-200',
    'text-gray-600'        => 'dark:text-zinc-300',
    'text-gray-500'        => 'dark:text-zinc-400',
    'text-gray-400'        => 'dark:text-zinc-500',
    'placeholder-gray-400' => 'dark:placeholder-zinc-500',
    'text-zinc-900'        => 'dark:text-zinc-100',
    'text-zinc-800'        => 'dark:text-zinc-100',
    'text-zinc-700'        => 'dark:text-zinc-200',
    'text-zinc-600'        => 'dark:text-zinc-300',
    'text-zinc-500'        => 'dark:text-zinc-300',

    // bordes
    'border-gray-100'      => 'dark:border-zinc-800',
    'border-gray-200'      => 'dark:border-zinc-800',
    'border-gray-300'      => 'dark:border-zinc-700',
    'border-gray-400'      => 'dark:border-zinc-600',
    'border-zinc-200'      => 'dark:border-zinc-800',
    'border-zinc-300'      => 'dark:border-zinc-700',

    // divisores
    'divide-gray-100'      => 'dark:divide-zinc-800',
    'divide-gray-200'      => 'dark:divide-zinc-800',
    'divide-zinc-200'      => 'dark:divide-zinc-800',

    // estados hover
    'hover:bg-gray-50'     => 'dark:hover:bg-zinc-800',
    'hover:bg-gray-100'    => 'dark:hover:bg-zinc-800',
    'hover:bg-gray-200'    => 'dark:hover:bg-zinc-700',
    'hover:text-gray-900'  => 'dark:hover:text-zinc-100',
    'hover:text-gray-700'  => 'dark:hover:text-zinc-200',
    'hover:text-gray-600'  => 'dark:hover:text-zinc-300',
];

/**
 * Agrega las variantes oscuras a una lista de clases.
 */
function agregarOscuras(string $clases, array $mapa): string
{
    $tokens = preg_split('/\s+/', trim($clases));
    $out = [];

    foreach ($tokens as $t) {
        if ($t === '') continue;
        $out[] = $t;

        if (!isset($mapa[$t])) continue;

        $oscura = $mapa[$t];
        if (in_array($oscura, $tokens, true)) continue;   // ya esta
        $out[] = $oscura;
    }

    return implode(' ', $out);
}

$archivos = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raiz, FilesystemIterator::SKIP_DOTS));
$total = 0;
$cambiados = 0;

foreach ($archivos as $f) {
    if (!$f->isFile() || $f->getExtension() !== 'php') continue;

    $ruta = $f->getPathname();
    $rel = str_replace($raiz . '/', '', $ruta);

    if (str_contains($rel, '/pdf.blade.php')) continue;   // se imprimen

    $crudo = file_get_contents($ruta);
    $original = $crudo;

    // 1) class="..."
    $crudo = preg_replace_callback('/class="([^"]*)"/', function ($m) use ($mapa, &$total) {
        $nuevo = agregarOscuras($m[1], $mapa);
        $total += substr_count($nuevo, 'dark:') - substr_count($m[1], 'dark:');
        return 'class="' . $nuevo . '"';
    }, $crudo);

    // 2) 'class' => '...'   y   "class" => "..."
    $crudo = preg_replace_callback(
        "/('class'\\s*=>\\s*')([^']*)(')|(\"class\"\\s*=>\\s*\")([^\"]*)(\")/",
        function ($m) use ($mapa, &$total) {
            if (isset($m[2]) && $m[2] !== '') {
                $nuevo = agregarOscuras($m[2], $mapa);
                $total += substr_count($nuevo, 'dark:') - substr_count($m[2], 'dark:');
                return $m[1] . $nuevo . $m[3];
            }
            $nuevo = agregarOscuras($m[5], $mapa);
            $total += substr_count($nuevo, 'dark:') - substr_count($m[5], 'dark:');
            return $m[4] . $nuevo . $m[6];
        },
        $crudo
    );

    if ($crudo !== $original) {
        file_put_contents($ruta, $crudo);
        $cambiados++;
    }
}

echo "  $cambiados archivos actualizados, $total variantes dark agregadas\n";
