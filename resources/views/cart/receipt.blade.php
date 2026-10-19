<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Comprobante de Venta #{{ $sale->id_sale }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8 border-b border-gray-200">

                {{-- Alerta de éxito --}}
                @if(session('success'))
                    <div class="mb-6 px-4 py-3 bg-green-100 text-green-800 text-sm rounded-md">
                        ✅ {{ session('success') }}
                    </div>
                @endif

                {{-- Encabezado del comprobante --}}
                <div class="flex justify-between items-start mb-8">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">PalletStore</h3>
                        <p class="text-sm text-gray-500">Muebles artesanales de pallets</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-gray-900">Comprobante #{{ $sale->id_sale }}</p>
                        <p class="text-xs text-gray-500">
                            Emitido: {{ $sale->receipt->issue_date ?? now()->toDateString() }}
                        </p>
                        <p class="text-xs text-gray-500">
                            Vence: {{ $sale->receipt->due_date ?? now()->addDays(30)->toDateString() }}
                        </p>
                    </div>
                </div>

                {{-- Datos del cliente --}}
                <div class="bg-gray-50 rounded-md p-4 mb-6">
                    <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Cliente</p>
                    <p class="text-sm font-medium text-gray-900">
                        {{ $sale->customer->first_name }} {{ $sale->customer->last_name }}
                    </p>
                    <p class="text-sm text-gray-600">{{ $sale->customer->user->email ?? '' }}</p>
                    <p class="text-sm text-gray-600">{{ $sale->customer->phone ?? '' }}</p>
                </div>

                {{-- Detalle de productos --}}
                <div class="mb-6">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-4 py-3">Artículo</th>
                                <th class="px-4 py-3 text-center">Cant.</th>
                                <th class="px-4 py-3 text-right">Precio unit.</th>
                                <th class="px-4 py-3 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sale->cart->items as $item)
                            <tr class="border-t border-gray-100">
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    {{ $item->article->article_name }}
                                </td>
                                <td class="px-4 py-3 text-center">{{ $item->quantity }}</td>
                                <td class="px-4 py-3 text-right">
                                    ${{ number_format($item->unit_price, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-900">
                                    ${{ number_format($item->unit_price * $item->quantity, 2) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Total y método de pago --}}
                <div class="border-t border-gray-200 pt-4 mb-6">
                    <div class="flex justify-between text-sm text-gray-600 mb-2">
                        <span>Método de pago</span>
                        <span class="font-medium text-gray-900">{{ $sale->payment_method }}</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold text-gray-900">
                        <span>Total</span>
                        <span>${{ number_format($subtotal, 2) }}</span>
                    </div>
                </div>

                {{-- Atendido por --}}
                @if($sale->employee)
                <p class="text-xs text-gray-400 mb-6">
                    Atendido por: {{ $sale->employee->first_name }} {{ $sale->employee->last_name }}
                </p>
                @endif

                {{-- Acciones --}}
                <div class="flex items-center gap-4">
                    <a href="{{ route('cart.index') }}"
                       class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-md transition">
                        Nueva venta
                    </a>
                    <a href="{{ route('dashboard') }}"
                       class="text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-md transition">
                        Ir al dashboard
                    </a>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>