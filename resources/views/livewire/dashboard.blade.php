<div class="space-y-6">
    
    <!-- Tarjetas KPI / Métricas rápidas -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        
        <!-- Venta Estimada -->
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4">
            <div class="p-3 bg-amber-50 text-amber-600 rounded-lg">
                <svg class="w-6 h-6 fill-none stroke-current stroke-2" viewBox="0 0 24 24">
                    <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 12v-2m0 0c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase">Ventas del Mes</p>
                <h3 class="text-lg font-bold text-slate-900">${{ number_format($totalVentas, 2, ',', '.') }}</h3>
            </div>
        </div>

        <!-- Artículos en Catálogo -->
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4">
            <div class="p-3 bg-blue-50 text-blue-600 rounded-lg">
                <svg class="w-6 h-6 fill-none stroke-current stroke-2" viewBox="0 0 24 24">
                    <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase">Artículos Activos</p>
                <h3 class="text-lg font-bold text-slate-900">{{ $articulosCount }} Productos</h3>
            </div>
        </div>

        <!-- Proveedores -->
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4">
            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-lg">
                <svg class="w-6 h-6 fill-none stroke-current stroke-2" viewBox="0 0 24 24">
                    <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase">Proveedores</p>
                <h3 class="text-lg font-bold text-slate-900">{{ $proveedoresCount }} Registrados</h3>
            </div>
        </div>

        <!-- Alerta Stock Bajo -->
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4">
            <div class="p-3 bg-red-50 text-red-600 rounded-lg">
                <svg class="w-6 h-6 fill-none stroke-current stroke-2" viewBox="0 0 24 24">
                    <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase">Alertas de Stock</p>
                <h3 class="text-lg font-bold text-red-600">{{ $alertasStock }} Reposiciones</h3>
            </div>
        </div>
    </div>

    <!-- Sección de Gráficos con Chart.js -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Gráfico 1: Rendimiento de Ventas (Bar Chart) -->
        <div class="lg:col-span-2 bg-white p-6 rounded-xl border border-gray-200 shadow-sm" wire:ignore>
            <h3 class="text-sm font-bold text-slate-800 mb-4 uppercase tracking-wider">Rendimiento de Ventas ($)</h3>
            <div class="relative h-72">
                <canvas id="salesChart" 
                        data-labels='@json($ventasMes["labels"])' 
                        data-values='@json($ventasMes["data"])'></canvas>
            </div>
        </div>

        <!-- Gráfico 2: Distribución de Stock (Doughnut Chart) -->
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm" wire:ignore>
            <h3 class="text-sm font-bold text-slate-800 mb-4 uppercase tracking-wider">Estado de Inventario</h3>
            <div class="relative h-72 flex items-center justify-center">
                <canvas id="stockChart" 
                        data-labels='@json($stockPorCategoria["labels"])' 
                        data-values='@json($stockPorCategoria["data"])'></canvas>
            </div>
        </div>
    </div>

    <!-- Script de Inicialización de Chart.js -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // 1. Gráfico de Ventas
            const canvasSales = document.getElementById('salesChart');
            if (canvasSales) {
                const labelsSales = JSON.parse(canvasSales.dataset.labels || '[]');
                const valuesSales = JSON.parse(canvasSales.dataset.values || '[]');

                new Chart(canvasSales.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: labelsSales,
                        datasets: [{
                            label: 'Ventas Totales ($)',
                            data: valuesSales,
                            backgroundColor: '#d97706',
                            borderRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, grid: { color: '#f3f4f6' } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }

            // 2. Gráfico de Stock
            const canvasStock = document.getElementById('stockChart');
            if (canvasStock) {
                const labelsStock = JSON.parse(canvasStock.dataset.labels || '[]');
                const valuesStock = JSON.parse(canvasStock.dataset.values || '[]');

                new Chart(canvasStock.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: labelsStock,
                        datasets: [{
                            data: valuesStock,
                            backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } }
                    }
                });
            }
        });
    </script>
</div>