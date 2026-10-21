<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-zinc-100 leading-tight">
            Comprobante de Venta #{{ $sale->id_sale }}
        </h2>
    </x-slot>

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
                    <div class="mb-6 px-4 py-3 bg-green-100 text-green-800 text-sm rounded-md">
                        ✅ {{ session('success') }}
                    </div>
                @endif

                {{-- Encabezado del comprobante --}}
                <div class="flex flex-wrap justify-between items-start gap-3 mb-8">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('images/logo-marca.svg') }}"
                             alt="Pallets Flow"
                             class="h-12 w-12 rounded-md shrink-0">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-zinc-100">PalletStore</h3>
                            <p class="text-sm text-gray-500 dark:text-zinc-400">Muebles artesanales de pallets</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-gray-900 dark:text-zinc-100">Comprobante #{{ $sale->id_sale }}</p>
                        <p class="text-xs text-gray-500 dark:text-zinc-400">
                            Emitido: {{ $sale->receipt->issue_date ?? now()->toDateString() }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-zinc-400">
                            Vence: {{ $sale->receipt->due_date ?? now()->addDays(30)->toDateString() }}
                        </p>
                    </div>
                </div>

                {{-- Datos del cliente --}}
                <div class="bg-gray-50 dark:bg-zinc-950 rounded-md p-4 mb-6">
                    <p class="text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase mb-2">Cliente</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-zinc-100">
                        {{ $sale->customer->first_name }} {{ $sale->customer->last_name }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-zinc-300">{{ $sale->customer->user->email ?? '' }}</p>
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

                {{-- Total y método de pago --}}
                <div class="border-t border-gray-200 dark:border-zinc-800 pt-4 mb-6">
                    <div class="flex justify-between text-sm text-gray-600 dark:text-zinc-300 mb-2">
                        <span>Método de pago</span>
                        <span class="font-medium text-gray-900 dark:text-zinc-100">{{ $sale->payment_method }}</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold text-gray-900 dark:text-zinc-100">
                        <span>Total</span>
                        <span>${{ number_format($subtotal, 2) }}</span>
                    </div>
                </div>

                {{-- Atendido por --}}
                @if($sale->employee)
                <p class="text-xs text-gray-400 dark:text-zinc-500 mb-6">
                    Atendido por: {{ $sale->employee->first_name }} {{ $sale->employee->last_name }}
                </p>
                @endif

                {{-- Acciones --}}
                <div class="flex items-center gap-4">
                    <a href="{{ route('cart.index') }}"
                       class="bg-hazard-600 hover:bg-hazard-700 text-white text-sm font-medium px-4 py-2 rounded-md transition">
                        Nueva venta
                    </a>
                    <a href="{{ route('dashboard') }}"
                       class="text-sm text-gray-600 dark:text-zinc-300 bg-gray-100 dark:bg-zinc-950 hover:bg-gray-200 dark:hover:bg-zinc-700 px-4 py-2 rounded-md transition">
                        Ir al dashboard
                    </a>
                </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>