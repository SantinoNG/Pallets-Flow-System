<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Gestión de Roles') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-b border-gray-200">

                {{-- Encabezado con botón --}}
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-medium text-gray-900">Listado de Roles</h3>
                    <a href="{{ route('roles.create') }}"
                       class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-md transition">
                        + Nuevo Rol
                    </a>
                </div>

                {{-- Mensaje de éxito --}}
                @if(session('success'))
                    <div class="mb-4 px-4 py-3 bg-green-100 text-green-800 text-sm rounded-md">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- Tabla --}}
                <div class="overflow-x-auto shadow-md sm:rounded-lg">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-6 py-3">ID</th>
                                <th class="px-6 py-3">Nombre</th>
                                <th class="px-6 py-3">Descripción</th>
                                <th class="px-6 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($roles as $role)
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-6 py-4 text-gray-900">{{ $role->id_role }}</td>
                                <td class="px-6 py-4 font-medium text-gray-900">{{ $role->role_name }}</td>
                                <td class="px-6 py-4">{{ $role->description ?? '—' }}</td>
                                <td class="px-6 py-4 text-center space-x-2">
                                    <a href="{{ route('roles.edit', $role->id_role) }}"
                                       class="font-medium text-indigo-600 hover:text-indigo-900 bg-indigo-50 px-3 py-2 rounded-md transition">
                                        Editar
                                    </a>
                                    <form action="{{ route('roles.destroy', $role->id_role) }}"
                                          method="POST"
                                          style="display:inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="font-medium text-red-600 hover:text-red-900 bg-red-50 px-3 py-2 rounded-md transition"
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