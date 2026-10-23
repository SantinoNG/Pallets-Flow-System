{{--
    Campos compartidos por el alta y la edición de una materia prima.

    `$material` puede venir null (alta) o con el modelo cargado (edición).
    La presentación es la unidad de compra: caja, bolsa, unidad, metro...
--}}
@php
    $esEdicion = isset($material) && $material;
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    {{-- Descripción --}}
    <div class="sm:col-span-2">
        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-zinc-200 mb-1">
            Materia prima <span class="text-red-500">*</span>
        </label>
        <input type="text" name="description" id="description"
               value="{{ old('description', $esEdicion ? $material->description : '') }}"
               placeholder="Ej: Bisagras metálicas"
               class="w-full border-gray-300 dark:border-zinc-700 rounded-md shadow-sm text-sm {{ $errors->has('description') ? 'border-red-500' : '' }}"
               required>
        @error('description')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- Presentación --}}
    <div>
        <label for="unit" class="block text-sm font-medium text-gray-700 dark:text-zinc-200 mb-1">
            Presentación <span class="text-red-500">*</span>
        </label>
        <select name="unit" id="unit"
                class="w-full border-gray-300 dark:border-zinc-700 rounded-md shadow-sm text-sm {{ $errors->has('unit') ? 'border-red-500' : '' }}"
                required>
            @foreach(\App\Models\RawMaterial::UNIDADES as $clave => [$singular, $plural])
                <option value="{{ $clave }}" @selected(old('unit', $esEdicion ? $material->unit : 'unidad') === $clave)>
                    {{ ucfirst($plural) }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-500 dark:text-zinc-400">
            Cómo se compra: caja de bisagras, bolsa de clavos, por unidad, metro de madera...
        </p>
        @error('unit')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- Costo unitario --}}
    <div>
        <label for="unit_cost" class="block text-sm font-medium text-gray-700 dark:text-zinc-200 mb-1">
            Costo por unidad <span class="text-red-500">*</span>
        </label>
        <input type="number" name="unit_cost" id="unit_cost" step="0.01" min="0"
               value="{{ old('unit_cost', $esEdicion ? $material->unit_cost : '') }}"
               placeholder="0.00"
               class="w-full border-gray-300 dark:border-zinc-700 rounded-md shadow-sm text-sm {{ $errors->has('unit_cost') ? 'border-red-500' : '' }}"
               required>
        <p class="mt-1 text-xs text-gray-500 dark:text-zinc-400">
            Lo que sale una unidad de compra (una caja, una bolsa, un metro...).
        </p>
        @error('unit_cost')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- Proveedor --}}
    <div class="sm:col-span-2">
        <label for="id_supplier" class="block text-sm font-medium text-gray-700 dark:text-zinc-200 mb-1">
            Proveedor que la brinda <span class="text-red-500">*</span>
        </label>
        <select name="id_supplier" id="id_supplier"
                class="w-full border-gray-300 dark:border-zinc-700 rounded-md shadow-sm text-sm {{ $errors->has('id_supplier') ? 'border-red-500' : '' }}"
                required>
            <option value="">Elegí un proveedor</option>
            @foreach($proveedores as $proveedor)
                <option value="{{ $proveedor->id_supplier }}" @selected((string) old('id_supplier', $esEdicion ? $material->id_supplier : '') === (string) $proveedor->id_supplier)>
                    {{ $proveedor->company_name }}
                </option>
            @endforeach
        </select>
        @error('id_supplier')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- Stock actual --}}
    <div>
        <label for="remaining_quantity" class="block text-sm font-medium text-gray-700 dark:text-zinc-200 mb-1">
            Stock actual <span class="text-red-500">*</span>
        </label>
        <input type="number" name="remaining_quantity" id="remaining_quantity" step="1" min="0"
               value="{{ old('remaining_quantity', $esEdicion ? ($material->stock->remaining_quantity ?? 0) : 0) }}"
               class="w-full border-gray-300 dark:border-zinc-700 rounded-md shadow-sm text-sm {{ $errors->has('remaining_quantity') ? 'border-red-500' : '' }}"
               required>
        @error('remaining_quantity')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    {{-- Stock mínimo --}}
    <div>
        <label for="minimum_stock" class="block text-sm font-medium text-gray-700 dark:text-zinc-200 mb-1">
            Stock mínimo <span class="text-red-500">*</span>
        </label>
        <input type="number" name="minimum_stock" id="minimum_stock" step="1" min="0"
               value="{{ old('minimum_stock', $esEdicion ? ($material->stock->minimum_stock ?? 0) : 0) }}"
               class="w-full border-gray-300 dark:border-zinc-700 rounded-md shadow-sm text-sm {{ $errors->has('minimum_stock') ? 'border-red-500' : '' }}"
               required>
        <p class="mt-1 text-xs text-gray-500 dark:text-zinc-400">
            Debajo de esta cantidad, el insumo aparece como "bajo mínimo".
        </p>
        @error('minimum_stock')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>
</div>
