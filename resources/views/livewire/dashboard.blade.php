<div class="space-y-6">

    {{--
        Filtro de periodo. wire:model.live hace que el cambio se envie al
        servidor en el momento y el componente se re-renderice con los datos
        del nuevo periodo.
    --}}
    <div class="bg-white dark:bg-zinc-900 p-4 rounded-xl border border-gray-200 dark:border-zinc-800 shadow-sm flex items-center justify-between gap-4">
        <div>
            <h2 class="text-sm font-bold text-zinc-800 dark:text-zinc-100 uppercase tracking-wider">Panel de control</h2>
            <p class="text-xs text-zinc-500 dark:text-zinc-300 mt-0.5">Datos reales de la base de datos</p>
        </div>
        <div class="flex items-center gap-2">
            <label for="periodo" class="text-xs font-semibold text-zinc-500 dark:text-zinc-300 uppercase">Periodo</label>
            <select id="periodo" wire:model.live="periodo"
                    class="rounded border-zinc-300 dark:border-zinc-700 text-sm shadow-sm focus:border-hazard-500 focus:ring-hazard-500">
                @foreach (\App\Livewire\Dashboard::PERIODOS as $valor => $texto)
                    <option value="{{ $valor }}">{{ $texto }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Tarjetas KPI / Métricas rápidas -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

        <!-- Ventas del Periodo -->
        <div class="bg-white dark:bg-zinc-900 p-5 rounded-xl border border-gray-200 dark:border-zinc-800 shadow-sm flex items-center gap-4">
            <div class="p-3 bg-hazard-50 text-hazard-600 rounded-lg">
                <svg class="w-6 h-6 fill-none stroke-current stroke-2" viewBox="0 0 24 24">
                    <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 12v-2m0 0c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 dark:text-zinc-100 uppercase">Ventas del Periodo</p>
                <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100">${{ number_format($totalVentas, 2, ',', '.') }}</h3>
            </div>
        </div>

        <!-- Artículos en Catálogo -->
        <div class="bg-white dark:bg-zinc-900 p-5 rounded-xl border border-gray-200 dark:border-zinc-800 shadow-sm flex items-center gap-4">
            <div class="p-3 bg-hazard-50 text-hazard-600 rounded-lg">
                <svg class="w-6 h-6 fill-none stroke-current stroke-2" viewBox="0 0 24 24">
                    <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 dark:text-zinc-100 uppercase">Artículos Activos</p>
                <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $articulosCount }} Productos</h3>
            </div>
        </div>

        <!-- Proveedores -->
        <div class="bg-white dark:bg-zinc-900 p-5 rounded-xl border border-gray-200 dark:border-zinc-800 shadow-sm flex items-center gap-4">
            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-lg">
                <svg class="w-6 h-6 fill-none stroke-current stroke-2" viewBox="0 0 24 24">
                    <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 dark:text-zinc-100 uppercase">Proveedores</p>
                <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $proveedoresCount }} Registrados</h3>
            </div>
        </div>

        <!-- Alerta Stock Bajo -->
        <div class="bg-white dark:bg-zinc-900 p-5 rounded-xl border border-gray-200 dark:border-zinc-800 shadow-sm flex items-center gap-4">
            <div class="p-3 bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-300 rounded-lg">
                <svg class="w-6 h-6 fill-none stroke-current stroke-2" viewBox="0 0 24 24">
                    <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 dark:text-zinc-100 uppercase">Alertas de Stock</p>
                <h3 class="text-lg font-bold text-red-600 dark:text-red-400">{{ $alertasStock }} Reposiciones</h3>
            </div>
        </div>
    </div>

    <!-- Sección de Gráficos con Chart.js -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{--
            Gráfico 1: Rendimiento de Ventas (Bar Chart)

            OJO con el wire:ignore. Va SOLO sobre el <canvas>, nunca sobre el
            div que lo envuelve: si el wire:ignore cubre el div, Livewire no
            actualiza los atributos data-* y el grafico se queda con los
            datos del primer render (el filtro de periodo cambiaba el KPI
            pero no el grafico).

            Los datos viven en un <span> aparte, FUERA del wire:ignore, que
            es lo que Livewire si actualiza.
        --}}
        <div class="lg:col-span-2 bg-white dark:bg-zinc-900 p-6 rounded-xl border border-gray-200 dark:border-zinc-800 shadow-sm">
            <h3 class="text-sm font-bold text-zinc-800 dark:text-zinc-100 mb-4 uppercase tracking-wider">Rendimiento de Ventas ($)</h3>
            <div class="relative h-72" wire:ignore>
                <canvas id="salesChart"></canvas>
            </div>
            <span id="datos-salesChart" hidden
                  data-labels='@json($ventasMes["labels"])'
                  data-values='@json($ventasMes["data"])'></span>
        </div>

        <!-- Gráfico 2: Distribución de Stock (Doughnut Chart) -->
        <div class="bg-white dark:bg-zinc-900 p-6 rounded-xl border border-gray-200 dark:border-zinc-800 shadow-sm">
            <h3 class="text-sm font-bold text-zinc-800 dark:text-zinc-100 mb-4 uppercase tracking-wider">Estado de Inventario</h3>
            <div class="relative h-72 flex items-center justify-center" wire:ignore>
                <canvas id="stockChart"></canvas>
            </div>
            <span id="datos-stockChart" hidden
                  data-labels='@json($stockPorCategoria["labels"])'
                  data-values='@json($stockPorCategoria["data"])'></span>
        </div>
    </div>

    <!-- Segunda fila de gráficos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Gráfico 3: Top productos vendidos (Bar Chart horizontal) -->
        <div class="bg-white dark:bg-zinc-900 p-6 rounded-xl border border-gray-200 dark:border-zinc-800 shadow-sm">
            <h3 class="text-sm font-bold text-zinc-800 dark:text-zinc-100 mb-4 uppercase tracking-wider">Productos Más Vendidos (unidades)</h3>
            <div class="relative h-72" wire:ignore>
                <canvas id="topProductsChart"></canvas>
            </div>
            <span id="datos-topProductsChart" hidden
                  data-labels='@json($topProductos["labels"])'
                  data-values='@json($topProductos["data"])'></span>
        </div>

        <!-- Gráfico 4: Medios de pago (Pie Chart) -->
        <div class="bg-white dark:bg-zinc-900 p-6 rounded-xl border border-gray-200 dark:border-zinc-800 shadow-sm">
            <h3 class="text-sm font-bold text-zinc-800 dark:text-zinc-100 mb-4 uppercase tracking-wider">Facturación por Medio de Pago</h3>
            <div class="relative h-72 flex items-center justify-center" wire:ignore>
                <canvas id="paymentChart"></canvas>
            </div>
            <span id="datos-paymentChart" hidden
                  data-labels='@json($mediosPago["labels"])'
                  data-values='@json($mediosPago["data"])'></span>
        </div>
    </div>

    <!-- Tabla: materias primas y su stock -->
    <div class="bg-white dark:bg-zinc-900 p-6 rounded-xl border border-gray-200 dark:border-zinc-800 shadow-sm">
        <h3 class="text-sm font-bold text-zinc-800 dark:text-zinc-100 mb-4 uppercase tracking-wider">Materias Primas</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase text-zinc-500 dark:text-zinc-100 border-b border-gray-200 dark:border-zinc-800">
                        <th class="py-2 pr-4">Materia prima</th>
                        <th class="py-2 pr-4 text-right">Stock actual</th>
                        <th class="py-2 pr-4 text-right">Stock mínimo</th>
                        <th class="py-2 text-right">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($materiaPrimaStock['labels'] as $i => $materia)
                        @php $actual = $materiaPrimaStock['actual'][$i]; $minimo = $materiaPrimaStock['minimo'][$i]; @endphp
                        <tr class="border-b border-gray-100 dark:border-zinc-800">
                            <td class="py-2 pr-4 text-zinc-700 dark:text-zinc-200">{{ $materia }}</td>
                            <td class="py-2 pr-4 text-right font-semibold text-zinc-900 dark:text-zinc-100">{{ $actual }}</td>
                            <td class="py-2 pr-4 text-right text-zinc-500 dark:text-zinc-300">{{ $minimo }}</td>
                            <td class="py-2 text-right">
                                @if ($actual <= $minimo)
                                    <span class="inline-flex items-center rounded-full bg-red-100 dark:bg-red-950/40 px-2 py-0.5 text-xs font-semibold text-red-700 dark:text-red-300">Reponer</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-green-100 dark:bg-green-950/40 px-2 py-0.5 text-xs font-semibold text-green-700 dark:text-green-300">OK</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-4 text-center text-zinc-500 dark:text-zinc-300">
                                No hay materias primas con stock registrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{--
        Inicialización de Chart.js.

        Los <canvas> están en wire:ignore, así que Livewire no los reemplaza al
        re-renderizar. Eso obliga a destruir el chart anterior antes de crear
        uno nuevo (si no, se apilan dos gráficos sobre el mismo canvas) y a
        re-inicializar los charts cuando el componente se actualiza, por
        ejemplo al cambiar el filtro de periodo.
    --}}
    <script>
        (function () {
            const instancias = {};

            function crear(id, config) {
                const canvas = document.getElementById(id);
                if (!canvas) return;

                // Si ya hay un chart en ese canvas, se destruye primero
                if (instancias[id]) {
                    instancias[id].destroy();
                    delete instancias[id];
                }

                instancias[id] = new Chart(canvas.getContext('2d'), config);
            }

            // Paleta de los graficos, segun el modo claro u oscuro.
            // Los graficos no se estilan con Tailwind (se dibujan en
            // un <canvas>), asi que los colores se eligen en JS.
            function paleta() {
                const oscuro = document.documentElement.classList.contains('dark');

                return {
                    oscuro: oscuro,
                    texto: oscuro ? '#a1a1aa' : '#52525b',   // zinc-400 / zinc-600
                    grid:  oscuro ? '#3f3f46' : '#e4e4e7',   // zinc-700 / zinc-200
                    acento: '#ea580c',                        // hazard-600
                    series: oscuro
                        ? ['#ea580c', '#10b981', '#71717a', '#ef4444', '#a1a1aa']
                        : ['#ea580c', '#10b981', '#a1a1aa', '#ef4444', '#71717a']
                };
            }

            function initCharts() {
                if (typeof Chart === 'undefined') return;

                const p = paleta();

                /*
                 * Los datos se leen del <span id="datos-XXX">, no del canvas.
                 *
                 * El canvas esta dentro de un wire:ignore (si no, Livewire
                 * lo reemplaza y quedan dos graficos superpuestos), asi que
                 * sus atributos no se actualizan al cambiar el periodo. El
                 * span esta FUERA del wire:ignore, ahi si se actualiza.
                 */
                const datos = (id) => {
                    const el = document.getElementById('datos-' + id);
                    return {
                        labels: JSON.parse(el?.dataset.labels || '[]'),
                        values: JSON.parse(el?.dataset.values || '[]'),
                    };
                };

                // 1. Gráfico de Ventas
                const cVentas = document.getElementById('salesChart');
                if (cVentas) {
                    const d = datos('salesChart');
                    crear('salesChart', {
                        type: 'bar',
                        data: {
                            labels: d.labels,
                            datasets: [{
                                label: 'Ventas Totales ($)',
                                data: d.values,
                                backgroundColor: p.acento,
                                borderRadius: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { beginAtZero: true, grid: { color: p.grid }, ticks: { color: p.texto } },
                                x: { grid: { display: false }, ticks: { color: p.texto } }
                            }
                        }
                    });
                }

                // 2. Gráfico de Stock
                const cStock = document.getElementById('stockChart');
                if (cStock) {
                    const d = datos('stockChart');
                    crear('stockChart', {
                        type: 'doughnut',
                        data: {
                            labels: d.labels,
                            datasets: [{
                                data: d.values,
                                backgroundColor: p.series,
                                borderColor: p.oscuro ? '#18181b' : '#ffffff',
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { color: p.texto, boxWidth: 12, usePointStyle: true }
                                }
                            }
                        }
                    });
                }

                // 3. Gráfico de Top productos
                const cTop = document.getElementById('topProductsChart');
                if (cTop) {
                    const d = datos('topProductsChart');
                    crear('topProductsChart', {
                        type: 'bar',
                        data: {
                            labels: d.labels,
                            datasets: [{
                                label: 'Unidades vendidas',
                                data: d.values,
                                backgroundColor: p.acento,
                                borderRadius: 4
                            }]
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { beginAtZero: true, grid: { color: p.grid }, ticks: { color: p.texto } },
                                y: { grid: { display: false }, ticks: { color: p.texto } }
                            }
                        }
                    });
                }

                // 4. Gráfico de Medios de pago
                const cPago = document.getElementById('paymentChart');
                if (cPago) {
                    const d = datos('paymentChart');
                    crear('paymentChart', {
                        type: 'pie',
                        data: {
                            labels: d.labels,
                            datasets: [{
                                data: d.values,
                                backgroundColor: p.series,
                                borderColor: p.oscuro ? '#18181b' : '#ffffff',
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { color: p.texto, boxWidth: 12, usePointStyle: true }
                                }
                            }
                        }
                    });
                }
            }

            // Al cargar la página
            document.addEventListener('DOMContentLoaded', initCharts);

            // Cuando Livewire actualiza el componente (por ejemplo al cambiar
            // el periodo), los datos del canvas cambian y hay que redibujar.
            document.addEventListener('livewire:init', () => {
                initCharts();

                Livewire.hook('morph', () => {
                    // Se espera a que Livewire termine de morphar el DOM
                    setTimeout(initCharts, 50);
                });
            });

            // Al cambiar de modo, los graficos hay que redibujarlos: el
            // canvas no se actualiza solo con el cambio de clase en <html>.
            document.addEventListener('tema:cambiado', () => {
                setTimeout(initCharts, 50);
            });
        })();
    </script>
</div>
