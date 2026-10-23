<?php

namespace App\Livewire;

use App\Models\Article;
use App\Models\ProductStock;
use App\Models\Supplier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Dashboard con datos REALES de la base.
 *
 * Antes todos los datos de los charts estaban hardcodeados en el componente
 * (`$totalVentas = 348000` y arrays fijos), asi que el dashboard mostraba
 * siempre lo mismo, le costara lo que le costara a la base. Ademas el
 * filtro `periodo` existia pero no habia ningun control en la vista para
 * cambiarlo, y los charts de Chart.js no se re-inicializaban cuando
 * Livewire re-renderizaba el componente.
 */
class Dashboard extends Component
{
    /**
     * Periodo del reporte. Lo cambia el <select> de la vista.
     */
    public $periodo = '30_dias';

    /**
     * Periodos disponibles.
     */
    public const PERIODOS = [
        '30_dias' => 'Últimos 30 días',
        '90_dias' => 'Últimos 90 días',
        '6_meses' => 'Últimos 6 meses',
        'anio'    => 'Último año',
        'todo'    => 'Todo el histórico',
    ];

    /**
     * Traduce el periodo a una fecha de corte.
     */
    private function fechaDesde(): ?Carbon
    {
        return match ($this->periodo) {
            '30_dias' => now()->subDays(30),
            '90_dias' => now()->subDays(90),
            '6_meses' => now()->subMonths(6),
            'anio'    => now()->subYear(),
            default   => null,   // 'todo'
        };
    }

    public function render()
    {
        /*
         * El panel de negocio es del PERSONAL.
         *
         * El Cliente tiene su propio panel (DashboardController lo sirve en
         * dashboard.cliente), pero este componente se puede pedir
         * directamente por el endpoint de Livewire. Sin este chequeo, un
         * Cliente podria pedir los KPIs de la empresa: la venta total, el
         * stock, los proveedores y los medios de pago.
         */
        abort_unless(
            auth()->check() && ! auth()->user()->hasRole('Cliente'),
            403,
            'El panel de la empresa es del personal.'
        );

        $desde = $this->fechaDesde();

        // ------------------------------------------------------------------
        // KPIs
        // ------------------------------------------------------------------
        $totalVentas = $this->totalVentas($desde);
        $articulosCount = Article::count();
        $proveedoresCount = Supplier::count();
        $alertasStock = ProductStock::whereRaw('remaining_quantity <= minimum_stock')->count();

        // ------------------------------------------------------------------
        // Chart 1: ventas por mes
        // ------------------------------------------------------------------
        $ventasMes = $this->ventasPorMes($desde);

        // ------------------------------------------------------------------
        // Chart 2: estado del inventario (stock por articulo)
        // ------------------------------------------------------------------
        $stockPorCategoria = $this->stockPorArticulo();

        // ------------------------------------------------------------------
        // Chart 3: top productos vendidos
        // ------------------------------------------------------------------
        $topProductos = $this->topProductosVendidos($desde);

        // ------------------------------------------------------------------
        // Chart 4: medios de pago
        // ------------------------------------------------------------------
        $mediosPago = $this->mediosDePago($desde);

        // ------------------------------------------------------------------
        // Tabla: materias primas con su stock
        // ------------------------------------------------------------------
        $materiaPrimaStock = $this->materiasPrimas();

        return view('livewire.dashboard', compact(
            'totalVentas',
            'articulosCount',
            'proveedoresCount',
            'alertasStock',
            'ventasMes',
            'stockPorCategoria',
            'topProductos',
            'mediosPago',
            'materiaPrimaStock'
        ));
    }

    // ======================================================================
    //  Consultas
    // ======================================================================

    /**
     * Monto total vendido en el periodo.
     *
     * La tabla `sales` no tiene columna de monto: el total de una venta sale
     * de sus items (quantity * unit_price). Por eso se une con cart_items.
     */
    private function totalVentas(?Carbon $desde): float
    {
        $q = DB::table('sales')
            ->join('cart_items', 'cart_items.id_cart', '=', 'sales.id_cart');

        if ($desde !== null) {
            $q->where('sales.created_at', '>=', $desde);
        }

        return (float) $q->sum(DB::raw('cart_items.quantity * cart_items.unit_price'));
    }

    /**
     * Ventas agrupadas por mes.
     *
     * Se hace en PHP y no con DATE_FORMAT para que la consulta funcione
     * igual en MySQL y en SQLite (los tests corren en SQLite en memoria).
     */
    private function ventasPorMes(?Carbon $desde): array
    {
        $q = DB::table('sales')
            ->join('cart_items', 'cart_items.id_cart', '=', 'sales.id_cart')
            // groupBy por la fecha exacta: una fila por venta, con el total de
            // sus items. El agrupado por MES se hace despues en PHP.
            //
            // El groupBy es obligatorio: MySQL con sql_mode=only_full_group_by
            // (el default desde 5.7) rechaza un SELECT con una columna no
            // agregada y sin GROUP BY.
            ->groupBy('sales.created_at')
            ->select('sales.created_at')
            ->selectRaw('SUM(cart_items.quantity * cart_items.unit_price) as total');

        if ($desde !== null) {
            $q->where('sales.created_at', '>=', $desde);
        }

        $filas = $q->get();

        // Se agrupa por mes
        $porMes = $filas
            ->filter(fn ($f) => $f->created_at !== null)
            ->groupBy(fn ($f) => Carbon::parse($f->created_at)->format('Y-m'))
            ->map(fn ($grupo) => round((float) $grupo->sum('total'), 2));

        // Se arma la linea de tiempo completa del periodo para que el chart
        // muestre los meses sin ventas con 0 en vez de saltarselos.
        $etiquetas = [];
        $valores = [];

        if ($desde !== null) {
            $meses = $desde->copy()->startOfMonth();
            $fin = now()->startOfMonth();

            while ($meses->lessThanOrEqualTo($fin)) {
                $etiquetas[] = $meses->translatedFormat('M Y');
                $valores[] = (float) ($porMes[$meses->format('Y-m')] ?? 0);
                $meses->addMonth();
            }
        } else {
            foreach ($porMes->sortKeys() as $mes => $total) {
                $etiquetas[] = Carbon::createFromFormat('Y-m', $mes)->translatedFormat('M Y');
                $valores[] = (float) $total;
            }
        }

        return ['labels' => $etiquetas, 'data' => $valores];
    }

    /**
     * Stock de cada articulo (para el chart de inventario).
     *
     * No hay columna de categoria en el esquema, asi que en vez de inventar
     * categorias se muestra el stock real de los articulos con mas
     * unidades en deposito.
     */
    private function stockPorArticulo(): array
    {
        $filas = ProductStock::with('article')
            ->orderByDesc('remaining_quantity')
            ->limit(5)
            ->get();

        return [
            'labels' => $filas->map(fn ($f) => $f->article?->article_name ?? 'Sin artículo')->all(),
            'data'   => $filas->map(fn ($f) => (int) $f->remaining_quantity)->all(),
        ];
    }

    /**
     * Articulos mas vendidos (por unidades), con su facturacion.
     */
    private function topProductosVendidos(?Carbon $desde): array
    {
        $q = DB::table('cart_items')
            ->join('articles', 'articles.id_article', '=', 'cart_items.id_article')
            ->join('sales', 'sales.id_cart', '=', 'cart_items.id_cart')
            ->groupBy('cart_items.id_article', 'articles.article_name')
            ->select('articles.article_name')
            ->selectRaw('SUM(cart_items.quantity) as unidades')
            ->selectRaw('SUM(cart_items.quantity * cart_items.unit_price) as facturado')
            ->orderByDesc('unidades')
            ->limit(5);

        if ($desde !== null) {
            $q->where('sales.created_at', '>=', $desde);
        }

        $filas = $q->get();

        return [
            'labels' => $filas->map(fn ($f) => $f->article_name)->all(),
            'data'   => $filas->map(fn ($f) => (int) $f->unidades)->all(),
        ];
    }

    /**
     * Distribucion por medio de pago.
     *
     * Se suma el DETALLE (sale_payments), no el resumen de la venta: en un
     * pago combinado el resumen dice "Pago combinado (Efectivo + ...)" y
     * no sirve para saber cuanto entro por cada medio. Con el detalle, cada
     * peso cae en el medio que corresponde.
     */
    private function mediosDePago(?Carbon $desde): array
    {
        $q = DB::table('sale_payments')
            ->join('sales', 'sales.id_sale', '=', 'sale_payments.id_sale')
            ->groupBy('sale_payments.payment_method')
            ->select('sale_payments.payment_method')
            ->selectRaw('SUM(sale_payments.amount) as total')
            ->orderByDesc('total');

        if ($desde !== null) {
            $q->where('sales.created_at', '>=', $desde);
        }

        $filas = $q->get();

        return [
            'labels' => $filas->map(fn ($f) => $f->payment_method)->all(),
            'data'   => $filas->map(fn ($f) => round((float) $f->total, 2))->all(),
        ];
    }

    /**
     * Materias primas con su stock actual y minimo.
     */
    private function materiasPrimas(): array
    {
        $filas = DB::table('raw_materials')
            ->join('raw_material_stock', 'raw_material_stock.id_raw_material', '=', 'raw_materials.id_raw_material')
            ->select('raw_materials.description')
            ->selectRaw('raw_material_stock.remaining_quantity as actual')
            ->selectRaw('raw_material_stock.minimum_stock as minimo')
            ->orderByDesc('raw_material_stock.remaining_quantity')
            ->limit(6)
            ->get();

        return [
            'labels' => $filas->map(fn ($f) => $f->description)->all(),
            'actual' => $filas->map(fn ($f) => (int) $f->actual)->all(),
            'minimo' => $filas->map(fn ($f) => (int) $f->minimo)->all(),
        ];
    }
}
