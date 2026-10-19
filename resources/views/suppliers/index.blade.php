<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Gestión de Proveedores') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-b border-gray-200">

                <!-- 1. Encabezado de la tarjeta -->
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-base font-semibold text-gray-800">Listado de Proveedores</h3>
                    <a href="{{ route('suppliers.create') }}"
                       class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-4 py-2 rounded-md transition shadow-sm">
                        + Nuevo Proveedor
                    </a>
                </div>

                <!-- 2. Filtros y Buscador arriba -->
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 mb-6">
                    <form action="{{ route('suppliers.index') }}" method="GET" class="flex flex-wrap md:flex-nowrap items-end gap-3">
                        
                        <!-- Campo: Razón Social / Empresa -->
                        <div class="flex-1 min-w-[200px]">
                            <label for="company_name" class="block text-[11px] font-bold text-gray-500 uppercase mb-1">
                                Razón Social / Empresa
                            </label>
                            <input type="text" 
                                   name="company_name" 
                                   id="company_name" 
                                   value="{{ request('company_name') }}" 
                                   placeholder="Buscar por empresa..." 
                                   class="w-full text-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2">
                        </div>

                        <!-- Campo: Materia Prima -->
                        <div class="flex-1 min-w-[200px]">
                            <label for="raw_material" class="block text-[11px] font-bold text-gray-500 uppercase mb-1">
                                Materia Prima
                            </label>
                            <input type="text" 
                                   name="raw_material" 
                                   id="raw_material" 
                                   value="{{ request('raw_material') }}" 
                                   placeholder="Ej: Pallets, Tornillos..." 
                                   class="w-full text-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2">
                        </div>

                        <!-- Botones Filtrar y Limpiar -->
                        <div class="flex items-center gap-2">
                            <button type="submit" 
                                    class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-4 py-2 rounded-md transition">
                                Filtrar
                            </button>
                            
                            <a href="{{ route('suppliers.index') }}" 
                               class="bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-semibold px-4 py-2 rounded-md transition">
                                Limpiar
                            </a>
                        </div>
                    </form>
                </div>

                <!-- 3. Contador de resultados y Botón Exportar PDF -->
                <div class="flex justify-between items-center mb-4">
                    <span class="text-xs text-gray-500 font-medium">
                        {{ method_exists($suppliers, 'total') ? $suppliers->total() : count($suppliers) }} resultado(s) encontrado(s)
                    </span>

                    <a href="{{ route('suppliers.pdf', request()->all()) }}" 
                       target="_blank" 
                       class="bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-3 py-1.5 rounded-md transition flex items-center gap-1.5 shadow-sm">
                        <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20">
                            <path d="M4 18h12v-2H4v2zM12 2H8v6H5l5 5 5-5h-3V2z"/>
                        </svg>
                        Exportar PDF
                    </a>
                </div>

                @if(session('success'))
                    <div class="mb-4 px-4 py-3 bg-green-100 text-green-800 text-xs rounded-md">
                        {{ session('success') }}
                    </div>
                @endif

                <!-- 4. Tabla de registros -->
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="w-full text-xs text-left text-gray-600">
                        <thead class="text-[11px] text-gray-500 uppercase bg-gray-50 border-b border-gray-200 font-bold">
                            <tr>
                                <th class="px-6 py-3">Razón Social</th>
                                <th class="px-6 py-3">Correo</th>
                                <th class="px-6 py-3">Teléfono</th>
                                <th class="px-6 py-3">Materias Primas</th>
                                <th class="px-6 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($suppliers as $supplier)
                            <tr class="bg-white hover:bg-gray-50 transition">
                                <td class="px-6 py-4 font-semibold text-gray-900">
                                    {{ $supplier->company_name }}
                                </td>
                                <td class="px-6 py-4 text-gray-500">{{ $supplier->supplier_email }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ $supplier->supplier_phone }}</td>
                                <td class="px-6 py-4">
                                    @forelse($supplier->rawMaterials as $material)
                                        <span class="inline-block bg-blue-100 text-blue-800 text-[10px] font-semibold px-2 py-0.5 rounded-full mr-1 mb-0.5">
                                            {{ $material->description }}
                                        </span>
                                    @empty
                                        <span class="text-gray-400 italic">Sin materias primas</span>
                                    @endforelse
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="inline-flex items-center space-x-2">
                                        <a href="{{ route('suppliers.edit', $supplier->id_supplier) }}"
                                           class="bg-blue-50 text-blue-600 hover:bg-blue-100 text-xs font-semibold px-2.5 py-1 rounded transition">
                                            Editar
                                        </a>
                                        <form action="{{ route('suppliers.destroy', $supplier->id_supplier) }}"
                                              method="POST" 
                                              class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="bg-red-50 text-red-600 hover:bg-red-100 text-xs font-semibold px-2.5 py-1 rounded transition"
                                                    onclick="return confirm('¿Estás seguro de eliminar este proveedor?')">
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-400 italic">
                                    No se encontraron proveedores con los criterios ingresados.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                @if(method_exists($suppliers, 'links'))
                    <div class="mt-4">
                        {{ $suppliers->links() }}
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>