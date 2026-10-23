<?php

namespace App\Support;

use App\Models\Article;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\RawMaterial;
use App\Models\Sale;
use App\Models\Supplier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Reporte interno que se le manda a un empleado.
 *
 * El contenido cambia según el rol exacto de la persona:
 *
 *   Deposito   -> pedido de reposición (qué falta, cuánto y a quién comprarlo)
 *   Vendedor   -> ventas del período y disponibilidad del catálogo
 *   Encargado  -> resumen operativo (stock crítico + ventas)
 *   Jefe/Admin -> resumen general del negocio
 *
 * La idea es que el Encargado o el Administrador pueda generar el PDF desde
 * Empleados y mandárselo al empleado que tiene que actuar: el Depósito hace
 * el pedido, el Vendedor sabe qué puede ofrecer.
 */
class ReporteEmpleado
{
    /** Ventana de ventas que muestra el reporte. */
    public const DIAS = 30;

    /**
     * Título y bajada de cada tipo de reporte.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const PRESENTACION = [
        'deposito' => [
            'Pedido de reposición y estado del depósito',
            'Estos son los insumos y artículos que están en el mínimo o por debajo. '
                . 'El pedido se hace a los proveedores que figuran en cada fila.',
        ],
        'vendedor' => [
            'Ventas y disponibilidad del catálogo',
            'Resumen de lo vendido en los últimos días y de los productos que hoy '
                . 'no se pueden ofrecer por falta de stock.',
        ],
        'encargado' => [
            'Resumen operativo',
            'Stock crítico del depósito y ventas del período, para decidir qué '
                . 'reponer y qué empujar en el mostrador.',
        ],
        'general' => [
            'Resumen general del negocio',
            'Ventas, stock y estructura del equipo en una sola hoja.',
        ],
    ];

    /**
     * Arma todos los datos que necesita la vista del PDF.
     *
     * @return array<string, mixed>
     */
    public static function para(Employee $empleado): array
    {
        $rol   = $empleado->role?->role_name ?? 'Sin rol';
        $tipo  = self::tipo($rol);
        $desde = now()->subDays(self::DIAS)->startOfDay();

        [$titulo, $bajada] = self::PRESENTACION[$tipo];

        $insumos  = self::insumos();
        $articulos = self::articulos();

        return [
            'empleado'       => $empleado,
            'rol'            => $rol,
            'tipo'           => $tipo,
            'titulo'         => $titulo,
            'bajada'         => $bajada,
            'desde'          => $desde,
            'dias'           => self::DIAS,
            'insumos'        => $insumos,
            'articulos'      => $articulos,
            'reposicion'     => self::reposicion($insumos),
            'ventas'         => self::ventas($desde),
            'ventasEmpleado' => self::ventas($desde, $empleado->id_employee),
            'masVendidos'    => self::masVendidos($desde),
            'totales'        => self::totales(),
            'porRol'         => self::empleadosPorRol(),
        ];
    }

    /**
     * Traduce el nombre del rol a la variante del reporte.
     *
     * El Jefe y el Administrador comparten el resumen general: los dos
     * necesitan la foto completa, no una tabla de su puesto.
     */
    public static function tipo(string $rol): string
    {
        return match ($rol) {
            'Deposito'  => 'deposito',
            'Vendedor'  => 'vendedor',
            'Encargado' => 'encargado',
            default     => 'general',
        };
    }

    /**
     * Todos los insumos con su estado, no solo los que están bajo mínimo.
     *
     * El Depósito necesita ver el depósito entero: los que están bien para
     * confirmar que no hay que pedir nada y los críticos para armar el pedido.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private static function insumos(): Collection
    {
        return RawMaterial::with(['supplier', 'stock'])
            ->orderBy('description')
            ->get()
            ->map(function (RawMaterial $material) {
                $stock  = (float) ($material->stock->remaining_quantity ?? 0);
                $minimo = (float) ($material->stock->minimum_stock ?? 0);
                $bajo   = $stock <= $minimo;

                // Se pide lo que falta para llegar al mínimo, con un piso de
                // una unidad: si está justo en el mínimo, igual conviene pedir
                // algo para no quedar en el límite.
                $sugerida = $bajo ? max((int) ceil($minimo - $stock), 1) : 0;

                return [
                    'material' => $material,
                    'stock'    => $stock,
                    'minimo'   => $minimo,
                    'bajo'     => $bajo,
                    'sugerida' => $sugerida,
                    'subtotal' => $sugerida * (float) $material->unit_cost,
                ];
            });
    }

    /**
     * Todos los artículos con su estado de stock.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private static function articulos(): Collection
    {
        return Article::with('stock')
            ->orderBy('article_name')
            ->get()
            ->map(function (Article $articulo) {
                $stock  = (int) ($articulo->stock->remaining_quantity ?? 0);
                $minimo = (int) ($articulo->stock->minimum_stock ?? 0);

                return [
                    'articulo' => $articulo,
                    'stock'    => $stock,
                    'minimo'   => $minimo,
                    'bajo'     => $stock <= $minimo,
                    'sinStock' => $stock <= 0,
                    'enMinimo' => $stock > 0 && $stock === $minimo,
                    'faltante' => max($minimo - $stock, 0),
                ];
            });
    }

    /**
     * Totales del pedido: cuántos insumos y cuánto costaría reponerlos.
     *
     * @param  Collection<int, array<string, mixed>>  $insumos
     * @return array{cantidad: int, total: float}
     */
    private static function reposicion(Collection $insumos): array
    {
        $bajos = $insumos->where('bajo', true);

        return [
            'cantidad' => $bajos->count(),
            'total'    => (float) $bajos->sum('subtotal'),
        ];
    }

    /**
     * Ventas de los últimos días, opcionalmente de un empleado.
     *
     * El total sale de los items: la tabla `sales` no guarda el monto.
     *
     * @return array{cantidad: int, total: float, promedio: float}
     */
    private static function ventas($desde, ?int $idEmpleado = null): array
    {
        $cantidad = Sale::where('created_at', '>=', $desde)
            ->when($idEmpleado, fn ($q) => $q->where('id_employee', $idEmpleado))
            ->count();

        $total = (float) DB::table('cart_items')
            ->join('sales', 'sales.id_cart', '=', 'cart_items.id_cart')
            ->where('sales.created_at', '>=', $desde)
            ->when($idEmpleado, fn ($q) => $q->where('sales.id_employee', $idEmpleado))
            ->sum(DB::raw('cart_items.quantity * cart_items.unit_price'));

        return [
            'cantidad' => $cantidad,
            'total'    => $total,
            'promedio' => $cantidad > 0 ? $total / $cantidad : 0,
        ];
    }

    /**
     * Los productos que más salieron en el período.
     *
     * @return Collection<int, object>
     */
    private static function masVendidos($desde, int $limite = 5): Collection
    {
        return DB::table('cart_items')
            ->join('sales', 'sales.id_cart', '=', 'cart_items.id_cart')
            ->join('articles', 'articles.id_article', '=', 'cart_items.id_article')
            ->where('sales.created_at', '>=', $desde)
            ->select(
                'articles.article_name',
                DB::raw('SUM(cart_items.quantity) as unidades'),
                DB::raw('SUM(cart_items.quantity * cart_items.unit_price) as total')
            )
            ->groupBy('articles.id_article', 'articles.article_name')
            ->orderByDesc('unidades')
            ->limit($limite)
            ->get();
    }

    /**
     * Números generales del negocio para el reporte del Jefe/Administrador.
     *
     * @return array<string, int|float>
     */
    private static function totales(): array
    {
        return [
            'articulos'    => Article::count(),
            'insumos'      => RawMaterial::count(),
            'proveedores'  => Supplier::count(),
            'empleados'    => Employee::count(),
            'clientes'     => Customer::count(),
            'ventas'       => Sale::count(),
            'facturado'    => (float) DB::table('cart_items')
                ->join('sales', 'sales.id_cart', '=', 'cart_items.id_cart')
                ->sum(DB::raw('cart_items.quantity * cart_items.unit_price')),
        ];
    }

    /**
     * Cuántos empleados hay en cada rol.
     *
     * @return Collection<int, object>
     */
    private static function empleadosPorRol(): Collection
    {
        return DB::table('employees')
            ->join('roles', 'roles.id_role', '=', 'employees.id_role')
            ->select('roles.role_name', DB::raw('COUNT(*) as total'))
            ->groupBy('roles.id_role', 'roles.role_name')
            ->orderBy('roles.role_name')
            ->get();
    }
}
