<x-app-layout>
    {{-- El Cliente ve el catálogo en modo lectura: puede buscar y
         comprar, pero no gestionar el inventario. El Vendedor también
         lo ve en lectura, pero sin el carrito del cliente: su venta la
         arma desde el mostrador. Los flags los pasa el controlador. --}}

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-zinc-100 leading-tight">
            {{ $puedeGestionar ? __('Gestión de Artículos') : __('Catálogo de productos') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow-sm sm:rounded-lg p-6 border-b border-gray-200 dark:border-zinc-800">

                {{-- Encabezado --}}
                <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-zinc-100">
                        {{ $puedeGestionar ? 'Inventario de artículos' : 'Productos disponibles' }}
                    </h3>
                    @if ($puedeGestionar)
                        <a href="{{ route('articles.create') }}"
                           class="bg-hazard-600 hover:bg-hazard-700 text-white text-sm font-medium px-4 py-2 rounded-md transition">
                            + Nuevo Artículo
                        </a>
                    @endif
                </div>

                @if(session('success'))
                    <div class="mb-4 px-4 py-3 bg-green-100 dark:bg-green-950/40 text-green-800 dark:text-green-300 text-sm rounded-md">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- FILTROS --}}
                <form method="GET" action="{{ route('articles.index') }}"
                      class="bg-gray-50 dark:bg-zinc-950 rounded-lg p-4 mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-zinc-300 mb-1">Buscar por nombre</label>
                        <input type="text" name="search"
                               value="{{ request('search') }}"
                               placeholder="Ej: Sillón..."
                               class="w-full border-gray-300 dark:border-zinc-700 rounded-md shadow-sm text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-zinc-300 mb-1">Precio mínimo</label>
                        <input type="number" name="price_min"
                               value="{{ request('price_min') }}"
                               placeholder="0"
                               min="0" step="0.01"
                               class="w-full border-gray-300 dark:border-zinc-700 rounded-md shadow-sm text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-zinc-300 mb-1">Precio máximo</label>
                        <input type="number" name="price_max"
                               value="{{ request('price_max') }}"
                               placeholder="99999"
                               min="0" step="0.01"
                               class="w-full border-gray-300 dark:border-zinc-700 rounded-md shadow-sm text-sm">
                    </div>

                    <div class="flex flex-col justify-end">
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-zinc-200 mb-2">
                            <input type="checkbox" name="low_stock" value="1"
                                   {{ request('low_stock') == '1' ? 'checked' : '' }}
                                   class="rounded border-gray-300 dark:border-zinc-700 text-hazard-600">
                            Solo stock bajo
                        </label>
                        <div class="flex gap-2">
                            <button type="submit"
                                    class="flex-1 bg-hazard-600 hover:bg-hazard-700 text-white text-sm font-medium px-3 py-2 rounded-md transition">
                                Filtrar
                            </button>
                            <a href="{{ route('articles.index') }}"
                               class="flex-1 text-center bg-gray-200 dark:bg-zinc-800 hover:bg-gray-300 text-gray-700 dark:text-zinc-200 text-sm font-medium px-3 py-2 rounded-md transition">
                                Limpiar
                            </a>
                        </div>
                    </div>
                </form>

                {{-- Resultados y botón PDF --}}
                <div class="flex flex-wrap justify-between items-center gap-3 mb-3">
                    <p class="text-sm text-gray-500 dark:text-zinc-400">
                        {{ $articles->count() }} resultado(s) encontrado(s)
                    </p>
                    @unless ($esCliente)
                        <a href="{{ route('articles.pdf', request()->query()) }}"
                           class="bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-4 py-2 rounded-md transition">
                            📄 Exportar PDF
                        </a>
                    @endunless
                </div>

                {{-- Tabla --}}
                <div class="overflow-x-auto shadow-md sm:rounded-lg">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-zinc-400">
                        <thead class="text-xs text-gray-700 dark:text-zinc-200 uppercase bg-gray-50 dark:bg-zinc-950">
                            <tr>
                                <th class="px-4 py-3 w-20">Foto</th>
                                <th class="px-6 py-3">Nombre</th>
                                <th class="px-6 py-3">Precio unitario</th>
                                <th class="px-6 py-3">Stock actual</th>
                                @unless ($esCliente)
                                    <th class="px-6 py-3">Stock mínimo</th>
                                @endunless
                                @if ($puedeGestionar)
                                    <th class="px-6 py-3 text-center">Acciones</th>
                                @elseif ($esCliente)
                                    <th class="px-6 py-3 text-center">Comprar</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($articles as $article)
                            <tr class="bg-white dark:bg-zinc-900 border-b hover:bg-gray-50 dark:hover:bg-zinc-800">
                                <td class="px-4 py-3">
                                    <x-article-image :article="$article" size="sm" />
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-zinc-100">
                                    @if ($puedeGestionar)
                                        <a href="{{ route('articles.show', $article->id_article) }}"
                                           class="hover:text-hazard-600 dark:hover:text-hazard-400 transition">
                                            {{ $article->article_name }}
                                        </a>
                                        <span class="block text-[11px] text-gray-400 dark:text-zinc-500 font-normal">
                                            {{ $article->raw_materials_count }} insumo(s) en los planos
                                        </span>
                                    @else
                                        {{ $article->article_name }}
                                    @endif
                                </td>
                                <td class="px-6 py-4 font-mono tabular-nums">
                                    ${{ number_format($article->unit_price, 2) }}
                                </td>
                                <td class="px-6 py-4 font-mono tabular-nums">
                                    @if($article->stock)
                                        @if($article->stock->remaining_quantity <= $article->stock->minimum_stock)
                                            <span class="bg-red-100 dark:bg-red-950/40 text-red-800 dark:text-red-300 text-xs font-semibold px-2.5 py-0.5 rounded">
                                                {{ $article->stock->remaining_quantity }} ⚠️
                                            </span>
                                        @else
                                            <span class="bg-green-100 dark:bg-green-950/40 text-green-800 dark:text-green-300 text-xs font-semibold px-2.5 py-0.5 rounded">
                                                {{ $article->stock->remaining_quantity }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-gray-400 dark:text-zinc-500 italic">Sin stock</span>
                                    @endif
                                </td>
                                @unless ($esCliente)
                                    <td class="px-6 py-4 font-mono tabular-nums">
                                        {{ $article->stock->minimum_stock ?? '—' }}
                                    </td>
                                @endunless
                                @if ($puedeGestionar)
                                    <td class="px-6 py-4 text-center font-mono tabular-nums">
                                        <div class="inline-flex flex-wrap justify-center items-center gap-2">
                                            <a href="{{ route('articles.show', $article->id_article) }}"
                                               class="font-medium text-gray-600 dark:text-zinc-300 bg-gray-100 dark:bg-zinc-950 px-3 py-2 rounded-md transition">
                                                Planos
                                            </a>
                                            <a href="{{ route('articles.edit', $article->id_article) }}"
                                               class="font-medium text-hazard-600 hover:text-hazard-900 bg-hazard-50 px-3 py-2 rounded-md transition">
                                                Editar
                                            </a>
                                            <form action="{{ route('articles.destroy', $article->id_article) }}"
                                                  method="POST" style="display:inline-block;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="font-medium text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 bg-red-50 dark:bg-red-950/40 px-3 py-2 rounded-md transition"
                                                        onclick="return confirm('¿Estás seguro?')">
                                                    Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                @elseif ($esCliente)
                                    {{-- El Cliente compra: el carrito se resuelve
                                         desde su propia ficha, asi que no
                                         puede meter cosas en el de otro. --}}
                                    <td class="px-6 py-4 text-center">
                                        @if ($article->stock && $article->stock->remaining_quantity > 0)
                                            <form action="{{ route('cart.mine.addItem') }}" method="POST" class="inline-flex items-center gap-2">
                                                @csrf
                                                <input type="hidden" name="id_article" value="{{ $article->id_article }}">
                                                <input type="number" name="quantity" value="1" min="1"
                                                       max="{{ $article->stock->remaining_quantity }}"
                                                       class="w-16 border-gray-300 dark:border-zinc-700 rounded-md shadow-sm text-sm text-center font-mono tabular-nums">
                                                <button type="submit"
                                                        class="bg-hazard-600 hover:bg-hazard-700 text-white text-xs font-semibold px-3 py-2 rounded-md transition">
                                                    Agregar
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-gray-400 dark:text-zinc-500 italic text-xs">Sin stock</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ $puedeGestionar ? 6 : 5 }}" class="px-6 py-8 text-center text-gray-400 dark:text-zinc-500 italic">
                                    No se encontraron artículos con esos filtros.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>