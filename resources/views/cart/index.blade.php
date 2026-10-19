<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Nueva Venta') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-b border-gray-200">

                <h3 class="text-lg font-medium text-gray-900 mb-6">
                    Seleccioná el cliente para iniciar la venta
                </h3>

                @if(session('error'))
                    <div class="mb-4 px-4 py-3 bg-red-100 text-red-800 text-sm rounded-md">
                        {{ session('error') }}
                    </div>
                @endif

                <form action="{{ route('cart.create') }}" method="POST">
                    @csrf

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Cliente <span class="text-red-500">*</span>
                        </label>
                        <select name="id_customer"
                                class="w-full border-gray-300 rounded-md shadow-sm text-sm
                                       {{ $errors->has('id_customer') ? 'border-red-500' : '' }}"
                                required>
                            <option value="">Seleccioná un cliente...</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id_customer }}">
                                    {{ $customer->first_name }} {{ $customer->last_name }}
                                    — {{ $customer->user->email ?? '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('id_customer')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-4">
                        <button type="submit"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-md transition">
                            Iniciar venta
                        </button>
                        <a href="{{ route('dashboard') }}"
                           class="text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-md transition">
                            Cancelar
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>