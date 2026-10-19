<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Registrar Proveedor') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-b border-gray-200">

                <h3 class="text-lg font-medium text-gray-900 mb-6">Datos del proveedor</h3>

                <form action="{{ route('suppliers.store') }}" method="POST">
                    @csrf

                    {{-- Razón social --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Razón social / Nombre <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="company_name"
                               value="{{ old('company_name') }}"
                               class="w-full border-gray-300 rounded-md shadow-sm text-sm
                                      {{ $errors->has('company_name') ? 'border-red-500' : '' }}"
                               required>
                        @error('company_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Correo --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Correo electrónico <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="supplier_email"
                               value="{{ old('supplier_email') }}"
                               class="w-full border-gray-300 rounded-md shadow-sm text-sm
                                      {{ $errors->has('supplier_email') ? 'border-red-500' : '' }}"
                               required>
                        @error('supplier_email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Teléfono --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            WhatsApp / Teléfono <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="supplier_phone"
                               value="{{ old('supplier_phone') }}"
                               class="w-full border-gray-300 rounded-md shadow-sm text-sm
                                      {{ $errors->has('supplier_phone') ? 'border-red-500' : '' }}"
                               required>
                        @error('supplier_phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Materias primas --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Materias primas que provee <span class="text-red-500">*</span>
                        </label>
                        <div id="materials-list" class="space-y-2 mb-3">
                            <div class="flex gap-2">
                                <input type="text" name="materials[]"
                                       placeholder="Ej: Pallets de madera"
                                       class="flex-1 border-gray-300 rounded-md shadow-sm text-sm">
                            </div>
                        </div>
                        @error('materials')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        @error('materials.*')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <button type="button" onclick="addMaterial()"
                                class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                            + Agregar otra materia prima
                        </button>
                    </div>

                    <div class="flex items-center gap-4">
                        <button type="submit"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-md transition">
                            Guardar
                        </button>
                        <a href="{{ route('suppliers.index') }}"
                           class="text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-md transition">
                            Cancelar
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script>
        function addMaterial() {
            const list = document.getElementById('materials-list');
            const div = document.createElement('div');
            div.className = 'flex gap-2';
            div.innerHTML = `
                <input type="text" name="materials[]"
                       placeholder="Ej: Tornillos"
                       class="flex-1 border-gray-300 rounded-md shadow-sm text-sm">
                <button type="button" onclick="this.parentElement.remove()"
                        class="text-red-500 hover:text-red-700 text-sm font-medium px-2">
                    ✕
                </button>`;
            list.appendChild(div);
        }
    </script>
</x-app-layout>