<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Agregar Artículo al Catálogo') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-b border-gray-200">

                <h3 class="text-lg font-medium text-gray-900 mb-6">Datos del artículo</h3>

                <form action="{{ route('articles.store') }}" method="POST">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">

                        {{-- Nombre --}}
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Nombre del artículo <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="article_name"
                                   value="{{ old('article_name') }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm text-sm
                                          {{ $errors->has('article_name') ? 'border-red-500' : '' }}"
                                   required>
                            @error('article_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Descripción --}}
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                            <textarea name="description" rows="2"
                                      class="w-full border-gray-300 rounded-md shadow-sm text-sm">{{ old('description') }}</textarea>
                        </div>

                        {{-- Precio --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Precio unitario <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="unit_price" step="0.01" min="0.01"
                                   value="{{ old('unit_price') }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm text-sm
                                          {{ $errors->has('unit_price') ? 'border-red-500' : '' }}"
                                   required>
                            @error('unit_price')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Stock inicial --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Stock inicial <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="remaining_quantity" min="0"
                                   value="{{ old('remaining_quantity', 0) }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm text-sm
                                          {{ $errors->has('remaining_quantity') ? 'border-red-500' : '' }}"
                                   required>
                            @error('remaining_quantity')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Stock mínimo --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Stock mínimo <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="minimum_stock" min="0"
                                   value="{{ old('minimum_stock', 0) }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm text-sm
                                          {{ $errors->has('minimum_stock') ? 'border-red-500' : '' }}"
                                   required>
                            @error('minimum_stock')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>

                    {{-- Materias primas --}}
                    @if($rawMaterials->count() > 0)
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Materias primas utilizadas
                        </label>
                        <div class="border border-gray-200 rounded-md divide-y">
                            @foreach($rawMaterials as $material)
                            <div class="flex items-center gap-4 px-4 py-3">
                                <input type="checkbox"
                                       name="raw_materials[{{ $material->id_raw_material }}][selected]"
                                       value="1"
                                       id="mat_{{ $material->id_raw_material }}"
                                       class="rounded border-gray-300 text-indigo-600">
                                <label for="mat_{{ $material->id_raw_material }}"
                                       class="flex-1 text-sm text-gray-700">
                                    {{ $material->description }}
                                </label>
                                <input type="number"
                                       name="raw_materials[{{ $material->id_raw_material }}][quantity]"
                                       placeholder="Cantidad"
                                       min="0" step="0.01"
                                       class="w-24 border-gray-300 rounded-md shadow-sm text-sm">
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="flex items-center gap-4">
                        <button type="submit"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-md transition">
                            Guardar
                        </button>
                        <a href="{{ route('articles.index') }}"
                           class="text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-md transition">
                            Cancelar
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>