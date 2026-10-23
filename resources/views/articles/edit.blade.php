<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-zinc-100 leading-tight">
            {{ __('Modificar Artículo') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow-sm sm:rounded-lg p-6 border-b border-gray-200 dark:border-zinc-800">

                <h3 class="text-lg font-medium text-gray-900 dark:text-zinc-100 mb-6">
                    Editando: <span class="text-hazard-600">{{ $article->article_name }}</span>
                </h3>

                <form action="{{ route('articles.update', $article->id_article) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">

                        {{-- Foto --}}
                        <div class="sm:col-span-2">
                            <x-image-upload :current="$article->imagen_url" />
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-zinc-200 mb-1">
                                Nombre del artículo <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="article_name"
                                   value="{{ old('article_name', $article->article_name) }}"
                                   class="w-full border-gray-300 dark:border-zinc-700 rounded-md shadow-sm text-sm {{ $errors->has('article_name') ? 'border-red-500' : '' }}"
                                   required>
                            @error('article_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-zinc-200 mb-1">Descripción</label>
                            <textarea name="description" rows="2"
                                      class="w-full border-gray-300 dark:border-zinc-700 rounded-md shadow-sm text-sm">{{ old('description', $article->description) }}</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-zinc-200 mb-1">
                                Precio unitario <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="unit_price" step="0.01" min="0.01"
                                   value="{{ old('unit_price', $article->unit_price) }}"
                                   class="w-full border-gray-300 dark:border-zinc-700 rounded-md shadow-sm text-sm"
                                   required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-zinc-200 mb-1">
                                Stock actual <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="remaining_quantity" min="0"
                                   value="{{ old('remaining_quantity', $article->stock->remaining_quantity ?? 0) }}"
                                   class="w-full border-gray-300 dark:border-zinc-700 rounded-md shadow-sm text-sm"
                                   required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-zinc-200 mb-1">
                                Stock mínimo <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="minimum_stock" min="0"
                                   value="{{ old('minimum_stock', $article->stock->minimum_stock ?? 0) }}"
                                   class="w-full border-gray-300 dark:border-zinc-700 rounded-md shadow-sm text-sm"
                                   required>
                        </div>

                    </div>

                    {{-- Materias primas con valores pre-cargados --}}
                    @if($rawMaterials->count() > 0)
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-zinc-200 mb-1">
                            Materias primas utilizadas
                        </label>
                        <p class="text-xs text-gray-500 dark:text-zinc-400 mb-2">
                            Cada insumo se mide en su presentación: unidad, rollo, metro, kg, litro...
                            La presentación se guarda en la materia prima y se usa en todos los planos.
                        </p>
                        <div class="border border-gray-200 dark:border-zinc-800 rounded-md divide-y divide-gray-100 dark:divide-zinc-800">
                            @foreach($rawMaterials as $material)
                            @php
                                $selected = $selectedMaterials->get($material->id_raw_material);
                                $clave = 'raw_materials.' . $material->id_raw_material;
                                $unidadActual = old($clave . '.unit', $material->unit);
                                $cantidadActual = old($clave . '.quantity', $selected ? $selected->pivot->quantity_required : '');
                                $esContinua = in_array($unidadActual, ['metro', 'litro', 'kg'], true);
                            @endphp
                            <div class="flex flex-wrap items-center gap-3 px-4 py-3" data-material-row>
                                <input type="checkbox"
                                       name="raw_materials[{{ $material->id_raw_material }}][selected]"
                                       value="1"
                                       id="mat_{{ $material->id_raw_material }}"
                                       class="rounded border-gray-300 dark:border-zinc-700 text-hazard-600"
                                       {{ $selected ? 'checked' : '' }}>
                                <label for="mat_{{ $material->id_raw_material }}"
                                       class="flex-1 min-w-[160px] text-sm text-gray-700 dark:text-zinc-200">
                                    {{ $material->description }}
                                </label>

                                {{-- La presentación se puede cambiar según cómo se compre el insumo. --}}
                                <div class="flex items-center gap-2">
                                    <label for="unit_{{ $material->id_raw_material }}"
                                           class="text-[10px] font-bold uppercase text-gray-500 dark:text-zinc-400">
                                        Presentación
                                    </label>
                                    <select name="raw_materials[{{ $material->id_raw_material }}][unit]"
                                            id="unit_{{ $material->id_raw_material }}"
                                            data-unit-select
                                            class="w-28 text-xs border-gray-300 dark:border-zinc-700 rounded-md shadow-sm">
                                        @foreach(\App\Models\RawMaterial::UNIDADES as $claveUnidad => [$singular, $plural])
                                            <option value="{{ $claveUnidad }}" @selected($unidadActual === $claveUnidad)>
                                                {{ ucfirst($plural) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="flex items-center gap-2">
                                    <label for="qty_{{ $material->id_raw_material }}"
                                           class="text-[10px] font-bold uppercase text-gray-500 dark:text-zinc-400">
                                        Cantidad
                                    </label>
                                    <input type="number"
                                           name="raw_materials[{{ $material->id_raw_material }}][quantity]"
                                           id="qty_{{ $material->id_raw_material }}"
                                           placeholder="0"
                                           min="0" step="{{ $esContinua ? '0.01' : '1' }}"
                                           value="{{ $cantidadActual }}"
                                           data-quantity
                                           class="w-24 border-gray-300 dark:border-zinc-700 rounded-md shadow-sm text-sm">
                                    <span data-unit-label class="text-[11px] text-gray-400 dark:text-zinc-500">
                                        {{ $material->etiquetaUnidad(2) }}
                                    </span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="flex items-center gap-4">
                        <button type="submit"
                                class="bg-hazard-600 hover:bg-hazard-700 text-white text-sm font-medium px-4 py-2 rounded-md transition">
                            Actualizar
                        </button>
                        <a href="{{ route('articles.index') }}"
                           class="text-sm text-gray-600 dark:text-zinc-300 bg-gray-100 dark:bg-zinc-950 hover:bg-gray-200 dark:hover:bg-zinc-700 px-4 py-2 rounded-md transition">
                            Cancelar
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>

    {{--
        Al cambiar la presentación, el campo de cantidad se adapta:
        los insumos que se miden en metros, litros o kg aceptan decimales;
        los que se compran por unidad, bolsa, caja, rollo, pliego o plancha
        van de a uno.
    --}}
    <script>
        document.querySelectorAll('[data-material-row]').forEach(function (fila) {
            const selector = fila.querySelector('[data-unit-select]');
            const cantidad = fila.querySelector('[data-quantity]');
            const etiqueta = fila.querySelector('[data-unit-label]');
            const continuas = ['metro', 'litro', 'kg'];

            function actualizarPresentacion() {
                cantidad.step = continuas.indexOf(selector.value) !== -1 ? '0.01' : '1';
                etiqueta.textContent = selector.options[selector.selectedIndex].text.toLowerCase();
            }

            selector.addEventListener('change', actualizarPresentacion);
        });
    </script>
</x-app-layout>
