<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Editar Proveedor') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-b border-gray-200">

                <h3 class="text-lg font-medium text-gray-900 mb-6">
                    Editando: <span class="text-indigo-600">{{ $supplier->company_name }}</span>
                </h3>

                <form action="{{ route('suppliers.update', $supplier->id_supplier) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Razón social / Nombre <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="company_name"
                               value="{{ old('company_name', $supplier->company_name) }}"
                               class="w-full border-gray-300 rounded-md shadow-sm text-sm"
                               required>
                        @error('company_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Correo electrónico <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="supplier_email"
                               value="{{ old('supplier_email', $supplier->supplier_email) }}"
                               class="w-full border-gray-300 rounded-md shadow-sm text-sm"
                               required>
                        @error('supplier_email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            WhatsApp / Teléfono <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="supplier_phone"
                               value="{{ old('supplier_phone', $supplier->supplier_phone) }}"
                               class="w-full border-gray-300 rounded-md shadow-sm text-sm"
                               required>
                        @error('supplier_phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Materias primas actuales (solo lectura) --}}
                    @if($supplier->rawMaterials->count() > 0)
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Materias primas actuales
                        </label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($supplier->rawMaterials as $material)
                                <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-1 rounded">
                                    {{ $material->description }}
                                </span>
                            @endforeach
                        </div>
                        <p class="mt-2 text-xs text-gray-400">
                            Para agregar nuevas materias primas usá la sección de Materias Primas.
                        </p>
                    </div>
                    @endif

                    <div class="flex items-center gap-4">
                        <button type="submit"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-md transition">
                            Actualizar
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
</x-app-layout>