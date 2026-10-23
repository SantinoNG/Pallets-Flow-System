<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Materias Primas</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; margin-bottom: 18px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 17px; text-transform: uppercase; }
        .filters { font-size: 9px; color: #555; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f4f4f4; font-weight: bold; font-size: 9px; text-transform: uppercase; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .num { text-align: right; }
        .bajo { color: #b91c1c; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Materias Primas - Pallets Flow</h1>
        <p>Generado el: {{ now()->setTimezone('America/Argentina/Buenos_Aires')->format('d/m/Y H:i') }}</p>
    </div>

    <div class="filters">
        <strong>Filtros aplicados:</strong><br>
        Materia prima: {{ request('search', 'Todas') }} |
        Proveedor: {{ $proveedorFiltro ?? 'Todos' }} |
        Presentación: {{ request('unit') ? (\App\Models\RawMaterial::UNIDADES[request('unit')][1] ?? request('unit')) : 'Todas' }} |
        Solo bajo mínimo: {{ request('low_stock') === '1' ? 'Sí' : 'No' }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Materia prima</th>
                <th>Presentación</th>
                <th class="num">Stock</th>
                <th class="num">Mínimo</th>
                <th>Proveedor</th>
                <th class="num">Costo unitario</th>
                <th class="num">Valor</th>
                <th class="num">Productos</th>
            </tr>
        </thead>
        <tbody>
            @forelse($materiales as $material)
                @php
                    $stock = (float) ($material->stock->remaining_quantity ?? 0);
                    $minimo = (float) ($material->stock->minimum_stock ?? 0);
                    $bajo = $stock <= $minimo;
                @endphp
                <tr>
                    <td>{{ $material->description }}</td>
                    <td>{{ $material->etiquetaUnidad(2) }}</td>
                    <td class="num {{ $bajo ? 'bajo' : '' }}">{{ $material->cantidad($stock) }}</td>
                    <td class="num">{{ $material->cantidad($minimo) }}</td>
                    <td>{{ $material->supplier?->company_name ?? '—' }}</td>
                    <td class="num">${{ number_format($material->unit_cost, 2) }}</td>
                    <td class="num">${{ number_format($stock * (float) $material->unit_cost, 2) }}</td>
                    <td class="num">{{ $material->articles_count }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center;">No se encontraron materias primas con los filtros aplicados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
