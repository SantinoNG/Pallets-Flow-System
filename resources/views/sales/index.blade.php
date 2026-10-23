<x-app-layout>
    {{-- Para el Cliente, el listado es el de SUS compras. El flag
         $esCliente lo pasa el controlador (SaleController@index). --}}

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-zinc-100 leading-tight">
            {{ $esCliente ? 'Mis compras' : 'Ventas' }}
            <span class="text-sm font-normal text-gray-500 dark:text-zinc-400">
                — {{ $ventas->total() }} {{ $esCliente ? 'compra(s)' : 'venta(s)' }} · total ${{ number_format($totalFiltrado, 2, ',', '.') }}
            </span>
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 px-4 py-3 bg-green-100 dark:bg-green-950/40 text-green-800 dark:text-green-300 text-sm rounded-md">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Filtros --}}
            <form method="GET" action="{{ route('sales.index') }}"
                  class="bg-gray-50 dark:bg-zinc-950 rounded-lg p-4 mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div>
                    <label for="pago" class="block text-xs font-semibold text-gray-600 dark:text-zinc-300 mb-1">Medio de pago</label>
                    <select name="pago" id="pago"
                            class="w-full rounded border-gray-300 dark:border-zinc-700 text-sm shadow-sm focus:border-hazard-500 focus:ring-hazard-500">
                        <option value="">Todos</option>
                        @foreach($mediosPago as $medio)
                            <option value="{{ $medio }}" @selected(request('pago') === $medio)>{{ $medio }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- El filtro por empleado es del personal: al Cliente le
                     mostraría los nombres de los empleados, que no son
                     información suya. Su listado ya viene filtrado por su
                     propia ficha. --}}
                @unless ($esCliente)
                    <div>
                        <label for="empleado" class="block text-xs font-semibold text-gray-600 dark:text-zinc-300 mb-1">Empleado</label>
                        <select name="empleado" id="empleado"
                                class="w-full rounded border-gray-300 dark:border-zinc-700 text-sm shadow-sm focus:border-hazard-500 focus:ring-hazard-500">
                            <option value="">Todos</option>
                            @foreach($empleados as $emp)
                                <option value="{{ $emp->id_employee }}" @selected((string) request('empleado') === (string) $emp->id_employee)>
                                    {{ $emp->first_name }} {{ $emp->last_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endunless

                <div>
                    <label for="desde" class="block text-xs font-semibold text-gray-600 dark:text-zinc-300 mb-1">Desde</label>
                    <input type="date" name="desde" id="desde" value="{{ request('desde') }}"
                           class="w-full rounded border-gray-300 dark:border-zinc-700 text-sm shadow-sm focus:border-hazard-500 focus:ring-hazard-500">
                </div>

                <div>
                    <label for="hasta" class="block text-xs font-semibold text-gray-600 dark:text-zinc-300 mb-1">Hasta</label>
                    <input type="date" name="hasta" id="hasta" value="{{ request('hasta') }}"
                           class="w-full rounded border-gray-300 dark:border-zinc-700 text-sm shadow-sm focus:border-hazard-500 focus:ring-hazard-500">
                </div>

                <div class="flex items-end gap-2">
                    <x-primary-button>Filtrar</x-primary-button>
                    @if(request()->hasAny(['pago', 'empleado', 'desde', 'hasta']))
                        <a href="{{ route('sales.index') }}"
                           class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-zinc-800 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-zinc-200 uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-hazard-500 focus:ring-offset-2 transition">
                            Limpiar
                        </a>
                    @endif
                </div>
            </form>

            {{-- Tabla --}}
            <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-gray-600 dark:text-zinc-300">
                        <thead class="text-xs text-gray-700 dark:text-zinc-200 uppercase bg-gray-50 dark:bg-zinc-950">
                            <tr>
                                <th class="px-4 py-3">Comprobante</th>
                                <th class="px-4 py-3">Fecha</th>
                                <th class="px-4 py-3">Cliente</th>
                                @unless ($esCliente)
                                    <th class="px-4 py-3">Empleado</th>
                                @endunless
                                <th class="px-4 py-3 text-center">Artículos</th>
                                <th class="px-4 py-3">Medio de pago</th>
                                <th class="px-4 py-3 text-right">Total</th>
                                <th class="px-4 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ventas as $venta)
                                <tr class="border-b border-gray-100 dark:border-zinc-800 hover:bg-gray-50 dark:hover:bg-zinc-800">
                                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-zinc-100 font-mono tabular-nums">
                                        {{-- Numero fiscal del comprobante, no
                                             el id interno de la venta. --}}
                                        {{ $venta->numeroComprobante() ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ $venta->created_at?->format('d/m/Y H:i') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ $venta->customer?->first_name }} {{ $venta->customer?->last_name }}
                                        {{-- El email del cliente solo lo ve quien
                                             gestiona las fichas (Jefe y
                                             Administrador). Para el resto del
                                             personal alcanza con el teléfono. --}}
                                        @if ($veEmails)
                                            <span class="block text-xs text-gray-400 dark:text-zinc-500">{{ $venta->customer?->user?->email }}</span>
                                        @elseif ($venta->customer?->phone)
                                            <span class="block text-xs text-gray-400 dark:text-zinc-500">{{ $venta->customer?->phone }}</span>
                                        @endif
                                    </td>
                                    @unless ($esCliente)
                                        <td class="px-4 py-3">
                                            {{ $venta->employee?->first_name }} {{ $venta->employee?->last_name }}
                                        </td>
                                    @endunless
                                    <td class="px-4 py-3 text-center font-mono tabular-nums">{{ $venta->unidades() }}</td>
                                    <td class="px-4 py-3">{{ $venta->payment_method }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-zinc-100 font-mono tabular-nums">
                                        ${{ number_format($venta->total(), 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right space-x-4 font-mono tabular-nums">
                                        <a href="{{ route('sales.show', $venta->id_sale) }}"
                                           class="text-hazard-600 hover:text-hazard-800 font-medium">Ver</a>
                                        <a href="{{ route('sales.pdf', $venta->id_sale) }}"
                                           class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 font-medium">PDF</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    {{-- El Cliente no ve la columna Empleado,
                                         asi que la fila vacia se estira
                                         sobre una columna menos. --}}
                                    <td colspan="{{ $esCliente ? 7 : 8 }}" class="px-4 py-8 text-center text-gray-500 dark:text-zinc-400">
                                        No hay ventas que coincidan con el filtro.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($ventas->hasPages())
                    <div class="px-4 py-3 border-t border-gray-200 dark:border-zinc-800">
                        {{ $ventas->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
