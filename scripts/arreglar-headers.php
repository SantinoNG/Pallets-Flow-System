<?php

// Agrega flex-wrap y separacion a los headers que tienen un titulo y
// botones juntos.
//
// Sin flex-wrap, en pantallas angostas el titulo y los botones no entran:
// el flex los aplasta y el ultimo queda cortado fuera de la pantalla
// (se ve en el encabezado del detalle de venta: "Venta #39" pegado al
// boton rojo, y el segundo boton cortado).

$raiz = __DIR__ . '/../resources/views';

// Contenedores de header: "flex justify-between items-center ..." sin gap.
$patron = '/class="flex justify-between items-(center|start)([^"]*)"/';

$archivos = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raiz, FilesystemIterator::SKIP_DOTS));

$total = 0;
$cambiados = 0;

foreach ($archivos as $f) {
    if (! $f->isFile() || $f->getExtension() !== 'php') {
        continue;
    }

    $ruta = $f->getPathname();
    $rel = str_replace($raiz . '/', '', $ruta);

    // Las vistas de paginacion del vendor no se tocan.
    if (str_contains($rel, 'vendor/pagination/')) {
        continue;
    }

    $crudo = file_get_contents($ruta);
    $original = $crudo;

    $crudo = preg_replace_callback($patron, function ($m) use (&$total) {
        $total++;

        return 'class="flex flex-wrap justify-between items-' . $m[1] . ' gap-3' . $m[2] . '"';
    }, $crudo);

    if ($crudo !== $original) {
        file_put_contents($ruta, $crudo);
        $cambiados++;
        printf("  %-46s %d header(s)\n", $rel, $total);
        $total = 0;
    }
}

echo "\n  $cambiados archivos actualizados\n";
