<x-app-layout>
    {{-- Panel personal del Cliente: SOLO sus compras.

         El layout cambia la etiqueta del menu a "Mi panel" cuando quien
         entra es un Cliente. --}}

    <x-slot name="header">
        <h2 class="font-bold text-lg text-zinc-800 dark:text-zinc-100">
            {{ __('Mi panel - PalletStore') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Aviso: este panel es el del cliente, no el de la empresa --}}
            <div class="bg-hazard-50 dark:bg-hazard-950 border border-hazard-200 dark:border-hazard-900 rounded-lg px-4 py-3">
                <p class="text-sm text-hazard-900 dark:text-hazard-200">
                    Acá ves <strong>tus compras</strong>. Los datos de la empresa (ventas
                    totales, stock, proveedores) son del personal de PalletStore.
                </p>
            </div>

            {{-- KPIs del cliente --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

                <div class="bg-white dark:bg-zinc-900 p-5 rounded-xl border border-gray-200 dark:border-zinc-800 shadow-sm flex items-center gap-4">
                    <div class="p-3 bg-hazard-50 dark:bg-hazard-950 text-hazard-600 rounded-lg">
                        <svg class="w-6 h-6 fill-none stroke-current stroke-2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12A1.125 1.125 0 0119.75 22H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase">Compras realizadas</p>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100 font-mono tabular-nums">{{ $compras }}</h3>
                    </div>
                </div>

                <div class="bg-white dark:bg-zinc-900 p-5 rounded-xl border border-gray-200 dark:border-zinc-800 shadow-sm flex items-center gap-4">
                    <div class="p-3 bg-hazard-50 dark:bg-hazard-950 text-hazard-600 rounded-lg">
                        <svg class="w-6 h-6 fill-none stroke-current stroke-2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase">Total gastado</p>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100 font-mono tabular-nums">${{ number_format($totalGastado, 2, ',', '.') }}</h3>
                    </div>
                </div>

                <div class="bg-white dark:bg-zinc-900 p-5 rounded-xl border border-gray-200 dark:border-zinc-800 shadow-sm flex items-center gap-4">
                    <div class="p-3 bg-hazard-50 dark:bg-hazard-950 text-hazard-600 rounded-lg">
                        <svg class="w-6 h-6 fill-none stroke-current stroke-2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase">Artículos distintos</p>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100 font-mono tabular-nums">{{ $articulos }}</h3>
                    </div>
                </div>

                <div class="bg-white dark:bg-zinc-900 p-5 rounded-xl border border-gray-200 dark:border-zinc-800 shadow-sm flex items-center gap-4">
                    <div class="p-3 bg-hazard-50 dark:bg-hazard-950 text-hazard-600 rounded-lg">
                        <svg class="w-6 h-6 fill-none stroke-current stroke-2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase">Última compra</p>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100 font-mono tabular-nums">
                            {{ $ultimaCompra?->created_at?->format('d/m/Y') ?? '—' }}
                        </h3>
                    </div>
                </div>
            </div>

            {{-- Sus compras recientes --}}
            <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="flex flex-wrap justify-between items-center gap-3 p-6 border-b border-gray-200 dark:border-zinc-800">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-zinc-100">Mis últimas compras</h3>
                    <a href="{{ route('sales.index') }}"
                       class="text-sm font-medium text-hazard-600 hover:text-hazard-800 dark:hover:text-hazard-400">
                        Ver todas →
                    </a>
                </div>

                @if ($recientes->isEmpty())
                    <div class="p-8 text-center">
                        <p class="text-gray-400 dark:text-zinc-500 italic">Todavía no hiciste ninguna compra.</p>
                        <a href="{{ route('articles.index') }}"
                           class="inline-flex items-center mt-4 px-4 py-2 bg-hazard-600 hover:bg-hazard-700 text-white text-sm font-medium rounded-md transition">
                            Ver el catálogo
                        </a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm text-left text-gray-600 dark:text-zinc-300">
                            <thead class="text-xs text-gray-700 dark:text-zinc-200 uppercase bg-gray-50 dark:bg-zinc-950">
                                <tr>
                                    <th class="px-4 py-3">Compra</th>
                                    <th class="px-4 py-3">Fecha</th>
                                    <th class="px-4 py-3">Artículos</th>
                                    <th class="px-4 py-3">Medio de pago</th>
                                    <th class="px-4 py-3 text-right">Total</th>
                                    <th class="px-4 py-3 text-center">Detalle</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recientes as $compra)
                                    <tr class="bg-white dark:bg-zinc-900 border-b hover:bg-gray-50 dark:hover:bg-zinc-800">
                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-zinc-100 font-mono tabular-nums">
                                            #{{ $compra->id_sale }}
                                        </td>
                                        <td class="px-4 py-3 font-mono tabular-nums">
                                            {{ $compra->created_at?->format('d/m/Y') ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ $compra->cart?->items->sum('quantity') ?? 0 }}
                                        </td>
                                        <td class="px-4 py-3">{{ $compra->payment_method }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-zinc-100 font-mono tabular-nums">
                                            ${{ number_format($compra->total(), 2, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <a href="{{ route('sales.show', $compra->id_sale) }}"
                                               class="font-medium text-hazard-600 hover:text-hazard-800 dark:hover:text-hazard-400">
                                                Ver
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
