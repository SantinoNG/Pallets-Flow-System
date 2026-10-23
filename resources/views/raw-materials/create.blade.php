<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-zinc-100 leading-tight">
            {{ __('Nueva Materia Prima') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow-sm sm:rounded-lg p-6 border-b border-gray-200 dark:border-zinc-800">

                <h3 class="text-lg font-medium text-gray-900 dark:text-zinc-100 mb-1">Datos del insumo</h3>
                <p class="text-xs text-gray-500 dark:text-zinc-400 mb-6">
                    El insumo se compra por caja, bolsa, unidad o metro: eso es la presentación.
                </p>

                <form action="{{ route('raw-materials.store') }}" method="POST">
                    @csrf

                    @include('raw-materials.partials.campos', ['material' => null])

                    <div class="flex items-center gap-4 mt-6">
                        <button type="submit"
                                class="bg-hazard-600 hover:bg-hazard-700 text-white text-sm font-medium px-4 py-2 rounded-md transition">
                            Guardar
                        </button>
                        <a href="{{ route('raw-materials.index') }}"
                           class="text-sm text-gray-600 dark:text-zinc-300 bg-gray-100 dark:bg-zinc-950 hover:bg-gray-200 dark:hover:bg-zinc-800 px-4 py-2 rounded-md transition">
                            Cancelar
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
