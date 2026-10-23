<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Comprobante de {{ $esCliente ? 'Compra' : 'Venta' }}{{ $sale->receipt ? ' N° ' . $sale->receipt->numeroComprobante() : '' }}</title>
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

        /* Marca de agua: la marca de la empresa, difuminada y por detras
           del contenido. dompdf entiende position:absolute y opacity. */
        .marca-agua {
            position: absolute;
            top: 300px;
            left: 50%;
            width: 420px;
            margin-left: -210px;
            opacity: 0.07;
            z-index: -1;
        }
    </style>
</head>
<body>

    @php
        /*
         * El comprobante no muestra el id de la venta (numero interno).
         * Muestra el numero fiscal: punto de venta + correlativo del
         * cliente, y el desglose de IVA guardado al emitir.
         */
        $desglose = $sale->receipt?->desglose() ?? \App\Support\Iva::desglosar($sale->total());
        $numeroCompra = $sale->receipt?->receipt_number;
    @endphp

    {{-- Marca de agua de la marca --}}
    <img src="{{ public_path('images/marca-agua.jpg') }}" class="marca-agua" alt="">

    <div class="header">
        <table>
            <tr>
                <td style="width: 62px; vertical-align: top;">
                    <img src="{{ public_path('images/logo-pdf.jpg') }}" style="width: 54px; border-radius: 4px;" alt="Pallets Flow">
                </td>
                <td>
                    <h1>Pallets Flow</h1>
                    <div class="muted">Muebles de pallet · Diseño natural</div>
                    <div class="muted">Responsable Inscripto · IVA 21%</div>
                </td>
                <td class="right font-mono tabular-nums">
                    <div class="label">Comprobante N°</div>
                    <div><strong>{{ $sale->receipt?->numeroComprobante() ?? '—' }}</strong></div>
                    @if($numeroCompra)
                        <div class="muted">Compra N.º {{ $numeroCompra }} del cliente</div>
                    @endif
                    <div class="muted">Emitido: {{ $sale->created_at?->format('d/m/Y H:i') ?? '—' }}</div>
                    @if($sale->receipt)
                        <div class="muted">Vence: {{ $sale->receipt->due_date?->format('d/m/Y') }}</div>
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
                        {{-- El email del cliente solo lo ve quien gestiona las
                             fichas (Jefe y Administrador). --}}
                        @if($veEmails)
                            {{ $sale->customer->user?->email }}<br>
                        @endif
                        @if($sale->customer->phone) {{ $sale->customer->phone }}<br> @endif
                        @if($sale->customer->address) {{ $sale->customer->address }} @endif
                    @else
                        <span class="muted">Sin cliente</span>
                    @endif
                </div>
            </td>
            <td style="width: 50%; vertical-align: top;">
                <div class="box">
                    {{-- El Cliente no ve al personal que lo atendio: a el
                         le alcanza con su compra. --}}
                    @unless($esCliente)
                        <div class="label">Atendido por</div>
                        @if($sale->employee)
                            <strong>{{ $sale->employee->first_name }} {{ $sale->employee->last_name }}</strong><br>
                            {{ $sale->employee->phone ?? '' }}
                        @else
                            <span class="muted">Sin empleado</span>
                        @endif
                    @endunless
                    <div class="label" style="margin-top:8px;">Forma de pago</div>
                    @forelse($sale->payments as $pago)
                        <div>{{ $pago->etiqueta() }}: <strong>$ {{ number_format($pago->amount, 2, ',', '.') }}</strong></div>
                    @empty
                        <strong>{{ $sale->payment_method }}</strong>
                    @endforelse
                    @if($sale->payments->count() > 1)
                        <div class="muted">Pago combinado</div>
                    @endif
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
                    <td class="center font-mono tabular-nums">{{ $item->quantity }}</td>
                    <td class="right font-mono tabular-nums">$ {{ number_format((float) $item->unit_price, 2, ',', '.') }}</td>
                    <td class="right font-mono tabular-nums">$ {{ number_format((float) $item->unit_price * $item->quantity, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="center muted">Esta {{ $esCliente ? 'compra' : 'venta' }} no tiene items en el carrito.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="muted">Artículos</td>
            <td class="right font-mono tabular-nums">{{ $sale->unidades() }}</td>
        </tr>
        <tr>
            <td class="muted">Subtotal (neto)</td>
            <td class="right font-mono tabular-nums">$ {{ number_format($desglose['neto'], 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="muted">IVA {{ \App\Support\Iva::etiqueta($desglose['tasa']) }}</td>
            <td class="right font-mono tabular-nums">$ {{ number_format($desglose['iva'], 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="final">Total</td>
            <td class="right final font-mono tabular-nums">$ {{ number_format($desglose['total'], 2, ',', '.') }}</td>
        </tr>
    </table>

    <div class="footer">
        Documento generado automáticamente por Pallets Flow System.
        Gracias por su compra.
    </div>

</body>
</html>
