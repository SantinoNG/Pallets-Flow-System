<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-zinc-100 leading-tight">
            Confirmar Venta
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow-sm sm:rounded-lg p-6 border-b border-gray-200 dark:border-zinc-800">

                <h3 class="text-lg font-medium text-gray-900 dark:text-zinc-100 mb-6">Resumen de la venta</h3>

                {{-- Detalle de items --}}
                <div class="border border-gray-200 dark:border-zinc-800 rounded-md overflow-hidden mb-6">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-zinc-400">
                        <thead class="text-xs text-gray-700 dark:text-zinc-200 uppercase bg-gray-50 dark:bg-zinc-950">
                            <tr>
                                <th class="px-4 py-3">Artículo</th>
                                <th class="px-4 py-3">Cant.</th>
                                <th class="px-4 py-3 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cart->items as $item)
                            <tr class="border-t border-gray-100 dark:border-zinc-800">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-zinc-100">
                                    {{ $item->article->article_name }}
                                </td>
                                <td class="px-4 py-3 font-mono tabular-nums">{{ $item->quantity }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-zinc-100 font-mono tabular-nums">
                                    ${{ number_format($item->unit_price * $item->quantity, 2) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Total --}}
                <div class="flex justify-between text-lg font-bold text-gray-900 dark:text-zinc-100 mb-6 px-1">
                    <span>Total</span>
                    <span>${{ number_format($subtotal, 2) }}</span>
                </div>

                {{-- Formulario de pago --}}
                <form action="{{ route('cart.confirm', $cart->id_cart) }}" method="POST">
                    @csrf

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-zinc-200 mb-1">
                            Método de pago <span class="text-red-500">*</span>
                        </label>
                        <select name="payment_method"
                                class="w-full border-gray-300 dark:border-zinc-700 rounded-md shadow-sm text-sm"
                                required>
                            <option value="">Seleccioná...</option>
                            <option value="Efectivo">Efectivo</option>
                            <option value="Tarjeta de crédito">Tarjeta de crédito</option>
                            <option value="Tarjeta de débito">Tarjeta de débito</option>
                            <option value="Transferencia">Transferencia</option>
                        </select>
                        @error('payment_method')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-4">
                        <button type="submit"
                                class="bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-6 py-2 rounded-md transition">
                            Finalizar venta y emitir comprobante
                        </button>
                        <a href="{{ route('cart.show', $cart->id_cart) }}"
                           class="text-sm text-gray-600 dark:text-zinc-300 bg-gray-100 dark:bg-zinc-950 hover:bg-gray-200 dark:hover:bg-zinc-700 px-4 py-2 rounded-md transition">
                            Volver al carrito
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>