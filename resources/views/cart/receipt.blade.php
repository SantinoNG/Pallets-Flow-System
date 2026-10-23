<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-zinc-100 leading-tight">Comprobante de {{ $esCliente ? 'Compra' : 'Venta' }}@if($sale->receipt) N° {{ $sale->receipt->numeroComprobante() }}@endif</h2>
    </x-slot>

    @php
        /*
         * El comprobante no muestra el id de la venta: ese numero es
         * interno. Lo que se ve es el numero fiscal (punto de venta +
         * correlativo del cliente) y el desglose de IVA guardado al
         * emitir.
         */
        $desglose = $desglose ?? ($sale->receipt?->desglose() ?? \App\Support\Iva::desglosar((float) $subtotal));
        $numeroCompra = $sale->receipt?->receipt_number;
    @endphp

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            {{--
                Marca de agua de la marca: la misma que el comprobante en
                PDF, difuminada y por detras del contenido. Se posiciona
                en absoluto y el contenido va con position:relative para
                quedar arriba.
            --}}
            <div class="relative">
                <img src="{{ asset('images/marca.png') }}"
                     alt=""
                     aria-hidden="true"
                     class="pointer-events-none absolute left-1/2 top-1/2 -z-0 w-[85%] -translate-x-1/2 -translate-y-1/2 opacity-[0.06] select-none">

                <div class="relative z-10 bg-white dark:bg-zinc-900 overflow-hidden shadow-sm sm:rounded-lg p-8 border-b border-gray-200 dark:border-zinc-800">

                {{-- Alerta de éxito --}}
                @if(session('success'))
                    <div class="mb-6 px-4 py-3 bg-green-100 dark:bg-green-950/40 text-green-800 dark:text-green-300 text-sm rounded-md">
                        ✅ {{ session('success') }}
                    </div>
                @endif

                {{-- Encabezado del comprobante --}}
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-3 mb-8">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('images/logo-marca.svg') }}"
                             alt="Pallets Flow"
                             class="h-12 w-12 rounded-md shrink-0">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-zinc-100">Pallets Flow</h3>
                            <p class="text-sm text-gray-500 dark:text-zinc-400">Muebles de pallet · Diseño natural</p>
                            <p class="text-xs text-gray-500 dark:text-zinc-400">Responsable Inscripto · IVA 21%</p>
                        </div>
                    </div>
                    <div class="sm:text-right">
                        <p class="text-sm font-semibold text-gray-900 dark:text-zinc-100">
                            @if($sale->receipt)
                                {{ $sale->receipt->etiqueta() }}
                            @else
                                Comprobante
                            @endif
                        </p>
                        {{-- El numero de comprobante es el correlativo del
                             cliente: su 1ra compra es la 1, la 2da la 2. --}}
                        @if($numeroCompra)
                            <p class="text-xs text-gray-500 dark:text-zinc-400">
                                Compra N.º {{ $numeroCompra }} del cliente
                            </p>
                        @endif
                        <p class="text-xs text-gray-500 dark:text-zinc-400">
                            Emitido: {{ $sale->receipt?->issue_date?->format('d/m/Y') ?? now()->format('d/m/Y') }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-zinc-400">
                            Vence: {{ $sale->receipt?->due_date?->format('d/m/Y') ?? now()->addDays(30)->format('d/m/Y') }}
                        </p>
                    </div>
                </div>

                {{-- Datos del cliente --}}
                <div class="bg-gray-50 dark:bg-zinc-950 rounded-md p-4 mb-6">
                    <p class="text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase mb-2">Cliente</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-zinc-100">
                        {{ $sale->customer->first_name }} {{ $sale->customer->last_name }}
                    </p>
                    {{-- El email del cliente solo lo ve quien gestiona las
                         fichas. Para el Cliente es su propio comprobante y
                         ya sabe cual es su email. --}}
                    @unless ($esCliente)
                        <p class="text-sm text-gray-600 dark:text-zinc-300">{{ $sale->customer->user->email ?? '' }}</p>
                    @endunless
                    <p class="text-sm text-gray-600 dark:text-zinc-300">{{ $sale->customer->phone ?? '' }}</p>
                </div>

                {{-- Detalle de productos --}}
                <div class="mb-6">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-zinc-400">
                        <thead class="text-xs text-gray-700 dark:text-zinc-200 uppercase bg-gray-50 dark:bg-zinc-950">
                            <tr>
                                <th class="px-4 py-3">Artículo</th>
                                <th class="px-4 py-3 text-center">Cant.</th>
                                <th class="px-4 py-3 text-right">Precio unit.</th>
                                <th class="px-4 py-3 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sale->cart->items as $item)
                            <tr class="border-t border-gray-100 dark:border-zinc-800">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-zinc-100">
                                    {{ $item->article->article_name }}
                                </td>
                                <td class="px-4 py-3 text-center font-mono tabular-nums">{{ $item->quantity }}</td>
                                <td class="px-4 py-3 text-right font-mono tabular-nums">
                                    ${{ number_format($item->unit_price, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-zinc-100 font-mono tabular-nums">
                                    ${{ number_format($item->unit_price * $item->quantity, 2) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Desglose de IVA. Los precios ya lo incluyen, asi que el
                     neto se calcula hacia atras y el IVA sale por diferencia. --}}
                <div class="border-t border-gray-200 dark:border-zinc-800 pt-4 mb-6 space-y-2">
                    <div class="flex justify-between text-sm text-gray-600 dark:text-zinc-300">
                        <span>Subtotal (neto)</span>
                        <span class="font-mono tabular-nums">${{ number_format($desglose['neto'], 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm text-gray-600 dark:text-zinc-300">
                        <span>IVA {{ \App\Support\Iva::etiqueta($desglose['tasa']) }}</span>
                        <span class="font-mono tabular-nums">${{ number_format($desglose['iva'], 2) }}</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold text-gray-900 dark:text-zinc-100 border-t border-gray-200 dark:border-zinc-800 pt-2">
                        <span>Total</span>
                        <span class="font-mono tabular-nums">${{ number_format($desglose['total'], 2) }}</span>
                    </div>
                </div>

                {{-- Forma de pago: un renglon por medio. Si hay mas de uno,
                     es un pago combinado. --}}
                <div class="bg-gray-50 dark:bg-zinc-950 rounded-md p-4 mb-6">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                        <p class="text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase">Forma de pago</p>
                        @if($sale->payments->count() > 1)
                            <span class="text-xs font-semibold uppercase tracking-wider px-2 py-0.5 rounded bg-hazard-100 text-hazard-800 dark:bg-hazard-900/40 dark:text-hazard-300">
                                Pago combinado
                            </span>
                        @endif
                    </div>

                    @forelse($sale->payments as $pago)
                        <div class="flex justify-between text-sm text-gray-600 dark:text-zinc-300">
                            <span>{{ $pago->etiqueta() }}</span>
                            <span class="font-mono tabular-nums">${{ number_format($pago->amount, 2) }}</span>
                        </div>
                    @empty
                        {{-- Ventas viejas sin detalle de pagos --}}
                        <div class="flex justify-between text-sm text-gray-600 dark:text-zinc-300">
                            <span>{{ $sale->payment_method }}</span>
                            <span class="font-mono tabular-nums">${{ number_format($desglose['total'], 2) }}</span>
                        </div>
                    @endforelse
                </div>

                {{-- Atendido por. El Cliente no lo necesita: el comprobante
                     es de su compra, no del personal que lo atendio. --}}
                @if($sale->employee && ! $esCliente)
                <p class="text-xs text-gray-400 dark:text-zinc-500 mb-6">
                    Atendido por: {{ $sale->employee->first_name }} {{ $sale->employee->last_name }}
                </p>
                @endif

                {{-- Acciones --}}
                <div class="flex items-center gap-4">
                    <a href="{{ $esCliente ? route('articles.index') : route('cart.index') }}"
                       class="bg-hazard-600 hover:bg-hazard-700 text-white text-sm font-medium px-4 py-2 rounded-md transition">
                        {{ $esCliente ? 'Seguir comprando' : 'Nueva venta' }}
                    </a>
                    <a href="{{ route('dashboard') }}"
                       class="text-sm text-gray-600 dark:text-zinc-300 bg-gray-100 dark:bg-zinc-950 hover:bg-gray-200 dark:hover:bg-zinc-700 px-4 py-2 rounded-md transition">
                        {{ $esCliente ? 'Ir a mi panel' : 'Ir al dashboard' }}
                    </a>
                </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
