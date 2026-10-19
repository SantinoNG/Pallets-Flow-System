<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Empleados</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #333; margin: 0; padding: 10px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #1e3a8a; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 18px; text-transform: uppercase; color: #1e3a8a; }
        .filters { font-size: 10px; color: #475569; background-color: #f8fafc; padding: 8px 12px; border: 1px solid #e2e8f0; margin-bottom: 15px; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        th { background-color: #f1f5f9; font-weight: bold; font-size: 10px; text-transform: uppercase; color: #334155; }
        tr:nth-child(even) { background-color: #f8fafc; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Empleados - PalletStore</h1>
        <p>Generado el: {{ now()->setTimezone('America/Argentina/Buenos_Aires')->format('d/m/Y H:i') }} hs</p>
    </div>

    <div class="filters">
        <strong>Filtros aplicados:</strong><br>
        • <strong>Búsqueda:</strong> {{ request('search') ?: 'Todos' }} | 
        • <strong>Rol / Cargo:</strong> {{ request('id_role') ? (\App\Models\Role::find(request('id_role'))->role_name ?? 'Seleccionado') : 'Todos' }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 10%;" class="text-center">ID</th>
                <th style="width: 30%;">Nombre y Apellido</th>
                <th style="width: 20%;">Teléfono</th>
                <th style="width: 25%;">Correo (Usuario)</th>
                <th style="width: 15%;">Rol / Cargo</th>
            </tr>
        </thead>
        <tbody>
            @forelse($employees as $employee)
                <tr>
                    <td class="text-center">#{{ $employee->id_employee }}</td>
                    <td>{{ $employee->first_name }} {{ $employee->last_name }}</td>
                    <td>{{ $employee->phone }}</td>
                    <td>{{ $employee->user ? $employee->user->email : 'Sin usuario' }}</td>
                    <td>{{ $employee->role ? $employee->role->role_name : 'Sin rol' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center" style="padding: 15px; color: #888;">
                        No se encontraron empleados registrados con los filtros aplicados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>