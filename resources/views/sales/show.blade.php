<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Venta #{{ $sale->id_sale }}
            </h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('sales.pdf', $sale->id_sale) }}"
                   class="inline-flex items-center px-4 py-2 bg-amber-500 border border-transparent rounded-md
                          font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-600
                          focus:bg-amber-600 active:bg-amber-700 focus:outline-none focus:ring-2
                          focus:ring-amber-500 focus:ring-offset-2 transition">
                    Descargar comprobante
                </a>
                <a href="{{ route('sales.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md
                          font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300
                          focus:bg-gray-300 active:bg-gray-300 focus:outline-none focus:ring-2
                          focus:ring-indigo-500 focus:ring-offset-2 transition">
                    Volver
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Datos generales --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Fecha</p>
                        <p class="text-sm font-medium text-gray-900">
                            {{ $sale->created_at?->format('d/m/Y H:i') ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Medio de pago</p>
                        <p class="text-sm font-medium text-gray-900">{{ $sale->payment_method }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Empleado</p>
                        <p class="text-sm font-medium text-gray-900">
                            {{ $sale->employee?->first_name }} {{ $sale->employee?->last_name }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Total</p>
                        <p class="text-lg font-bold text-gray-900">
                            ${{ number_format($sale->total(), 2, ',', '.') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Cliente --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider mb-4">Cliente</h3>
                @if($sale->customer)
                    <div class="bg-gray-50 rounded-md p-4">
                        <p class="text-sm font-medium text-gray-900">
                            {{ $sale->customer->first_name }} {{ $sale->customer->last_name }}
                        </p>
                        <p class="text-sm text-gray-600">{{ $sale->customer->user?->email }}</p>
                        @if($sale->customer->phone)
                            <p class="text-sm text-gray-600">{{ $sale->customer->phone }}</p>
                        @endif
                        @if($sale->customer->address)
                            <p class="text-sm text-gray-600">{{ $sale->customer->address }}</p>
                        @endif
                    </div>
                @else
                    <p class="text-sm text-gray-500">Sin cliente asociado.</p>
                @endif
            </div>

            {{-- Artículos vendidos --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Artículos vendidos</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-gray-600">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-4 py-3">Artículo</th>
                                <th class="px-4 py-3 text-center">Cant.</th>
                                <th class="px-4 py-3 text-right">Precio unit.</th>
                                <th class="px-4 py-3 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sale->cart?->items ?? [] as $item)
                                <tr class="border-b border-gray-100">
                                    <td class="px-4 py-3 font-medium text-gray-900">
                                        {{ $item->article?->article_name ?? 'Artículo eliminado' }}
                                    </td>
                                    <td class="px-4 py-3 text-center">{{ $item->quantity }}</td>
                                    <td class="px-4 py-3 text-right">
                                        ${{ number_format((float) $item->unit_price, 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-900">
                                        ${{ number_format((float) $item->unit_price * $item->quantity, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                                        Esta venta no tiene items en el carrito.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(($sale->cart?->items ?? [])->isNotEmpty())
                            <tfoot class="bg-gray-50 font-semibold text-gray-900">
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-right">Total</td>
                                    <td class="px-4 py-3 text-right">
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
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider mb-4">Comprobante</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase">Emitido</p>
                            <p class="text-gray-900">{{ $sale->receipt->issue_date }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase">Vence</p>
                            <p class="text-gray-900">{{ $sale->receipt->due_date }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase">Estado</p>
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">
                                Emitido
                            </span>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
