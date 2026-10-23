{{--
    Un renglon del cobro: medio, importe y, si es tarjeta de credito, cuotas.

    Lo usan el formulario y el <template> de las filas nuevas. El indice $i
    puede ser un numero o el marcador __i__, que el JavaScript reemplaza al
    insertar la fila.

    El importe es el TOTAL cobrado con ese medio. Las cuotas son la forma de
    pago: "6 cuotas de $X" se calcula al mostrar, no se guarda por cuota.
--}}
@php
    $i    = $i ?? 0;
    $pago = $pago ?? ['method' => $medios[0] ?? '', 'amount' => '', 'installments' => 1];

    $esCredito  = \App\Support\MediosPago::permiteCuotas((string) ($pago['method'] ?? ''));
    $cuotasElegidas = (int) ($pago['installments'] ?? 1);
@endphp

<div class="fila-pago rounded-md border border-gray-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-2" data-fila>
    <div class="flex flex-col sm:flex-row gap-2 sm:items-center">

        <select name="payments[{{ $i }}][method]"
                class="w-full sm:flex-1 border-gray-300 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 rounded-md shadow-sm text-sm">
            @foreach($medios as $medio)
                <option value="{{ $medio }}" @selected(($pago['method'] ?? '') === $medio)>{{ $medio }}</option>
            @endforeach
        </select>

        <div class="relative w-full sm:w-40">
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

        {{-- Cuotas: solo tiene sentido con tarjeta de credito. Se oculta y
             se deshabilita (asi no se envia) para los demas medios. --}}
        <select name="payments[{{ $i }}][installments]"
                title="Cuotas"
                @disabled(! $esCredito)
                class="select-cuotas w-full sm:w-36 border-gray-300 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 rounded-md shadow-sm text-sm {{ $esCredito ? '' : 'hidden' }}">
            @foreach(\App\Support\MediosPago::CUOTAS as $cuota)
                <option value="{{ $cuota }}" @selected($cuotasElegidas === $cuota)>
                    {{ $cuota === 1 ? '1 pago' : $cuota . ' cuotas' }}
                </option>
            @endforeach
        </select>

        <button type="button"
                class="quitar-pago shrink-0 px-3 py-2 text-sm rounded-md text-gray-500 dark:text-zinc-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-gray-100 dark:hover:bg-zinc-800 transition"
                title="Quitar este medio"
                aria-label="Quitar este medio">
            ✕
        </button>
    </div>

    {{-- Lo completa el JavaScript: "6 cuotas de $9.166,67" --}}
    <p class="texto-cuotas text-xs text-hazard-600 dark:text-hazard-400 mt-1 {{ $esCredito && $cuotasElegidas > 1 ? '' : 'hidden' }}"></p>
</div>
