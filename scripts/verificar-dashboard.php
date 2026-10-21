<?php

// Verifica los datos del dashboard para cada periodo, tal como los ve el
// usuario: se ejecuta el componente Livewire y se leen los datos que le
// pasa a la vista.

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Livewire\Dashboard;

foreach (array_keys(Dashboard::PERIODOS) as $periodo) {
    $componente = new Dashboard();
    $componente->periodo = $periodo;

    // El componente calcula todo en render() y se lo pasa a la vista.
    $vista = $componente->render();
    $datos = $vista->getData();

    $ventas = $datos['ventasMes'];

    echo '  ' . str_pad(Dashboard::PERIODOS[$periodo], 22) . "\n";
    echo '      meses con ventas: ' . count($ventas['labels']) . '  ->  '
        . implode(', ', $ventas['labels']) . "\n";
    echo '      total: $' . number_format($datos['totalVentas'], 2, ',', '.')
        . '   artículos: ' . $datos['articulosCount']
        . '   proveedores: ' . $datos['proveedoresCount'] . "\n";
    echo '      medios de pago: ' . count($datos['mediosPago']['labels'])
        . '   top productos: ' . count($datos['topProductos']['labels'])
        . '   artículos en stock: ' . count($datos['stockPorCategoria']['labels']) . "\n\n";
}
