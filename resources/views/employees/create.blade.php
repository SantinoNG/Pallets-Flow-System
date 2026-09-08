<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Registrar Empleado') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-b border-gray-200">

                <h3 class="text-lg font-medium text-gray-900 mb-6">Datos del nuevo empleado</h3>

                <form action="{{ route('employees.store') }}" method="POST">
                    @csrf

                    {{-- Usuario --}}
                    <div class="mb-4">
                        <label for="id_user" class="block text-sm font-medium text-gray-700 mb-1">
                            Cuenta de usuario <span class="text-red-500">*</span>
                        </label>
                        <select id="id_user" name="id_user"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm
                                       {{ $errors->has('id_user') ? 'border-red-500' : '' }}">
                            <option value="">Seleccioná un usuario...</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id_user }}"
                                    {{ old('id_user') == $user->id_user ? 'selected' : '' }}>
                                    {{ $user->email }}
                                </option>
                            @endforeach
                        </select>
                        @error('id_user')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Rol --}}
                    <div class="mb-4">
                        <label for="id_role" class="block text-sm font-medium text-gray-700 mb-1">
                            Rol <span class="text-red-500">*</span>
                        </label>
                        <select id="id_role" name="id_role"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm
                                       {{ $errors->has('id_role') ? 'border-red-500' : '' }}">
                            <option value="">Seleccioná un rol...</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id_role }}"
                                    {{ old('id_role') == $role->id_role ? 'selected' : '' }}>
                                    {{ $role->role_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('id_role')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Nombre --}}
                    <div class="mb-4">
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">
                            Nombre <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="first_name" name="first_name"
                               value="{{ old('first_name') }}"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm
                                      {{ $errors->has('first_name') ? 'border-red-500' : '' }}"
                               required>
                        @error('first_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Apellido --}}
                    <div class="mb-4">
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">
                            Apellido <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="last_name" name="last_name"
                               value="{{ old('last_name') }}"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm
                                      {{ $errors->has('last_name') ? 'border-red-500' : '' }}"
                               required>
                        @error('last_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Teléfono --}}
                    <div class="mb-6">
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                            Teléfono
                        </label>
                        <input type="text" id="phone" name="phone"
                               value="{{ old('phone') }}"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    </div>

                    {{-- Botones --}}
                    <div class="flex items-center gap-4">
                        <button type="submit"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-md transition">
                            Guardar
                        </button>
                        <a href="{{ route('employees.index') }}"
                           class="text-sm text-gray-600 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-md transition">
                            Cancelar
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>