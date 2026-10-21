<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"/>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px; }
        h1 { font-size: 20px; color: #1e1b4b; margin-bottom: 4px; }
        .subtitle { font-size: 11px; color: #666; margin-bottom: 20px; }
        .filters { background: #f3f4f6; padding: 10px 14px; border-radius: 6px; margin-bottom: 16px; font-size: 11px; }
        .filters strong { color: #374151; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        thead { background-color: #1e1b4b; color: white; }
        th { padding: 8px 10px; text-align: left; font-size: 11px; }
        td { padding: 7px 10px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
        tr:nth-child(even) { background-color: #f9fafb; }
        .badge-ok  { background: #d1fae5; color: #065f46; padding: 2px 8px; border-radius: 10px; }
        .badge-low { background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 10px; }
        .total-row { font-weight: bold; background: #f3f4f6; }
        .footer { margin-top: 30px; font-size: 10px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>

    <h1>Pallets Flow — Reporte de Artículos</h1>
    <p class="subtitle">Generado el {{ now()->format('d/m/Y H:i') }}</p>

    {{-- Filtros aplicados --}}
    @if(array_filter($filters))
    <div class="filters">
        <strong>Filtros aplicados:</strong>
        @if(!empty($filters['search'])) Nombre: "{{ $filters['search'] }}" &nbsp; @endif
        @if(!empty($filters['price_min'])) Precio mín: ${{ $filters['price_min'] }} &nbsp; @endif
        @if(!empty($filters['price_max'])) Precio máx: ${{ $filters['price_max'] }} &nbsp; @endif
        @if(!empty($filters['low_stock'])) Solo stock bajo &nbsp; @endif
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Artículo</th>
                <th>Descripción</th>
                <th>Precio unit.</th>
                <th>Stock actual</th>
                <th>Stock mínimo</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($articles as $article)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td><strong>{{ $article->article_name }}</strong></td>
                <td>{{ $article->description ?? '—' }}</td>
                <td>${{ number_format($article->unit_price, 2) }}</td>
                <td>{{ $article->stock->remaining_quantity ?? 0 }}</td>
                <td>{{ $article->stock->minimum_stock ?? 0 }}</td>
                <td>
                    @if($article->stock && $article->stock->remaining_quantity <= $article->stock->minimum_stock)
                        <span class="badge-low">Stock bajo</span>
                    @else
                        <span class="badge-ok">Normal</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align:center; color:#9ca3af; padding: 20px;">
                    No se encontraron artículos con los filtros aplicados.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer">
        Total: {{ $articles->count() }} artículo(s) &nbsp;|&nbsp; Pallets Flow © {{ now()->year }}
    </p>

</body>
</html>