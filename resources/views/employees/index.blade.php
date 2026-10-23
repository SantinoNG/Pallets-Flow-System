<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-zinc-100 leading-tight">
            {{ __('Gestión de Empleados') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow-sm sm:rounded-lg p-6 border-b border-gray-200 dark:border-zinc-800">

                <!-- 1. Encabezado con Botón + Nuevo Empleado -->
                <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-zinc-100">Listado de Empleados</h3>
                    <a href="{{ route('employees.create') }}"
                       class="bg-hazard-600 hover:bg-hazard-700 text-white text-xs font-semibold px-4 py-2 rounded-md transition shadow-sm">
                        + Nuevo Empleado
                    </a>
                </div>

                <!-- 2. Filtros y Buscador arriba -->
                <div class="bg-gray-50 dark:bg-zinc-950 p-4 rounded-lg border border-gray-100 dark:border-zinc-800 mb-6">
                    <form action="{{ route('employees.index') }}" method="GET" class="flex flex-wrap md:flex-nowrap items-end gap-3">
                        
                        <!-- Campo: Buscar por Nombre o Apellido -->
                        <div class="flex-1 min-w-[220px]">
                            <label for="search" class="block text-[11px] font-bold text-gray-500 dark:text-zinc-400 uppercase mb-1">
                                Buscar por Nombre / Apellido
                            </label>
                            <input type="text" 
                                   name="search" 
                                   id="search" 
                                   value="{{ request('search') }}" 
                                   placeholder="Ej: Mateo o Lautaro..." 
                                   class="w-full text-xs rounded-md border-gray-300 dark:border-zinc-700 shadow-sm focus:border-hazard-500 focus:ring-hazard-500 px-3 py-2">
                        </div>

                        <!-- Campo: Rol / Cargo -->
                        <div class="w-52">
                            <label for="id_role" class="block text-[11px] font-bold text-gray-500 dark:text-zinc-400 uppercase mb-1">
                                Rol / Cargo
                            </label>
                            <select name="id_role" id="id_role" class="w-full text-xs rounded-md border-gray-300 dark:border-zinc-700 shadow-sm focus:border-hazard-500 focus:ring-hazard-500 px-3 py-2">
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
                                    class="bg-hazard-600 hover:bg-hazard-700 text-white text-xs font-semibold px-4 py-2 rounded-md transition">
                                Filtrar
                            </button>
                            
                            <a href="{{ route('employees.index') }}" 
                               class="bg-gray-200 dark:bg-zinc-800 hover:bg-gray-300 text-gray-700 dark:text-zinc-200 text-xs font-semibold px-4 py-2 rounded-md transition">
                                Limpiar
                            </a>
                        </div>
                    </form>
                </div>

                <!-- 3. Contador de resultados y Botón Exportar PDF -->
                <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
                    <span class="text-xs text-gray-500 dark:text-zinc-400 font-medium">
                        {{ method_exists($employees, 'total') ? $employees->total() : count($employees) }} resultado(s) encontrado(s)
                    </span>

                    <a href="{{ route('employees.pdf', request()->all()) }}" 
class="bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-3 py-1.5 rounded-md transition flex items-center gap-1.5 shadow-sm">
                        <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20">
                            <path d="M4 18h12v-2H4v2zM12 2H8v6H5l5 5 5-5h-3V2z"/>
                        </svg>
                        Exportar PDF
                    </a>
                </div>

                @if(session('success'))
                    <div class="mb-4 px-4 py-3 bg-green-100 dark:bg-green-950/40 text-green-800 dark:text-green-300 text-xs rounded-md">
                        {{ session('success') }}
                    </div>
                @endif

                <!-- 4. Tabla de Empleados -->
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-zinc-800">
                    <table class="w-full text-xs text-left text-gray-600 dark:text-zinc-300">
                        <thead class="text-[11px] text-gray-500 dark:text-zinc-400 uppercase bg-gray-50 dark:bg-zinc-950 border-b border-gray-200 dark:border-zinc-800 font-bold">
                            <tr>
                                <th class="px-6 py-3">ID</th>
                                <th class="px-6 py-3">Nombre completo</th>
                                <th class="px-6 py-3">Teléfono</th>
                                <th class="px-6 py-3">Usuario (email)</th>
                                <th class="px-6 py-3">Rol</th>
                                <th class="px-6 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                            @forelse($employees as $employee)
                            <tr class="bg-white dark:bg-zinc-900 hover:bg-gray-50 dark:hover:bg-zinc-800 transition">
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-zinc-100 font-mono tabular-nums">{{ $employee->id_employee }}</td>
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-zinc-100">
                                    {{ $employee->first_name }} {{ $employee->last_name }}
                                </td>
                                <td class="px-6 py-4 text-gray-500 dark:text-zinc-400">{{ $employee->phone ?? '—' }}</td>
                                <td class="px-6 py-4 text-gray-500 dark:text-zinc-400">{{ $employee->user->email ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-block bg-hazard-100 text-hazard-800 text-[10px] font-semibold px-2.5 py-0.5 rounded-full">
                                        {{ $employee->role->role_name ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center font-mono tabular-nums">
                                    <div class="inline-flex items-center space-x-4">
                                        <a href="{{ route('employees.edit', $employee->id_employee) }}"
                                           class="bg-hazard-50 text-hazard-600 hover:bg-hazard-100 text-xs font-semibold px-2.5 py-1 rounded transition">
                                            Editar
                                        </a>
                                        <form action="{{ route('employees.destroy', $employee->id_employee) }}"
                                              method="POST"
                                              class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-950/60 text-xs font-semibold px-2.5 py-1 rounded transition"
                                                    onclick="return confirm('¿Estás seguro de eliminar este empleado?')">
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-400 dark:text-zinc-500 italic">
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