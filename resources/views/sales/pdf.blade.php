<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Comprobante de Venta #{{ $sale->id_sale }}</title>
    <style>
        /* dompdf no entiende CSS moderno: nada de flex/grid, solo tablas */
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; margin: 0; }
        h1 { font-size: 20px; margin: 0 0 2px; }
        .muted { color: #6b7280; font-size: 11px; }
        .header { border-bottom: 2px solid #d97706; padding-bottom: 12px; margin-bottom: 18px; }
        .right { text-align: right; }
        .box { background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px; margin-bottom: 16px; }
        .label { font-size: 10px; text-transform: uppercase; color: #6b7280; margin-bottom: 2px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th { background: #f3f4f6; text-align: left; padding: 8px; font-size: 10px;
             text-transform: uppercase; color: #374151; border-bottom: 1px solid #d1d5db; }
        td { padding: 8px; border-bottom: 1px solid #f3f4f6; }
        .center { text-align: center; }
        .totals { margin-left: auto; width: 260px; }
        .totals td { border: none; padding: 4px 8px; }
        .totals .final { font-size: 15px; font-weight: bold; border-top: 2px solid #d97706; }
        .footer { border-top: 1px solid #e5e7eb; margin-top: 24px; padding-top: 10px; font-size: 10px; color: #6b7280; }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td>
                    <h1>PalletStore</h1>
                    <div class="muted">Muebles artesanales de pallets</div>
                </td>
                <td class="right">
                    <div class="label">Comprobante</div>
                    <div><strong>#{{ $sale->id_sale }}</strong></div>
                    <div class="muted">Emitido: {{ $sale->created_at?->format('d/m/Y H:i') ?? '—' }}</div>
                    @if($sale->receipt)
                        <div class="muted">Vence: {{ $sale->receipt->due_date }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <table>
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <div class="box">
                    <div class="label">Cliente</div>
                    @if($sale->customer)
                        <strong>{{ $sale->customer->first_name }} {{ $sale->customer->last_name }}</strong><br>
                        {{ $sale->customer->user?->email }}<br>
                        @if($sale->customer->phone) {{ $sale->customer->phone }}<br> @endif
                        @if($sale->customer->address) {{ $sale->customer->address }} @endif
                    @else
                        <span class="muted">Sin cliente</span>
                    @endif
                </div>
            </td>
            <td style="width: 50%; vertical-align: top;">
                <div class="box">
                    <div class="label">Atendido por</div>
                    @if($sale->employee)
                        <strong>{{ $sale->employee->first_name }} {{ $sale->employee->last_name }}</strong><br>
                        {{ $sale->employee->phone ?? '' }}
                    @else
                        <span class="muted">Sin empleado</span>
                    @endif
                    <div class="label" style="margin-top:8px;">Medio de pago</div>
                    <strong>{{ $sale->payment_method }}</strong>
                </div>
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Artículo</th>
                <th class="center" style="width: 70px;">Cant.</th>
                <th class="right" style="width: 100px;">Precio unit.</th>
                <th class="right" style="width: 110px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sale->cart?->items ?? [] as $item)
                <tr>
                    <td>{{ $item->article?->article_name ?? 'Artículo eliminado' }}</td>
                    <td class="center">{{ $item->quantity }}</td>
                    <td class="right">$ {{ number_format((float) $item->unit_price, 2, ',', '.') }}</td>
                    <td class="right">$ {{ number_format((float) $item->unit_price * $item->quantity, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="center muted">Esta venta no tiene items en el carrito.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="muted">Artículos</td>
            <td class="right">{{ $sale->unidades() }}</td>
        </tr>
        <tr>
            <td class="final">Total</td>
            <td class="right final">$ {{ number_format($sale->total(), 2, ',', '.') }}</td>
        </tr>
    </table>

    <div class="footer">
        Documento generado automáticamente por Pallets Flow System.
        Gracias por su compra.
    </div>

</body>
</html>
