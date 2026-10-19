<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Gestión de Empleados') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-b border-gray-200">

                <!-- 1. Encabezado con Botón + Nuevo Empleado -->
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-base font-semibold text-gray-800">Listado de Empleados</h3>
                    <a href="{{ route('employees.create') }}"
                       class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-4 py-2 rounded-md transition shadow-sm">
                        + Nuevo Empleado
                    </a>
                </div>

                <!-- 2. Filtros y Buscador arriba -->
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 mb-6">
                    <form action="{{ route('employees.index') }}" method="GET" class="flex flex-wrap md:flex-nowrap items-end gap-3">
                        
                        <!-- Campo: Buscar por Nombre o Apellido -->
                        <div class="flex-1 min-w-[220px]">
                            <label for="search" class="block text-[11px] font-bold text-gray-500 uppercase mb-1">
                                Buscar por Nombre / Apellido
                            </label>
                            <input type="text" 
                                   name="search" 
                                   id="search" 
                                   value="{{ request('search') }}" 
                                   placeholder="Ej: Mateo o Lautaro..." 
                                   class="w-full text-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2">
                        </div>

                        <!-- Campo: Rol / Cargo -->
                        <div class="w-52">
                            <label for="id_role" class="block text-[11px] font-bold text-gray-500 uppercase mb-1">
                                Rol / Cargo
                            </label>
                            <select name="id_role" id="id_role" class="w-full text-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2">
                                <option value="">Todos los roles</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id_role }}" {{ request('id_role') == $role->id_role ? 'selected' : '' }}>
                                        {{ $role->role_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Botones Filtrar y Limpiar -->
                        <div class="flex items-center gap-2">
                            <button type="submit" 
                                    class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-4 py-2 rounded-md transition">
                                Filtrar
                            </button>
                            
                            <a href="{{ route('employees.index') }}" 
                               class="bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-semibold px-4 py-2 rounded-md transition">
                                Limpiar
                            </a>
                        </div>
                    </form>
                </div>

                <!-- 3. Contador de resultados y Botón Exportar PDF -->
                <div class="flex justify-between items-center mb-4">
                    <span class="text-xs text-gray-500 font-medium">
                        {{ method_exists($employees, 'total') ? $employees->total() : count($employees) }} resultado(s) encontrado(s)
                    </span>

                    <a href="{{ route('employees.pdf', request()->all()) }}" 
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

                <!-- 4. Tabla de Empleados -->
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="w-full text-xs text-left text-gray-600">
                        <thead class="text-[11px] text-gray-500 uppercase bg-gray-50 border-b border-gray-200 font-bold">
                            <tr>
                                <th class="px-6 py-3">ID</th>
                                <th class="px-6 py-3">Nombre completo</th>
                                <th class="px-6 py-3">Teléfono</th>
                                <th class="px-6 py-3">Usuario (email)</th>
                                <th class="px-6 py-3">Rol</th>
                                <th class="px-6 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($employees as $employee)
                            <tr class="bg-white hover:bg-gray-50 transition">
                                <td class="px-6 py-4 font-semibold text-gray-900">{{ $employee->id_employee }}</td>
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    {{ $employee->first_name }} {{ $employee->last_name }}
                                </td>
                                <td class="px-6 py-4 text-gray-500">{{ $employee->phone ?? '—' }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ $employee->user->email ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-block bg-blue-100 text-blue-800 text-[10px] font-semibold px-2.5 py-0.5 rounded-full">
                                        {{ $employee->role->role_name ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="inline-flex items-center space-x-2">
                                        <a href="{{ route('employees.edit', $employee->id_employee) }}"
                                           class="bg-blue-50 text-blue-600 hover:bg-blue-100 text-xs font-semibold px-2.5 py-1 rounded transition">
                                            Editar
                                        </a>
                                        <form action="{{ route('employees.destroy', $employee->id_employee) }}"
                                              method="POST"
                                              class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="bg-red-50 text-red-600 hover:bg-red-100 text-xs font-semibold px-2.5 py-1 rounded transition"
                                                    onclick="return confirm('¿Estás seguro de eliminar este empleado?')">
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-400 italic">
                                    No se encontraron empleados con los criterios ingresados.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                @if(method_exists($employees, 'links'))
                    <div class="mt-4">
                        {{ $employees->links() }}
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>