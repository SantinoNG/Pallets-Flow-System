<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Bienvenida --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-1">
                    Bienvenido, {{ auth()->user()->name }} 
                </h3>
                <p class="text-sm text-gray-500">
                    Panel de administración del sistema PalletStore.
                </p>
            </div>

            {{-- Módulo: Administración --}}
            <div class="mb-8">
                <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">
                    Administración
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

                    <a href="{{ route('roles.index') }}"
                       class="bg-white shadow-sm sm:rounded-lg p-5 border border-gray-200 hover:border-indigo-400 hover:shadow-md transition group">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center text-indigo-600 text-xl">
                                
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 group-hover:text-indigo-600 transition">
                                    Roles
                                </p>
                                <p class="text-xs text-gray-500">Crear, editar y eliminar roles</p>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('employees.index') }}"
                       class="bg-white shadow-sm sm:rounded-lg p-5 border border-gray-200 hover:border-indigo-400 hover:shadow-md transition group">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center text-indigo-600 text-xl">
                                
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 group-hover:text-indigo-600 transition">
                                    Empleados
                                </p>
                                <p class="text-xs text-gray-500">Gestión del personal del sistema</p>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('users.index') }}"
                       class="bg-white shadow-sm sm:rounded-lg p-5 border border-gray-200 hover:border-indigo-400 hover:shadow-md transition group">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center text-indigo-600 text-xl">
                                
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 group-hover:text-indigo-600 transition">
                                    Usuarios
                                </p>
                                <p class="text-xs text-gray-500">Gestión de cuentas del sistema</p>
                            </div>
                        </div>
                    </a>

                </div>
            </div>

        </div>
    </div>
</x-app-layout>