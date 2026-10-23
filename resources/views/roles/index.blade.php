<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-zinc-100 leading-tight">
            {{ __('Gestión de Roles') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow-sm sm:rounded-lg p-6 border-b border-gray-200 dark:border-zinc-800">

                {{-- Encabezado con botón --}}
                <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-zinc-100">Listado de Roles</h3>
                    <a href="{{ route('roles.create') }}"
                       class="bg-hazard-600 hover:bg-hazard-700 text-white text-sm font-medium px-4 py-2 rounded-md transition">
                        + Nuevo Rol
                    </a>
                </div>

                {{-- Mensaje de éxito --}}
                @if(session('success'))
                    <div class="mb-4 px-4 py-3 bg-green-100 dark:bg-green-950/40 text-green-800 dark:text-green-300 text-sm rounded-md">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- Tabla --}}
                <div class="overflow-x-auto shadow-md sm:rounded-lg">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-zinc-400">
                        <thead class="text-xs text-gray-700 dark:text-zinc-200 uppercase bg-gray-50 dark:bg-zinc-950">
                            <tr>
                                <th class="px-6 py-3">ID</th>
                                <th class="px-6 py-3">Nombre</th>
                                <th class="px-6 py-3">Descripción</th>
                                <th class="px-6 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($roles as $role)
                            <tr class="bg-white dark:bg-zinc-900 border-b hover:bg-gray-50 dark:hover:bg-zinc-800">
                                <td class="px-6 py-4 text-gray-900 dark:text-zinc-100 font-mono tabular-nums">{{ $role->id_role }}</td>
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-zinc-100">{{ $role->role_name }}</td>
                                <td class="px-6 py-4">{{ $role->description ?? '—' }}</td>
                                <td class="px-6 py-4 text-center space-x-4 font-mono tabular-nums">
                                    <a href="{{ route('roles.edit', $role->id_role) }}"
                                       class="font-medium text-hazard-600 hover:text-hazard-900 bg-hazard-50 px-3 py-2 rounded-md transition">
                                        Editar
                                    </a>
                                    <form action="{{ route('roles.destroy', $role->id_role) }}"
                                          method="POST"
                                          style="display:inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="font-medium text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 bg-red-50 dark:bg-red-950/40 px-3 py-2 rounded-md transition"
                                                onclick="return confirm('¿Estás seguro de eliminar este rol?')">
                                            Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>