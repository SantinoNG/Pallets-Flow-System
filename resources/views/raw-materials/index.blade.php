<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-zinc-100 leading-tight">
            {{ __('Materias Primas') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow-sm sm:rounded-lg p-6 border-b border-gray-200 dark:border-zinc-800">

                <!-- Encabezado -->
                <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 dark:text-zinc-100">Depósito de materias primas</h3>
                        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-0.5">
                            Cada insumo con su presentación (caja, bolsa, unidad, metro...), su stock y el proveedor que lo brinda.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if($puedeGestionar)
                            <a href="{{ route('raw-materials.create') }}"
                               class="bg-hazard-600 hover:bg-hazard-700 text-white text-xs font-semibold px-4 py-2 rounded-md transition shadow-sm">
                                + Nueva materia prima
                            </a>
                        @endif
                        <a href="{{ route('raw-materials.pdf', request()->all()) }}"
                           class="bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-3 py-2 rounded-md transition flex items-center gap-1.5 shadow-sm">
                            <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20">
                                <path d="M4 18h12v-2H4v2zM12 2H8v6H5l5 5 5-5h-3V2z"/>
                            </svg>
                            Exportar PDF
                        </a>
                    </div>
                </div>

                <!-- Resumen -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-gray-50 dark:bg-zinc-950 rounded-lg border border-gray-100 dark:border-zinc-800 p-4">
                        <p class="text-[11px] font-bold text-gray-500 dark:text-zinc-400 uppercase">Materias primas</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-zinc-100 font-mono tabular-nums">{{ $resumen['total'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-zinc-400">insumos en el depósito</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-zinc-950 rounded-lg border border-gray-100 dark:border-zinc-800 p-4">
                        <p class="text-[11px] font-bold text-gray-500 dark:text-zinc-400 uppercase">Presentaciones</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-zinc-100 font-mono tabular-nums">{{ $resumen['unidades'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-zinc-400">formas de compra distintas</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-zinc-950 rounded-lg border border-gray-100 dark:border-zinc-800 p-4">
                        <p class="text-[11px] font-bold text-gray-500 dark:text-zinc-400 uppercase">Bajo mínimo</p>
                        <p class="text-2xl font-bold font-mono tabular-nums {{ $resumen['bajoMinimo'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-700 dark:text-green-400' }}">
                            {{ $resumen['bajoMinimo'] }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-zinc-400">insumos para reponer</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-zinc-950 rounded-lg border border-gray-100 dark:border-zinc-800 p-4">
                        <p class="text-[11px] font-bold text-gray-500 dark:text-zinc-400 uppercase">Valor del stock</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-zinc-100 font-mono tabular-nums">
                            ${{ number_format($resumen['valor'], 2) }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-zinc-400">a costo de reposición</p>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="bg-gray-50 dark:bg-zinc-950 p-4 rounded-lg border border-gray-100 dark:border-zinc-800 mb-6">
                    <form action="{{ route('raw-materials.index') }}" method="GET" class="flex flex-wrap items-end gap-3">
                        <div class="flex-1 min-w-[180px]">
                            <label for="search" class="block text-[11px] font-bold text-gray-500 dark:text-zinc-400 uppercase mb-1">
                                Materia prima
                            </label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}"
                                   placeholder="Ej: Bisagras, clavos..."
                                   class="w-full text-xs rounded-md border-gray-300 dark:border-zinc-700 shadow-sm focus:border-hazard-500 focus:ring-hazard-500 px-3 py-2">
                        </div>

                        <div class="min-w-[180px]">
                            <label for="supplier" class="block text-[11px] font-bold text-gray-500 dark:text-zinc-400 uppercase mb-1">
                                Proveedor
                            </label>
                            <select name="supplier" id="supplier"
                                    class="w-full text-xs rounded-md border-gray-300 dark:border-zinc-700 shadow-sm focus:border-hazard-500 focus:ring-hazard-500 px-3 py-2">
                                <option value="">Todos</option>
                                @foreach($proveedores as $proveedor)
                                    <option value="{{ $proveedor->id_supplier }}" @selected((string) request('supplier') === (string) $proveedor->id_supplier)>
                                        {{ $proveedor->company_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="min-w-[150px]">
                            <label for="unit" class="block text-[11px] font-bold text-gray-500 dark:text-zinc-400 uppercase mb-1">
                                Presentación
                            </label>
                            <select name="unit" id="unit"
                                    class="w-full text-xs rounded-md border-gray-300 dark:border-zinc-700 shadow-sm focus:border-hazard-500 focus:ring-hazard-500 px-3 py-2">
                                <option value="">Todas</option>
                                @foreach(\App\Models\RawMaterial::UNIDADES as $clave => [$singular, $plural])
                                    <option value="{{ $clave }}" @selected(request('unit') === $clave)>{{ ucfirst($plural) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <label class="flex items-center gap-2 text-xs text-gray-600 dark:text-zinc-300 pb-2">
                            <input type="checkbox" name="low_stock" value="1" @checked(request('low_stock') === '1')
                                   class="rounded border-gray-300 dark:border-zinc-700 text-hazard-600">
                            Solo bajo mínimo
                        </label>

                        <div class="flex items-center gap-2">
                            <button type="submit"
                                    class="bg-hazard-600 hover:bg-hazard-700 text-white text-xs font-semibold px-4 py-2 rounded-md transition">
                                Filtrar
                            </button>
                            <a href="{{ route('raw-materials.index') }}"
                               class="bg-gray-200 dark:bg-zinc-800 hover:bg-gray-300 dark:hover:bg-zinc-700 text-gray-700 dark:text-zinc-200 text-xs font-semibold px-4 py-2 rounded-md transition">
                                Limpiar
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Contador -->
                <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
                    <span class="text-xs text-gray-500 dark:text-zinc-400 font-medium">
                        {{ $materiales->total() }} materia(s) prima(s) encontrada(s)
                    </span>
                    <span class="text-xs text-gray-400 dark:text-zinc-500">
                        Las cantidades se muestran en la presentación de compra
                    </span>
                </div>

                @if(session('success'))
                    <div class="mb-4 px-4 py-3 bg-green-100 dark:bg-green-950/40 text-green-800 dark:text-green-300 text-xs rounded-md">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4 px-4 py-3 bg-red-100 dark:bg-red-950/40 text-red-800 dark:text-red-300 text-xs rounded-md">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Tabla -->
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-zinc-800">
                    <table class="w-full text-xs text-left text-gray-600 dark:text-zinc-300">
                        <thead class="text-[11px] text-gray-500 dark:text-zinc-400 uppercase bg-gray-50 dark:bg-zinc-950 border-b border-gray-200 dark:border-zinc-800 font-bold">
                            <tr>
                                <th class="px-6 py-3">Materia prima</th>
                                <th class="px-6 py-3">Presentación</th>
                                <th class="px-6 py-3 text-right">Stock</th>
                                <th class="px-6 py-3 text-right">Mínimo</th>
                                <th class="px-6 py-3">Proveedor</th>
                                <th class="px-6 py-3 text-right">Costo unitario</th>
                                @if($puedeGestionar)
                                    <th class="px-6 py-3 text-center">Acciones</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                            @forelse($materiales as $material)
                                @php
                                    $stock = (float) ($material->stock->remaining_quantity ?? 0);
                                    $minimo = (float) ($material->stock->minimum_stock ?? 0);
                                    $bajo = $stock <= $minimo;
                                @endphp
                                <tr class="bg-white dark:bg-zinc-900 hover:bg-gray-50 dark:hover:bg-zinc-800 transition">
                                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-zinc-100">
                                        {{ $material->description }}
                                        <span class="block text-[11px] font-normal text-gray-400 dark:text-zinc-500">
                                            {{ $material->articles_count }} producto(s) en sus planos
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-block bg-hazard-100 text-hazard-800 text-[10px] font-semibold px-2 py-0.5 rounded-full">
                                            {{ $material->etiquetaUnidad(2) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right font-mono tabular-nums">
                                        <span class="text-[10px] font-semibold px-2.5 py-0.5 rounded {{ $bajo ? 'bg-red-100 dark:bg-red-950/40 text-red-800 dark:text-red-300' : 'bg-green-100 dark:bg-green-950/40 text-green-800 dark:text-green-300' }}">
                                            {{ $material->cantidad($stock) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right font-mono tabular-nums text-gray-500 dark:text-zinc-400">
                                        {{ $material->cantidad($minimo) }}
                                    </td>
                                    <td class="px-6 py-4 text-gray-500 dark:text-zinc-400">
                                        @if($material->supplier)
                                            <a href="{{ route('suppliers.index', ['company_name' => $material->supplier->company_name]) }}"
                                               class="hover:text-hazard-600 dark:hover:text-hazard-500 transition">
                                                {{ $material->supplier->company_name }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right font-mono tabular-nums">
                                        ${{ number_format($material->unit_cost, 2) }}
                                        <span class="text-[11px] text-gray-400 dark:text-zinc-500">/ {{ $material->etiquetaUnidad(1) }}</span>
                                    </td>
                                    @if($puedeGestionar)
                                        <td class="px-6 py-4 text-center font-mono tabular-nums">
                                            <div class="inline-flex flex-wrap justify-center items-center gap-2">
                                                <a href="{{ route('raw-materials.edit', $material->id_raw_material) }}"
                                                   class="bg-hazard-50 text-hazard-600 hover:bg-hazard-100 text-xs font-semibold px-2.5 py-1 rounded transition">
                                                    Editar
                                                </a>
                                                <form action="{{ route('raw-materials.destroy', $material->id_raw_material) }}"
                                                      method="POST" class="inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-950/60 text-xs font-semibold px-2.5 py-1 rounded transition"
                                                            onclick="return confirm('¿Eliminar esta materia prima?')">
                                                        Eliminar
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $puedeGestionar ? 7 : 6 }}" class="px-6 py-8 text-center text-gray-400 dark:text-zinc-500 italic">
                                        No se encontraron materias primas con los criterios ingresados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(method_exists($materiales, 'links'))
                    <div class="mt-4">
                        {{ $materiales->links() }}
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>
