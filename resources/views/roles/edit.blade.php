<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Editar Rol') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-b border-gray-200">

                <h3 class="text-lg font-medium text-gray-900 mb-6">
                    Editando: <span class="text-indigo-600">{{ $role->role_name }}</span>
                </h3>

                <form action="{{ route('roles.update', $role->id_role) }}" method="POST">
                    @csrf
                    @method('PUT')

                    {{-- Nombre del rol --}}
                    <div class="mb-4">
                        <label for="role_name" class="block text-sm font-medium text-gray-700 mb-1">
                            Nombre del rol <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="role_name"
                               name="role_name"
                               value="{{ old('role_name', $role->role_name) }}"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm
                                      {{ $errors->has('role_name') ? 'border-red-500' : '' }}"
                               required>
                        @error('role_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Descripción --}}
                    <div class="mb-6">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                            Descripción
                        </label>
                        <input type="text"
                               id="description"
                               name="description"
                               value="{{ old('description', $role->description) }}"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm
                                      {{ $errors->has('description') ? 'border-red-500' : '' }}">
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Botones --}}
                    <div class="flex items-center gap-4">
                        <button type="submit"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-md transition">
                            Actualizar
                        </button>
                        <a href="{{ route('roles.index') }}"
                           class="text-sm text-gray-600 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-md transition">
                            Cancelar
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>