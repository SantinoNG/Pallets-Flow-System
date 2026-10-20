<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Usuarios</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #333; padding: 10px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #1e3a8a; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 18px; text-transform: uppercase; color: #1e3a8a; }
        .filters { font-size: 10px; color: #475569; background-color: #f8fafc; padding: 8px 12px; border: 1px solid #e2e8f0; margin-bottom: 15px; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        th { background-color: #f1f5f9; font-weight: bold; font-size: 10px; text-transform: uppercase; color: #334155; }
        tr:nth-child(even) { background-color: #f8fafc; }
        .text-center { text-align: center; }
        .footer { margin-top: 18px; font-size: 9px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Usuarios - PalletStore</h1>
        <p>Generado el: {{ now()->setTimezone('America/Argentina/Buenos_Aires')->format('d/m/Y H:i') }} hs</p>
    </div>

    <div class="filters">
        <strong>Filtros aplicados:</strong><br>
        → <strong>Búsqueda:</strong> {{ request('search') ?: 'Todos' }} |
        → <strong>Rol:</strong> {{ request('id_role') ? (\App\Models\Role::find(request('id_role'))->role_name ?? 'Seleccionado') : 'Todos' }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 8%;" class="text-center">ID</th>
                <th style="width: 25%;">Nombre</th>
                <th style="width: 30%;">Correo</th>
                <th style="width: 20%;">Rol</th>
                <th style="width: 17%;">Verificado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr>
                    <td class="text-center">{{ $user->id_user }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->role?->role_name ?? '—' }}</td>
                    <td class="text-center">{{ $user->email_verified_at ? 'Sí' : 'No' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">No hay usuarios que coincidan con el filtro.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Total de usuarios: {{ $users->count() }} · Documento generado automáticamente por Pallets Flow System.
    </div>
</body>
</html>
