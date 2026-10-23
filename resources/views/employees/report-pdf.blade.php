<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $titulo }} - {{ $empleado->first_name }} {{ $empleado->last_name }}</title>
    <style>
        /* dompdf no entiende flex/grid: todo con tablas */
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; margin: 0; }
        h1 { font-size: 19px; margin: 0 0 2px; }
        h2 { font-size: 15px; margin: 16px 0 4px; color: #9a3412; }
        h3 { font-size: 11px; margin: 14px 0 5px; text-transform: uppercase; letter-spacing: .4px; color: #374151; }
        .muted { color: #6b7280; font-size: 10px; }
        .header { border-bottom: 2px solid #ea580c; padding-bottom: 10px; margin-bottom: 12px; }
        .right { text-align: right; }
        .center { text-align: center; }
        .box { background: #fff7ed; border: 1px solid #fed7aa; padding: 9px 11px; margin-bottom: 12px; }
        .label { font-size: 9px; text-transform: uppercase; color: #9a3412; margin-bottom: 2px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th { background: #f3f4f6; text-align: left; padding: 5px 6px; font-size: 8px;
             text-transform: uppercase; color: #374151; border-bottom: 1px solid #d1d5db; }
        td { padding: 4px 6px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        tr.bajo td { background: #fef2f2; }
        .ok { color: #166534; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 8px; margin-bottom: 8px; }
        .alerta { color: #991b1b; background: #fef2f2; border: 1px solid #fecaca; padding: 8px; margin-bottom: 8px; }
        .resumen { width: 330px; }
        .totales { width: 280px; margin-left: auto; }
        .totales td { border: none; padding: 3px 6px; }
        .totales .final { font-size: 13px; font-weight: bold; border-top: 2px solid #ea580c; }
        .footer { border-top: 1px solid #e5e7eb; margin-top: 18px; padding-top: 8px; font-size: 9px; color: #6b7280; }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td style="width: 58px; vertical-align: top;">
                    <img src="{{ public_path('images/logo-pdf.jpg') }}" style="width: 50px; border-radius: 4px;" alt="Pallets Flow">
                </td>
                <td>
                    <h1>Pallets Flow</h1>
                    <div class="muted">Muebles de pallet · Diseño natural</div>
                    <div class="muted">Reporte interno</div>
                </td>
                <td class="right">
                    <div class="label">Emitido</div>
                    <div>{{ now()->setTimezone('America/Argentina/Buenos_Aires')->format('d/m/Y H:i') }} hs</div>
                    @if($tipo === 'vendedor' || $tipo === 'encargado' || $tipo === 'general')
                        <div class="muted">Ventas desde {{ $desde->format('d/m/Y') }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- Destinatario: el reporte se le manda a una persona concreta. --}}
    <div class="box">
        <table>
            <tr>
                <td style="width: 65%;">
                    <div class="label">Para</div>
                    <div><strong>{{ $empleado->first_name }} {{ $empleado->last_name }}</strong></div>
                    <div class="muted">{{ $rol }} · {{ $empleado->user->email ?? 'Sin usuario' }}</div>
                </td>
                <td class="right">
                    <div class="label">Teléfono</div>
                    <div>{{ $empleado->phone ?? '—' }}</div>
                    <div class="muted">Reporte {{ strtoupper($tipo) }}</div>
                </td>
            </tr>
        </table>
    </div>

    <h2>{{ $titulo }}</h2>
    <p class="muted">{{ $bajada }}</p>

    {{-- ------------------------------------------------------------------ --}}
    {{--  DEPOSITO / ENCARGADO: pedido de reposición                          --}}
    {{-- ------------------------------------------------------------------ --}}
    @if($tipo === 'deposito' || $tipo === 'encargado')
        <h3>Materias primas</h3>

        @if($reposicion['cantidad'] === 0)
            <div class="ok">
                No hay materias primas por debajo del mínimo. El depósito está al día.
            </div>
        @else
            <div class="alerta">
                <strong>{{ $reposicion['cantidad'] }}</strong> insumo(s) necesitan reposición
                (filas resaltadas). Total estimado del pedido:
                <strong>$ {{ number_format($reposicion['total'], 2, ',', '.') }}</strong>.
            </div>
        @endif

        <table>
            <thead>
                <tr>
                    <th style="width: 30%;">Insumo</th>
                    <th style="width: 13%;">Presentación</th>
                    <th style="width: 8%;" class="center">Stock</th>
                    <th style="width: 8%;" class="center">Mínimo</th>
                    <th style="width: 23%;">Proveedor</th>
                    <th style="width: 9%;" class="right">Costo unit.</th>
                    <th style="width: 9%;" class="right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($insumos as $fila)
                    <tr class="{{ $fila['bajo'] ? 'bajo' : '' }}">
                        <td>{{ $fila['material']->description }}</td>
                        <td>{{ $fila['material']->etiquetaUnidad(2) }}</td>
                        <td class="center">{{ number_format($fila['stock'], 0) }}</td>
                        <td class="center">{{ number_format($fila['minimo'], 0) }}</td>
                        <td>{{ $fila['material']->supplier->company_name ?? '—' }}</td>
                        <td class="right">$ {{ number_format($fila['material']->unit_cost, 2, ',', '.') }}</td>
                        <td class="right">
                            {{ $fila['bajo'] ? '$ ' . number_format($fila['subtotal'], 2, ',', '.') : '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if($reposicion['cantidad'] > 0)
            <table class="totales">
                <tr>
                    <td>Insumos a pedir</td>
                    <td class="right">{{ $reposicion['cantidad'] }}</td>
                </tr>
                <tr class="final">
                    <td>Total estimado</td>
                    <td class="right">$ {{ number_format($reposicion['total'], 2, ',', '.') }}</td>
                </tr>
            </table>
            <p class="muted">
                En las filas resaltadas, el subtotal es el costo estimado de la cantidad
                sugerida para volver por encima del mínimo. El total es estimado con el
                costo unitario cargado en cada materia prima; confirmá el precio con el
                proveedor antes de comprar.
            </p>
        @endif
    @endif

    {{-- ------------------------------------------------------------------ --}}
    {{--  VENDEDOR / ENCARGADO / GENERAL: ventas del período                  --}}
    {{-- ------------------------------------------------------------------ --}}
    @if($tipo === 'vendedor' || $tipo === 'encargado' || $tipo === 'general')
        <h3>Ventas de los últimos {{ $dias }} días</h3>
        <table class="resumen">
            <tr>
                <td>Operaciones</td>
                <td class="right">{{ $ventas['cantidad'] }}</td>
            </tr>
            <tr>
                <td>Total vendido</td>
                <td class="right">$ {{ number_format($ventas['total'], 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Ticket promedio</td>
                <td class="right">$ {{ number_format($ventas['promedio'], 2, ',', '.') }}</td>
            </tr>
        </table>

        @if($ventasEmpleado['cantidad'] > 0)
            <p class="muted">
                Ventas registradas por {{ $empleado->first_name }}:
                {{ $ventasEmpleado['cantidad'] }} operación(es) por
                $ {{ number_format($ventasEmpleado['total'], 2, ',', '.') }}.
            </p>
        @endif

        @if($masVendidos->isNotEmpty())
            <h3>Productos más vendidos</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 60%;">Producto</th>
                        <th style="width: 18%;" class="center">Unidades</th>
                        <th style="width: 22%;" class="right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($masVendidos as $producto)
                        <tr>
                            <td>{{ $producto->article_name }}</td>
                            <td class="center">{{ number_format($producto->unidades, 0) }}</td>
                            <td class="right">$ {{ number_format($producto->total, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif

    {{-- ------------------------------------------------------------------ --}}
    {{--  DISPONIBILIDAD DEL CATÁLOGO                                        --}}
    {{-- ------------------------------------------------------------------ --}}
    @if($tipo === 'deposito' || $tipo === 'vendedor' || $tipo === 'encargado')
        <h3>Artículos y disponibilidad</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 50%;">Artículo</th>
                    <th style="width: 15%;" class="center">Stock</th>
                    <th style="width: 15%;" class="center">Mínimo</th>
                    <th style="width: 20%;" class="center">Faltan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($articulos as $fila)
                    <tr class="{{ $fila['bajo'] ? 'bajo' : '' }}">
                        <td>{{ $fila['articulo']->article_name }}</td>
                        <td class="center">{{ $fila['stock'] }}</td>
                        <td class="center">{{ $fila['minimo'] }}</td>
                        <td class="center">
                            {{ $fila['bajo'] ? $fila['faltante'] : '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- ------------------------------------------------------------------ --}}
    {{--  GENERAL: números del negocio y equipo                               --}}
    {{-- ------------------------------------------------------------------ --}}
    @if($tipo === 'general')
        <h3>Números del negocio</h3>
        <table class="resumen">
            <tr><td>Artículos en catálogo</td><td class="right">{{ $totales['articulos'] }}</td></tr>
            <tr><td>Materias primas</td><td class="right">{{ $totales['insumos'] }}</td></tr>
            <tr><td>Proveedores</td><td class="right">{{ $totales['proveedores'] }}</td></tr>
            <tr><td>Empleados</td><td class="right">{{ $totales['empleados'] }}</td></tr>
            <tr><td>Clientes</td><td class="right">{{ $totales['clientes'] }}</td></tr>
            <tr><td>Ventas históricas</td><td class="right">{{ $totales['ventas'] }}</td></tr>
            <tr><td>Facturado histórico</td><td class="right">$ {{ number_format($totales['facturado'], 2, ',', '.') }}</td></tr>
        </table>

        <h3>Empleados por rol</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 70%;">Rol</th>
                    <th style="width: 30%;" class="center">Cantidad</th>
                </tr>
            </thead>
            <tbody>
                @foreach($porRol as $fila)
                    <tr>
                        <td>{{ $fila->role_name }}</td>
                        <td class="center">{{ $fila->total }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h3>Estado del stock</h3>
        <table class="resumen">
            <tr>
                <td>Insumos bajo mínimo</td>
                <td class="right">{{ $reposicion['cantidad'] }}</td>
            </tr>
            <tr>
                <td>Costo estimado de reposición</td>
                <td class="right">$ {{ number_format($reposicion['total'], 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Artículos bajo mínimo</td>
                <td class="right">{{ $articulos->where('bajo', true)->count() }}</td>
            </tr>
            <tr>
                <td>Artículos sin stock</td>
                <td class="right">{{ $articulos->where('sinStock', true)->count() }}</td>
            </tr>
        </table>
    @endif

    <div class="footer">
        <table>
            <tr>
                <td>
                    Generado por {{ auth()->user()->name ?? 'Pallets Flow' }} ·
                    {{ now()->setTimezone('America/Argentina/Buenos_Aires')->format('d/m/Y H:i') }} hs
                </td>
                <td class="right">Recibí conforme: ______________________________</td>
            </tr>
        </table>
    </div>

</body>
</html>
