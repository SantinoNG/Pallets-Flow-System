<x-app-layout>
    {{-- El Cliente ve "Mi carrito"; el personal, la venta que está armando.
         El flag $esCliente lo pasa el controlador. --}}

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-zinc-100 leading-tight">
            @if ($esCliente)
                {{ __('Mi carrito') }}
            @else
                Venta — {{ $cart->customer->first_name }} {{ $cart->customer->last_name }}
            @endif
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Panel izquierdo: agregar artículos --}}
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-zinc-900 shadow-sm sm:rounded-lg p-6 border-b border-gray-200 dark:border-zinc-800 mb-6">

                        <h3 class="text-base font-semibold text-gray-900 dark:text-zinc-100 mb-4">Agregar artículo</h3>

                        @if(session('success'))
                            <div class="mb-4 px-4 py-3 bg-green-100 text-green-800 text-sm rounded-md">
                                {{ session('success') }}
                            </div>
                        @endif
                        @if(session('error'))
                            <div class="mb-4 px-4 py-3 bg-red-100 text-red-800 text-sm rounded-md">
                                {{ session('error') }}
                            </div>
                        @endif

                        <form action="{{ route('cart.addItem', $cart->id_cart) }}" method="POST">
                            @csrf
                            <div class="flex gap-3">
                                <select name="id_article"
                                        class="flex-1 border-gray-300 dark:border-zinc-700 rounded-md shadow-sm text-sm"
                                        required>
                                    <option value="">Seleccioná un artículo...</option>
                                    @foreach($articles as $article)
                                        <option value="{{ $article->id_article }}">
                                            {{ $article->article_name }}
                                            — ${{ number_format($article->unit_price, 2) }}
                                            (Stock: {{ $article->stock->remaining_quantity ?? 0 }})
                                        </option>
                                    @endforeach
                                </select>
                                <input type="number" name="quantity" value="1" min="1"
                                       class="w-20 border-gray-300 dark:border-zinc-700 rounded-md shadow-sm text-sm"
                                       required>
                                <button type="submit"
                                        class="bg-hazard-600 hover:bg-hazard-700 text-white text-sm font-medium px-4 py-2 rounded-md transition">
                                    + Agregar
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Tabla de items --}}
                    <div class="bg-white dark:bg-zinc-900 shadow-sm sm:rounded-lg border-b border-gray-200 dark:border-zinc-800 overflow-hidden">
                        <div class="p-6 border-b border-gray-100 dark:border-zinc-800">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-zinc-100">
                                Artículos en el carrito
                            </h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-zinc-400">
                                <thead class="text-xs text-gray-700 dark:text-zinc-200 uppercase bg-gray-50 dark:bg-zinc-950">
                                    <tr>
                                        <th class="px-4 py-3 w-20">Foto</th>
                                        <th class="px-6 py-3">Artículo</th>
                                        <th class="px-6 py-3">Precio unit.</th>
                                        <th class="px-6 py-3">Cantidad</th>
                                        <th class="px-6 py-3">Subtotal</th>
                                        <th class="px-6 py-3 text-center">Quitar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($cart->items as $item)
                                    <tr class="bg-white dark:bg-zinc-900 border-b hover:bg-gray-50 dark:hover:bg-zinc-800">
                                        <td class="px-4 py-3">
                                            <x-article-image :article="$item->article" size="sm" />
                                        </td>
                                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-zinc-100">
                                            {{ $item->article->article_name }}
                                        </td>
                                        <td class="px-6 py-4 font-mono tabular-nums">
                                            ${{ number_format($item->unit_price, 2) }}
                                        </td>
                                        <td class="px-6 py-4 font-mono tabular-nums">{{ $item->quantity }}</td>
                                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-zinc-100 font-mono tabular-nums">
                                            ${{ number_format($item->unit_price * $item->quantity, 2) }}
                                        </td>
                                        <td class="px-6 py-4 text-center font-mono tabular-nums">
                                            <form action="{{ route('cart.removeItem', [$cart->id_cart, $item->id_cart_item]) }}"
                                                  method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="quantity" value="{{ $item->quantity }}">
                                                <button type="submit"
                                                        class="text-red-600 hover:text-red-800 font-medium text-xs bg-red-50 px-3 py-1 rounded-md"
                                                        onclick="return confirm('¿Quitar este artículo?')">
                                                    Quitar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-gray-400 dark:text-zinc-500 italic">
                                            El carrito está vacío. Agregá artículos arriba.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Panel derecho: resumen --}}
                <div>
                    <div class="bg-white dark:bg-zinc-900 shadow-sm sm:rounded-lg p-6 border-b border-gray-200 dark:border-zinc-800 sticky top-6">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-zinc-100 mb-4">Resumen</h3>

                        <div class="space-y-2 mb-4">
                            <div class="flex justify-between text-sm text-gray-600 dark:text-zinc-300">
                                <span>Cliente</span>
                                <span class="font-medium text-gray-900 dark:text-zinc-100">
                                    {{ $cart->customer->first_name }} {{ $cart->customer->last_name }}
                                </span>
                            </div>
                            <div class="flex justify-between text-sm text-gray-600 dark:text-zinc-300">
                                <span>Productos</span>
                                <span class="font-medium text-gray-900 dark:text-zinc-100">
                                    {{ $cart->items->count() }}
                                </span>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 dark:border-zinc-800 pt-4 mb-6">
                            <div class="flex justify-between text-lg font-bold text-gray-900 dark:text-zinc-100">
                                <span>Total</span>
                                <span>${{ number_format($subtotal, 2) }}</span>
                            </div>
                        </div>

                        @if($cart->items->count() > 0)
                        <a href="{{ route('cart.checkout', $cart->id_cart) }}"
                           class="block w-full text-center bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-4 py-3 rounded-md transition mb-3">
                            Confirmar venta →
                        </a>
                        @endif

                        <a href="{{ route('cart.index') }}"
                           class="block w-full text-center text-sm text-gray-600 dark:text-zinc-300 bg-gray-100 dark:bg-zinc-950 hover:bg-gray-200 dark:hover:bg-zinc-700 px-4 py-2 rounded-md transition">
                            Nueva venta
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>