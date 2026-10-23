<x-app-layout>
    {{-- Para el Cliente, una venta es su COMPRA: cambia el titulo y
         se oculta el vendedor (en una compra propia no hay). El flag
         $esCliente lo pasa el controlador (SaleController@show). --}}

    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            {{-- El comprobante no muestra el id interno de la venta:
                 muestra su numero fiscal. --}}
            <h2 class="font-semibold text-xl text-gray-800 dark:text-zinc-100 leading-tight">{{ $esCliente ? 'Compra' : 'Venta' }}@if($sale->receipt) · {{ $sale->receipt->etiqueta() }}@endif</h2>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('sales.pdf', $sale->id_sale) }}"
                   class="inline-flex items-center px-3 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 focus:bg-red-700 active:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-900 transition">
                    Descargar comprobante
                </a>
                <a href="{{ route('sales.index') }}"
                   class="inline-flex items-center px-3 py-2 bg-gray-200 dark:bg-zinc-800 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-zinc-200 uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-hazard-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-900 transition">
                    {{ $esCliente ? 'Ver mis compras' : 'Volver' }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Datos generales --}}
            <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-{{ $sale->employee ? 4 : 3 }} gap-6">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase mb-1">Fecha</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-zinc-100">
                            {{ $sale->created_at?->format('d/m/Y H:i') ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase mb-1">Forma de pago</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-zinc-100">{{ $sale->payment_method }}</p>
                        {{-- Si se pago con varios medios, el detalle con
                             importes va debajo del resumen. --}}
                        @if($sale->payments->count() > 1)
                            <ul class="mt-1 space-y-0.5">
                                @foreach($sale->payments as $pago)
                                    <li class="text-xs text-gray-500 dark:text-zinc-400">
                                        {{ $pago->payment_method }}: ${{ number_format($pago->amount, 2, ',', '.') }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    {{-- El campo aparece solo si hay un vendedor. En una
                         compra que el Cliente hizo por su cuenta no hay, y
                         una etiqueta vacia confunde. Y el Cliente tampoco lo
                         necesita: a el le alcanza con su compra. --}}
                    @if ($sale->employee && ! $esCliente)
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase mb-1">Empleado</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-zinc-100">
                                {{ $sale->employee->first_name }} {{ $sale->employee->last_name }}
                            </p>
                        </div>
                    @endif
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase mb-1">Total</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-zinc-100">
                            ${{ number_format($sale->total(), 2, ',', '.') }}
                        </p>
                        @if($sale->receipt)
                            @php $desglose = $sale->receipt->desglose(); @endphp
                            <p class="text-xs text-gray-500 dark:text-zinc-400">
                                Neto ${{ number_format($desglose['neto'], 2, ',', '.') }}
                                + IVA {{ \App\Support\Iva::etiqueta($desglose['tasa']) }}
                                ${{ number_format($desglose['iva'], 2, ',', '.') }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Cliente --}}
            <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-bold text-gray-800 dark:text-zinc-100 uppercase tracking-wider mb-4">Cliente</h3>
                @if($sale->customer)
                    <div class="bg-gray-50 dark:bg-zinc-950 rounded-md p-4">
                        <p class="text-sm font-medium text-gray-900 dark:text-zinc-100">
                            {{ $sale->customer->first_name }} {{ $sale->customer->last_name }}
                        </p>
                        {{-- El email del cliente solo lo ve quien gestiona
                             las fichas (Jefe y Administrador). --}}
                        @if ($veEmails)
                            <p class="text-sm text-gray-600 dark:text-zinc-300">{{ $sale->customer->user?->email }}</p>
                        @endif
                        @if($sale->customer->phone)
                            <p class="text-sm text-gray-600 dark:text-zinc-300">{{ $sale->customer->phone }}</p>
                        @endif
                        @if($sale->customer->address)
                            <p class="text-sm text-gray-600 dark:text-zinc-300">{{ $sale->customer->address }}</p>
                        @endif
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-zinc-400">Sin cliente asociado.</p>
                @endif
            </div>

            {{-- Artículos vendidos --}}
            <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-800">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-zinc-100 uppercase tracking-wider">
                        {{ $esCliente ? 'Artículos comprados' : 'Artículos vendidos' }}
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-gray-600 dark:text-zinc-300">
                        <thead class="text-xs text-gray-700 dark:text-zinc-200 uppercase bg-gray-50 dark:bg-zinc-950">
                            <tr>
                                <th class="px-4 py-3 w-20">Foto</th>
                                <th class="px-4 py-3">Artículo</th>
                                <th class="px-4 py-3 text-center">Cant.</th>
                                <th class="px-4 py-3 text-right">Precio unit.</th>
                                <th class="px-4 py-3 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sale->cart?->items ?? [] as $item)
                                <tr class="border-b border-gray-100 dark:border-zinc-800">
                                    <td class="px-4 py-3">
                                        <x-article-image :article="$item->article" size="sm" />
                                    </td>
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-zinc-100">
                                        {{ $item->article?->article_name ?? 'Artículo eliminado' }}
                                    </td>
                                    <td class="px-4 py-3 text-center font-mono tabular-nums">{{ $item->quantity }}</td>
                                    <td class="px-4 py-3 text-right font-mono tabular-nums">
                                        ${{ number_format((float) $item->unit_price, 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-zinc-100 font-mono tabular-nums">
                                        ${{ number_format((float) $item->unit_price * $item->quantity, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-zinc-400">
                                        {{ $esCliente ? 'Esta compra no tiene items en el carrito.' : 'Esta venta no tiene items en el carrito.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(($sale->cart?->items ?? [])->isNotEmpty())
                            <tfoot class="bg-gray-50 dark:bg-zinc-950 font-semibold text-gray-900 dark:text-zinc-100">
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-right">Total</td>
                                    <td class="px-4 py-3 text-right font-mono tabular-nums">
                                        ${{ number_format($sale->total(), 2, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            {{-- Comprobante --}}
            @if($sale->receipt)
                <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-zinc-100 uppercase tracking-wider mb-4">Comprobante</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase">Número</p>
                            <p class="text-gray-900 dark:text-zinc-100 font-mono">{{ $sale->receipt->numeroComprobante() }}</p>
                            @if($sale->receipt->receipt_number)
                                <p class="text-xs text-gray-500 dark:text-zinc-400">
                                    Compra N.º {{ $sale->receipt->receipt_number }} del cliente
                                </p>
                            @endif
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase">Emitido</p>
                            <p class="text-gray-900 dark:text-zinc-100">{{ $sale->receipt->issue_date?->format('d/m/Y') }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase">Vence</p>
                            <p class="text-gray-900 dark:text-zinc-100">{{ $sale->receipt->due_date?->format('d/m/Y') }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase">Estado</p>
                            <span class="inline-flex items-center rounded-full bg-green-100 dark:bg-green-950/40 px-2 py-0.5 text-xs font-semibold text-green-700 dark:text-green-300">
                                Emitido
                            </span>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
