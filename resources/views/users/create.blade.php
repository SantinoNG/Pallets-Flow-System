<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-zinc-100 leading-tight">
            {{ __('Nuevo Usuario') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow-sm sm:rounded-lg p-6 border-b border-gray-200 dark:border-zinc-800">

                <h3 class="text-lg font-medium text-gray-900 dark:text-zinc-100 mb-2">Datos de acceso</h3>

                <p class="text-sm text-gray-500 dark:text-zinc-400 mb-6">
                    Un usuario es <strong>O empleado O cliente</strong>, segun el rol que elijas.
                    Si es cliente, tambien se le crea la ficha con sus datos de contacto.
                </p>

                <form action="{{ route('users.store') }}" method="POST">
                    @csrf

                    {{-- Nombre --}}
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-zinc-200 mb-1">
                            Nombre y apellido <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="name"
                               name="name"
                               value="{{ old('name') }}"
                               placeholder="Ej: Juan Perez"
                               class="w-full border-gray-300 dark:border-zinc-700 rounded-md shadow-sm focus:ring-hazard-500 focus:border-hazard-500 text-sm {{ $errors->has('name') ? 'border-red-500' : '' }}"
                               required>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-zinc-200 mb-1">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email"
                               id="email"
                               name="email"
                               value="{{ old('email') }}"
                               placeholder="nombre@dominio.com"
                               class="w-full border-gray-300 dark:border-zinc-700 rounded-md shadow-sm focus:ring-hazard-500 focus:border-hazard-500 text-sm {{ $errors->has('email') ? 'border-red-500' : '' }}"
                               required>
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        {{-- Contrasena --}}
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-zinc-200 mb-1">
                                Contraseña <span class="text-red-500">*</span>
                            </label>
                            <input type="password"
                                   id="password"
                                   name="password"
                                   class="w-full border-gray-300 dark:border-zinc-700 rounded-md shadow-sm focus:ring-hazard-500 focus:border-hazard-500 text-sm {{ $errors->has('password') ? 'border-red-500' : '' }}"
                                   required>
                            <p class="mt-1 text-xs text-gray-500 dark:text-zinc-400">Minimo 8 caracteres.</p>
                            @error('password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Confirmacion --}}
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-zinc-200 mb-1">
                                Repetir contraseña <span class="text-red-500">*</span>
                            </label>
                            <input type="password"
                                   id="password_confirmation"
                                   name="password_confirmation"
                                   class="w-full border-gray-300 dark:border-zinc-700 rounded-md shadow-sm focus:ring-hazard-500 focus:border-hazard-500 text-sm {{ $errors->has('password_confirmation') ? 'border-red-500' : '' }}"
                                   required>
                            @error('password_confirmation')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Rol --}}
                    <div class="mb-6">
                        <label for="id_role" class="block text-sm font-medium text-gray-700 dark:text-zinc-200 mb-1">
                            Rol <span class="text-red-500">*</span>
                        </label>
                        <select id="id_role"
                                name="id_role"
                                class="w-full border-gray-300 dark:border-zinc-700 rounded-md shadow-sm text-sm {{ $errors->has('id_role') ? 'border-red-500' : '' }}"
                                required>
                            <option value="">Seleccioná un rol...</option>
                            @foreach($roles as $rol)
                                <option value="{{ $rol->id_role }}" @selected((string) old('id_role') === (string) $rol->id_role)>
                                    {{ $rol->role_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('id_role')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{--
                        Ficha de cliente. Se muestra siempre, pero solo es
                        obligatoria cuando el rol es Cliente: es lo que el
                        servidor valida.
                    --}}
                    <div class="border-t border-gray-200 dark:border-zinc-800 pt-6 mb-6">
                        <h4 class="text-sm font-semibold text-gray-800 dark:text-zinc-100 mb-1">
                            Ficha de cliente
                        </h4>
                        <p class="text-xs text-gray-500 dark:text-zinc-400 mb-4">
                            Se completa solo si el rol es <strong>Cliente</strong>.
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-gray-700 dark:text-zinc-200 mb-1">
                                    Nombre
                                </label>
                                <input type="text"
                                       id="first_name"
                                       name="first_name"
                                       value="{{ old('first_name') }}"
                                       class="w-full border-gray-300 dark:border-zinc-700 rounded-md shadow-sm focus:ring-hazard-500 focus:border-hazard-500 text-sm {{ $errors->has('first_name') ? 'border-red-500' : '' }}">
                                @error('first_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="last_name" class="block text-sm font-medium text-gray-700 dark:text-zinc-200 mb-1">
                                    Apellido
                                </label>
                                <input type="text"
                                       id="last_name"
                                       name="last_name"
                                       value="{{ old('last_name') }}"
                                       class="w-full border-gray-300 dark:border-zinc-700 rounded-md shadow-sm focus:ring-hazard-500 focus:border-hazard-500 text-sm {{ $errors->has('last_name') ? 'border-red-500' : '' }}">
                                @error('last_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-zinc-200 mb-1">
                                    Teléfono
                                </label>
                                <input type="text"
                                       id="phone"
                                       name="phone"
                                       value="{{ old('phone') }}"
                                       placeholder="3764-000000"
                                       class="w-full border-gray-300 dark:border-zinc-700 rounded-md shadow-sm focus:ring-hazard-500 focus:border-hazard-500 text-sm {{ $errors->has('phone') ? 'border-red-500' : '' }}">
                                @error('phone')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="address" class="block text-sm font-medium text-gray-700 dark:text-zinc-200 mb-1">
                                    Dirección
                                </label>
                                <input type="text"
                                       id="address"
                                       name="address"
                                       value="{{ old('address') }}"
                                       class="w-full border-gray-300 dark:border-zinc-700 rounded-md shadow-sm focus:ring-hazard-500 focus:border-hazard-500 text-sm {{ $errors->has('address') ? 'border-red-500' : '' }}">
                                @error('address')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Botones --}}
                    <div class="flex items-center gap-4">
                        <button type="submit"
                                class="bg-hazard-600 hover:bg-hazard-700 text-white text-sm font-medium px-4 py-2 rounded-md transition">
                            Guardar usuario
                        </button>
                        <a href="{{ route('users.index') }}"
                           class="text-sm text-gray-600 dark:text-zinc-300 hover:text-gray-900 dark:hover:text-zinc-100 bg-gray-100 dark:bg-zinc-950 hover:bg-gray-200 dark:hover:bg-zinc-700 px-4 py-2 rounded-md transition">
                            Cancelar
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
