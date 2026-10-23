<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Proveedores</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .filters { font-size: 10px; color: #555; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Proveedores - Pallet Flow System</h1>
        <p>Generado el: {{ now()->setTimezone('America/Argentina/Buenos_Aires')->format('d/m/Y H:i') }}</p>
    </div>

    <div class="filters">
        <strong>Filtros aplicados:</strong><br>
        Empresa: {{ request('company_name', 'Todas') }} | 
        Materia Prima: {{ request('raw_material', 'Todas') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Empresa</th>
                <th>Correo Electrónico</th>
                <th>Teléfono / WhatsApp</th>
                <th>Materias Primas Suministradas</th>
            </tr>
        </thead>
        <tbody>
            @forelse($suppliers as $supplier)
                <tr>
                    <td>{{ $supplier->company_name }}</td>
                    <td>{{ $supplier->supplier_email }}</td>
                    <td>{{ $supplier->supplier_phone }}</td>
                    <td>{{ $supplier->rawMaterials->map(fn ($material) => $material->description . ' (' . $material->etiquetaUnidad(2) . ')')->join(', ') ?: 'Sin registrar' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center;">No se encontraron proveedores con los filtros aplicados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>