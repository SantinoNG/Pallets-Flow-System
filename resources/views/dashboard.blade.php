<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-lg text-zinc-800 dark:text-zinc-100">
            {{ __('Panel de control - PalletStore') }}
        </h2>
    </x-slot>

    <!-- Renderiza el componente reactivo de Livewire -->
    <livewire:dashboard />
</x-app-layout>