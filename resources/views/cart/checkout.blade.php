<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-zinc-100 leading-tight">
            {{ $esCliente ? 'Confirmar Compra' : 'Confirmar Venta' }}
        </h2>
    </x-slot>

    @php
        /*
         * Las filas de pago se arman aca para que, si la validacion falla,
         * el usuario no pierda lo que habia cargado. Si no hay datos
         * viejos, arranca con una sola fila por el total (pago simple).
         */
        $medios = \App\Support\MediosPago::todos();

        $pagosViejos = old('payments');

        if (! is_array($pagosViejos) || count($pagosViejos) === 0) {
            $pagosViejos = [[
                'method' => $medios[0],
                'amount' => number_format((float) $subtotal, 2, '.', ''),
            ]];
        }
    @endphp

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow-sm sm:rounded-lg p-6 border-b border-gray-200 dark:border-zinc-800">

                <h3 class="text-lg font-medium text-gray-900 dark:text-zinc-100 mb-6">{{ $esCliente ? 'Resumen de la compra' : 'Resumen de la venta' }}</h3>

                {{-- Avisos de las acciones sobre los items (cambiar / quitar) --}}
                @if(session('success'))
                    <div class="mb-4 px-4 py-3 bg-green-100 dark:bg-green-950/40 text-green-800 dark:text-green-300 text-sm rounded-md">
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-4 px-4 py-3 bg-red-100 dark:bg-red-950/40 text-red-800 dark:text-red-300 text-sm rounded-md">
                        {{ session('error') }}
                    </div>
                @endif

                {{--
                    Detalle de items EDITABLE.

                    El pedido se puede corregir aca mismo, sin volver al
                    carrito: se cambia el producto en el desplegable, se
                    cambia la cantidad (se guarda al salir del campo) o se
                    quita el renglon. Cada accion recarga esta misma pantalla
                    con el total nuevo.
                --}}
                <div class="border border-gray-200 dark:border-zinc-800 rounded-md overflow-hidden mb-3">
                    <div class="px-4 py-2 bg-gray-50 dark:bg-zinc-950 text-xs uppercase text-gray-700 dark:text-zinc-200 flex items-center justify-between">
                        <span>Producto</span>
                        <span>Importe</span>
                    </div>

                    @foreach($cart->items as $item)
                        <div class="border-t border-gray-100 dark:border-zinc-800 px-4 py-3">
                            <div class="flex flex-col lg:flex-row lg:items-center gap-3">

                                {{-- Cambiar producto y cantidad --}}
                                <form action="{{ route('cart.updateItem', [$cart->id_cart, $item->id_cart_item]) }}"
                                      method="POST"
                                      class="flex flex-col sm:flex-row gap-2 flex-1">
                                    @csrf
                                    @method('PATCH')

                                    <select name="id_article"
                                            onchange="this.form.submit()"
                                            title="Cambiar el producto"
                                            class="w-full sm:flex-1 border-gray-300 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 rounded-md shadow-sm text-sm">
                                        @foreach($articles as $article)
                                            <option value="{{ $article->id_article }}"
                                                    @selected($article->id_article === $item->id_article)
                                                    @disabled(($article->stock->remaining_quantity ?? 0) <= 0 && $article->id_article !== $item->id_article)>
                                                {{ $article->article_name }}
                                                — ${{ number_format($article->unit_price, 2) }}
                                                (Stock: {{ $article->stock->remaining_quantity ?? 0 }})
                                            </option>
                                        @endforeach
                                    </select>

                                    <div class="flex items-center gap-1">
                                        <input type="number"
                                               name="quantity"
                                               value="{{ $item->quantity }}"
                                               min="1"
                                               title="Cantidad"
                                               onchange="this.form.submit()"
                                               class="w-20 border-gray-300 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 rounded-md shadow-sm text-sm font-mono tabular-nums">
                                        <button type="submit"
                                                title="Actualizar cantidad"
                                                class="px-3 py-2 text-sm rounded-md text-gray-600 dark:text-zinc-300 bg-gray-100 dark:bg-zinc-800 hover:bg-gray-200 dark:hover:bg-zinc-700 transition">
                                            ↻
                                        </button>
                                    </div>
                                </form>

                                {{-- Importe del renglon --}}
                                <div class="lg:w-32 lg:text-right font-semibold text-gray-900 dark:text-zinc-100 font-mono tabular-nums">
                                    ${{ number_format($item->unit_price * $item->quantity, 2) }}
                                </div>

                                {{-- Quitar el producto --}}
                                <form action="{{ route('cart.removeItem', [$cart->id_cart, $item->id_cart_item]) }}"
                                      method="POST"
                                      class="lg:w-20 lg:text-right">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="quantity" value="{{ $item->quantity }}">
                                    <button type="submit"
                                            class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 font-medium text-xs bg-red-50 dark:bg-red-950/40 px-3 py-1 rounded-md"
                                            onclick="return confirm('¿Quitar «{{ $item->article->article_name }}» del pedido?')">
                                        Quitar
                                    </button>
                                </form>
                            </div>

                            <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">
                                ${{ number_format($item->unit_price, 2) }} c/u
                                · Stock: {{ $item->article->stock->remaining_quantity ?? 0 }}
                            </p>
                        </div>
                    @endforeach
                </div>

                {{-- Agregar otro producto sin salir del checkout --}}
                <p class="text-xs text-gray-500 dark:text-zinc-400 mb-2">
                    Cambiá el producto en el desplegable o la cantidad y se guarda al instante.
                    Para sacar un producto del pedido, «Quitar».
                </p>
                <form action="{{ route('cart.addItem', $cart->id_cart) }}" method="POST" class="mb-6">
                    @csrf
                    <div class="flex flex-col sm:flex-row gap-2">
                        <select name="id_article" required
                                class="w-full sm:flex-1 border-gray-300 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 rounded-md shadow-sm text-sm">
                            <option value="">Agregar otro producto...</option>
                            @foreach($articles as $article)
                                <option value="{{ $article->id_article }}"
                                        @disabled(($article->stock->remaining_quantity ?? 0) <= 0)>
                                    {{ $article->article_name }}
                                    — ${{ number_format($article->unit_price, 2) }}
                                    (Stock: {{ $article->stock->remaining_quantity ?? 0 }})
                                </option>
                            @endforeach
                        </select>
                        <input type="number" name="quantity" value="1" min="1"
                               class="w-20 border-gray-300 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 rounded-md shadow-sm text-sm font-mono tabular-nums">
                        <button type="submit"
                                class="bg-gray-200 dark:bg-zinc-800 hover:bg-gray-300 dark:hover:bg-zinc-700 text-gray-800 dark:text-zinc-100 text-sm font-medium px-4 py-2 rounded-md transition">
                            + Agregar
                        </button>
                    </div>
                </form>

                {{-- Total --}}
                <div class="flex justify-between text-lg font-bold text-gray-900 dark:text-zinc-100 mb-2 px-1">
                    <span>Total</span>
                    <span>${{ number_format($subtotal, 2) }}</span>
                </div>

                {{-- Los precios de la calle ya traen el IVA: el comprobante
                     lo desglosa, pero el cliente paga el total. --}}
                <p class="text-xs text-gray-500 dark:text-zinc-400 mb-6 px-1">
                    Los precios incluyen IVA (21%). El comprobante muestra el neto y el IVA desglosados.
                </p>

                {{-- Errores de validación --}}
                @if ($errors->any())
                    <div class="mb-4 rounded-md bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900 px-4 py-3">
                        <ul class="text-sm text-red-700 dark:text-red-300 list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Formulario de pago --}}
                <form action="{{ route('cart.confirm', $cart->id_cart) }}" method="POST">
                    @csrf

                    <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-zinc-200">
                            Forma de pago <span class="text-red-500">*</span>
                        </label>
                        <button type="button" id="agregar-pago"
                                class="text-xs font-semibold uppercase tracking-wider text-hazard-600 dark:text-hazard-400 hover:text-hazard-700 dark:hover:text-hazard-300 transition">
                            + Agregar otro medio
                        </button>
                    </div>

                    <p class="text-xs text-gray-500 dark:text-zinc-400 mb-4">
                        Podés combinar medios: por ejemplo, una parte en efectivo y el resto con transferencia.
                        Los importes tienen que sumar el total.
                    </p>

                    {{-- Filas de pago --}}
                    <div id="pagos" class="space-y-2 mb-4">
                        @foreach($pagosViejos as $i => $pago)
                            <div class="fila-pago flex flex-col sm:flex-row gap-2 sm:items-center" data-fila>
                                <select name="payments[{{ $i }}][method]"
                                        class="w-full sm:w-1/2 border-gray-300 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 rounded-md shadow-sm text-sm">
                                    @foreach($medios as $medio)
                                        <option value="{{ $medio }}" @selected(($pago['method'] ?? '') === $medio)>{{ $medio }}</option>
                                    @endforeach
                                </select>

                                <div class="relative w-full sm:w-1/3">
                                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-zinc-500 text-sm">$</span>
                                    <input type="number"
                                           name="payments[{{ $i }}][amount]"
                                           value="{{ $pago['amount'] ?? '' }}"
                                           step="0.01"
                                           min="0"
                                           inputmode="decimal"
                                           placeholder="0.00"
                                           class="w-full pl-7 border-gray-300 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 rounded-md shadow-sm text-sm font-mono tabular-nums">
                                </div>

                                <button type="button"
                                        class="quitar-pago shrink-0 px-3 py-2 text-sm rounded-md text-gray-500 dark:text-zinc-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-gray-100 dark:hover:bg-zinc-800 transition"
                                        title="Quitar este medio"
                                        aria-label="Quitar este medio">
                                    ✕
                                </button>
                            </div>
                        @endforeach
                    </div>

                    {{-- Molde para las filas que se agregan con JavaScript.
                         El índice se reemplaza al insertar. --}}
                    <template id="plantilla-pago">
                        <div class="fila-pago flex flex-col sm:flex-row gap-2 sm:items-center" data-fila>
                            <select name="payments[__i__][method]"
                                    class="w-full sm:w-1/2 border-gray-300 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 rounded-md shadow-sm text-sm">
                                @foreach($medios as $medio)
                                    <option value="{{ $medio }}">{{ $medio }}</option>
                                @endforeach
                            </select>

                            <div class="relative w-full sm:w-1/3">
                                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-zinc-500 text-sm">$</span>
                                <input type="number"
                                       name="payments[__i__][amount]"
                                       value=""
                                       step="0.01"
                                       min="0"
                                       inputmode="decimal"
                                       placeholder="0.00"
                                       class="w-full pl-7 border-gray-300 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 rounded-md shadow-sm text-sm font-mono tabular-nums">
                            </div>

                            <button type="button"
                                    class="quitar-pago shrink-0 px-3 py-2 text-sm rounded-md text-gray-500 dark:text-zinc-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-gray-100 dark:hover:bg-zinc-800 transition"
                                    title="Quitar este medio"
                                    aria-label="Quitar este medio">
                                ✕
                            </button>
                        </div>
                    </template>

                    {{-- Resumen en vivo del cobro --}}
                    <div class="bg-gray-50 dark:bg-zinc-950 rounded-md p-4 mb-6 space-y-1 text-sm">
                        <div class="flex justify-between text-gray-600 dark:text-zinc-300">
                            <span>Total a cobrar</span>
                            <span class="font-mono tabular-nums">${{ number_format($subtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600 dark:text-zinc-300">
                            <span>Pagado</span>
                            <span class="font-mono tabular-nums">$<span id="pagado">0.00</span></span>
                        </div>
                        <div class="flex justify-between font-semibold">
                            <span id="estado-pago" class="text-red-600 dark:text-red-400">Falta</span>
                            <span id="diferencia" class="font-mono tabular-nums text-red-600 dark:text-red-400">$0.00</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <button type="submit"
                                class="bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-6 py-2 rounded-md transition">
                            {{ $esCliente ? 'Finalizar compra y emitir comprobante' : 'Finalizar venta y emitir comprobante' }}
                        </button>
                        <a href="{{ route('cart.show', $cart->id_cart) }}"
                           class="text-sm text-gray-600 dark:text-zinc-300 bg-gray-100 dark:bg-zinc-950 hover:bg-gray-200 dark:hover:bg-zinc-700 px-4 py-2 rounded-md transition">
                            Volver al carrito
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script>
        (function () {
            const TOTAL = {{ (float) $subtotal }};
            const contenedor = document.getElementById('pagos');
            const plantilla  = document.getElementById('plantilla-pago');
            const botonAgregar = document.getElementById('agregar-pago');

            let siguienteIndice = {{ count($pagosViejos) }};

            const pesos = (n) => n.toFixed(2);

            /** Suma lo que hay cargado en las filas. */
            function pagadoActual() {
                let pagado = 0;

                contenedor.querySelectorAll('input[name$="[amount]"]').forEach((el) => {
                    pagado += parseFloat(el.value) || 0;
                });

                return Math.round(pagado * 100) / 100;
            }

            /**
             * Renumera los name="payments[i][...]" y esconde la cruz de la
             * primera fila: siempre tiene que quedar al menos un medio.
             */
            function reindexar() {
                const filas = contenedor.querySelectorAll('[data-fila]');

                filas.forEach((fila, n) => {
                    fila.querySelectorAll('[name]').forEach((el) => {
                        el.name = el.name.replace(/payments\[\d+\]/, 'payments[' + n + ']');
                    });

                    const quitar = fila.querySelector('.quitar-pago');

                    if (quitar) {
                        quitar.style.visibility = filas.length === 1 ? 'hidden' : 'visible';
                    }
                });
            }

            /** Muestra Pagado / Falta / Sobra. */
            function recalcular() {
                const pagado = pagadoActual();
                const diferencia = Math.round((TOTAL - pagado) * 100) / 100;

                document.getElementById('pagado').textContent = pesos(pagado);

                const estado = document.getElementById('estado-pago');
                const monto  = document.getElementById('diferencia');

                if (Math.abs(diferencia) < 0.005) {
                    estado.textContent = 'Pago completo';
                    estado.className = 'text-green-600 dark:text-green-400';
                    monto.textContent = '$0.00';
                    monto.className = 'font-mono tabular-nums text-green-600 dark:text-green-400';
                } else if (diferencia > 0) {
                    estado.textContent = 'Falta';
                    estado.className = 'text-red-600 dark:text-red-400';
                    monto.textContent = '$' + pesos(diferencia);
                    monto.className = 'font-mono tabular-nums text-red-600 dark:text-red-400';
                } else {
                    estado.textContent = 'Sobra';
                    estado.className = 'text-amber-600 dark:text-amber-400';
                    monto.textContent = '$' + pesos(Math.abs(diferencia));
                    monto.className = 'font-mono tabular-nums text-amber-600 dark:text-amber-400';
                }
            }

            botonAgregar.addEventListener('click', () => {
                const html = plantilla.innerHTML.replaceAll('__i__', String(siguienteIndice++));

                contenedor.insertAdjacentHTML('beforeend', html);
                reindexar();

                /*
                 * La fila nueva arranca con lo que falta: es el caso tipico
                 * (pago 10.000 en efectivo y el resto con otro medio). Si ya
                 * esta pago, arranca vacia.
                 */
                const filas = contenedor.querySelectorAll('[data-fila]');
                const ultima = filas[filas.length - 1];
                const falta = Math.max(0, Math.round((TOTAL - pagadoActual()) * 100) / 100);
                const input = ultima.querySelector('input[name$="[amount]"]');

                if (input && falta > 0) {
                    input.value = pesos(falta);
                }

                recalcular();
            });

            contenedor.addEventListener('click', (evento) => {
                const boton = evento.target.closest('.quitar-pago');

                if (!boton) {
                    return;
                }

                // La primera fila no se puede quitar (el boton esta oculto,
                // pero por las dudas).
                if (contenedor.querySelectorAll('[data-fila]').length <= 1) {
                    return;
                }

                boton.closest('[data-fila]').remove();
                reindexar();
                recalcular();
            });

            contenedor.addEventListener('input', recalcular);

            reindexar();
            recalcular();
        })();
    </script>
</x-app-layout>
