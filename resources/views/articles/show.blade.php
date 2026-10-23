<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-zinc-100 leading-tight">
                Receta del artículo
            </h2>
            <a href="{{ route('articles.index') }}"
               class="text-sm text-gray-600 dark:text-zinc-300 bg-gray-100 dark:bg-zinc-950 hover:bg-gray-200 dark:hover:bg-zinc-800 px-4 py-2 rounded-md transition">
                ← Volver al catálogo
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Ficha del producto --}}
            <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow-sm sm:rounded-lg p-6 border-b border-gray-200 dark:border-zinc-800">
                <div class="flex flex-col sm:flex-row gap-6">
                    <x-article-image :article="$article" size="xl" />

                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-zinc-100">
                            {{ $article->article_name }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-zinc-400 mt-1">
                            {{ $article->description ?: 'Sin descripción.' }}
                        </p>

                        <div class="flex flex-wrap items-center gap-6 mt-4 text-sm">
                            <div>
                                <span class="block text-[11px] font-bold text-gray-500 dark:text-zinc-400 uppercase">Precio de venta</span>
                                <span class="font-mono tabular-nums text-gray-900 dark:text-zinc-100 font-semibold">
                                    ${{ number_format($article->unit_price, 2) }}
                                </span>
                            </div>
                            <div>
                                <span class="block text-[11px] font-bold text-gray-500 dark:text-zinc-400 uppercase">Stock del producto</span>
                                @if($article->stock)
                                    <span class="font-mono tabular-nums {{ $article->stock->remaining_quantity <= $article->stock->minimum_stock ? 'text-red-600 dark:text-red-400' : 'text-green-700 dark:text-green-400' }} font-semibold">
                                        {{ $article->stock->remaining_quantity }} unidades
                                    </span>
                                @else
                                    <span class="text-gray-400 dark:text-zinc-500 italic">Sin stock</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="sm:self-start">
                        <a href="{{ route('articles.edit', $article->id_article) }}"
                           class="inline-block bg-hazard-600 hover:bg-hazard-700 text-white text-sm font-medium px-4 py-2 rounded-md transition">
                            Editar receta
                        </a>
                    </div>
                </div>
            </div>

            {{-- Cálculos: variedad, costo, margen y alcance --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-zinc-900 rounded-lg border border-gray-200 dark:border-zinc-800 p-4">
                    <p class="text-[11px] font-bold text-gray-500 dark:text-zinc-400 uppercase">Variedad</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-zinc-100 font-mono tabular-nums">
                        {{ $materiales->count() }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-zinc-400">
                        materias primas distintas
                        @if($proveedores > 0)
                            · {{ $proveedores }} proveedor(es)
                        @endif
                    </p>
                </div>

                <div class="bg-white dark:bg-zinc-900 rounded-lg border border-gray-200 dark:border-zinc-800 p-4">
                    <p class="text-[11px] font-bold text-gray-500 dark:text-zinc-400 uppercase">Costo de materiales</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-zinc-100 font-mono tabular-nums">
                        ${{ number_format($costoMateriales, 2) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-zinc-400">por unidad fabricada</p>
                </div>

                <div class="bg-white dark:bg-zinc-900 rounded-lg border border-gray-200 dark:border-zinc-800 p-4">
                    <p class="text-[11px] font-bold text-gray-500 dark:text-zinc-400 uppercase">Margen bruto</p>
                    <p class="text-2xl font-bold font-mono tabular-nums {{ $margen < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-700 dark:text-green-400' }}">
                        ${{ number_format($margen, 2) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-zinc-400">
                        {{ number_format($margenPct, 1) }}% sobre el precio de venta
                    </p>
                </div>

                <div class="bg-white dark:bg-zinc-900 rounded-lg border border-gray-200 dark:border-zinc-800 p-4">
                    <p class="text-[11px] font-bold text-gray-500 dark:text-zinc-400 uppercase">Alcance con stock</p>
                    <p class="text-2xl font-bold font-mono tabular-nums {{ ($alcance ?? 0) > 0 ? 'text-gray-900 dark:text-zinc-100' : 'text-red-600 dark:text-red-400' }}">
                        {{ $alcance ?? 0 }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-zinc-400">unidades que se pueden armar</p>
                </div>
            </div>

            {{-- Detalle de la receta --}}
            <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow-sm sm:rounded-lg border-b border-gray-200 dark:border-zinc-800">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-800 flex flex-wrap justify-between items-center gap-2">
                    <h4 class="text-base font-semibold text-gray-800 dark:text-zinc-100">
                        Materias primas que lleva una unidad
                    </h4>
                    <span class="text-xs text-gray-500 dark:text-zinc-400">
                        Cantidad y unidad de cada insumo
                    </span>
                </div>

                @if($materiales->isEmpty())
                    <div class="px-6 py-10 text-center">
                        <p class="text-sm text-gray-500 dark:text-zinc-400">
                            Este artículo todavía no tiene materias primas cargadas.
                        </p>
                        <a href="{{ route('articles.edit', $article->id_article) }}"
                           class="inline-block mt-3 text-sm font-medium text-hazard-600 dark:text-hazard-400 hover:text-hazard-700 dark:hover:text-hazard-300">
                            Cargar la receta →
                        </a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-600 dark:text-zinc-300">
                            <thead class="text-xs text-gray-700 dark:text-zinc-200 uppercase bg-gray-50 dark:bg-zinc-950 border-b border-gray-200 dark:border-zinc-800">
                                <tr>
                                    <th class="px-6 py-3">Materia prima</th>
                                    <th class="px-6 py-3">Cantidad</th>
                                    <th class="px-6 py-3">Proveedor</th>
                                    <th class="px-6 py-3 text-right">Stock actual</th>
                                    <th class="px-6 py-3 text-right">Costo unitario</th>
                                    <th class="px-6 py-3 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                                @foreach($materiales as $material)
                                    @php
                                        $cantidad = (float) $material->pivot->quantity_required;
                                        $subtotal = $cantidad * (float) $material->unit_cost;
                                    @endphp
                                    <tr class="bg-white dark:bg-zinc-900 hover:bg-gray-50 dark:hover:bg-zinc-800 transition">
                                        <td class="px-6 py-3 font-medium text-gray-900 dark:text-zinc-100">
                                            {{ $material->description }}
                                        </td>
                                        <td class="px-6 py-3 font-mono tabular-nums">
                                            {{ $material->cantidad($cantidad) }}
                                        </td>
                                        <td class="px-6 py-3 text-gray-500 dark:text-zinc-400">
                                            {{ $material->supplier?->company_name ?? '—' }}
                                        </td>
                                        <td class="px-6 py-3 text-right font-mono tabular-nums {{ (float) ($material->stock->remaining_quantity ?? 0) < $cantidad ? 'text-red-600 dark:text-red-400' : '' }}">
                                            {{ $material->cantidad((float) ($material->stock->remaining_quantity ?? 0)) }}
                                        </td>
                                        <td class="px-6 py-3 text-right font-mono tabular-nums">
                                            ${{ number_format($material->unit_cost, 2) }}
                                            <span class="text-xs text-gray-400 dark:text-zinc-500">
                                                / {{ $material->etiquetaUnidad(1) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-3 text-right font-mono tabular-nums text-gray-900 dark:text-zinc-100">
                                            ${{ number_format($subtotal, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50 dark:bg-zinc-950 border-t border-gray-200 dark:border-zinc-800">
                                <tr>
                                    <td colspan="5" class="px-6 py-3 text-right font-semibold text-gray-700 dark:text-zinc-200">
                                        Costo total de materiales
                                    </td>
                                    <td class="px-6 py-3 text-right font-mono tabular-nums font-bold text-gray-900 dark:text-zinc-100">
                                        ${{ number_format($costoMateriales, 2) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Explicación del alcance --}}
            @if($materiales->isNotEmpty() && $limitante)
                <div class="rounded-lg border border-hazard-200 dark:border-hazard-900 bg-hazard-50 dark:bg-hazard-950/40 px-4 py-3 text-sm text-hazard-800 dark:text-hazard-200">
                    Con el stock actual de materias primas alcanza para
                    <strong class="font-mono tabular-nums">{{ $alcance ?? 0 }} unidades</strong>.
                    El insumo que limita es <strong>{{ $limitante->description }}</strong>:
                    hay {{ $limitante->cantidad((float) ($limitante->stock->remaining_quantity ?? 0)) }}
                    y cada unidad usa {{ $limitante->cantidad((float) $limitante->pivot->quantity_required) }}.
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
