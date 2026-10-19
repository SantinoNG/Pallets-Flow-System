<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-lg text-slate-800">
            {{ __('Resumen General - PalletStore') }}
        </h2>
    </x-slot>

    <!-- Renderiza el componente reactivo de Livewire -->
    <livewire:dashboard />
</x-app-layout>