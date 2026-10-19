<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Gestión de Artículos') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-b border-gray-200">

                {{-- Encabezado --}}
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-medium text-gray-900">Inventario de artículos</h3>
                    <a href="{{ route('articles.create') }}"
                       class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-md transition">
                        + Nuevo Artículo
                    </a>
                </div>

                @if(session('success'))
                    <div class="mb-4 px-4 py-3 bg-green-100 text-green-800 text-sm rounded-md">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- FILTROS --}}
                <form method="GET" action="{{ route('articles.index') }}"
                      class="bg-gray-50 rounded-lg p-4 mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Buscar por nombre</label>
                        <input type="text" name="search"
                               value="{{ request('search') }}"
                               placeholder="Ej: Sillón..."
                               class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Precio mínimo</label>
                        <input type="number" name="price_min"
                               value="{{ request('price_min') }}"
                               placeholder="0"
                               min="0" step="0.01"
                               class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Precio máximo</label>
                        <input type="number" name="price_max"
                               value="{{ request('price_max') }}"
                               placeholder="99999"
                               min="0" step="0.01"
                               class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                    </div>

                    <div class="flex flex-col justify-end">
                        <label class="flex items-center gap-2 text-sm text-gray-700 mb-2">
                            <input type="checkbox" name="low_stock" value="1"
                                   {{ request('low_stock') == '1' ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-indigo-600">
                            Solo stock bajo
                        </label>
                        <div class="flex gap-2">
                            <button type="submit"
                                    class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-3 py-2 rounded-md transition">
                                Filtrar
                            </button>
                            <a href="{{ route('articles.index') }}"
                               class="flex-1 text-center bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-medium px-3 py-2 rounded-md transition">
                                Limpiar
                            </a>
                        </div>
                    </div>
                </form>

                {{-- Resultados y botón PDF --}}
                <div class="flex justify-between items-center mb-3">
                    <p class="text-sm text-gray-500">
                        {{ $articles->count() }} resultado(s) encontrado(s)
                    </p>
                    <a href="{{ route('articles.pdf', request()->query()) }}"
                       class="bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-4 py-2 rounded-md transition">
                        📄 Exportar PDF
                    </a>
                </div>

                {{-- Tabla --}}
                <div class="overflow-x-auto shadow-md sm:rounded-lg">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-6 py-3">Nombre</th>
                                <th class="px-6 py-3">Precio unitario</th>
                                <th class="px-6 py-3">Stock actual</th>
                                <th class="px-6 py-3">Stock mínimo</th>
                                <th class="px-6 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($articles as $article)
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    {{ $article->article_name }}
                                </td>
                                <td class="px-6 py-4">
                                    ${{ number_format($article->unit_price, 2) }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($article->stock)
                                        @if($article->stock->remaining_quantity <= $article->stock->minimum_stock)
                                            <span class="bg-red-100 text-red-800 text-xs font-semibold px-2.5 py-0.5 rounded">
                                                {{ $article->stock->remaining_quantity }} ⚠️
                                            </span>
                                        @else
                                            <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded">
                                                {{ $article->stock->remaining_quantity }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-gray-400 italic">Sin stock</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    {{ $article->stock->minimum_stock ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-center space-x-2">
                                    <a href="{{ route('articles.edit', $article->id_article) }}"
                                       class="font-medium text-indigo-600 hover:text-indigo-900 bg-indigo-50 px-3 py-2 rounded-md transition">
                                        Editar
                                    </a>
                                    <form action="{{ route('articles.destroy', $article->id_article) }}"
                                          method="POST" style="display:inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="font-medium text-red-600 hover:text-red-900 bg-red-50 px-3 py-2 rounded-md transition"
                                                onclick="return confirm('¿Estás seguro?')">
                                            Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-400 italic">
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