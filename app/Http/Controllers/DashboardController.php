<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * El panel de control.
 *
 * Hay DOS paneles distintos:
 *
 * - El del NEGCIO (KPIs de la empresa, gráficos de ventas, stock,
 *   proveedores, medios de pago): es del personal. Un Cliente no puede
 *   verlo, porque le mostraría la facturación total de la empresa y los
 *   datos de los demás clientes.
 *
 * - El del CLIENTE (sus compras, lo que gastó, qué compró): es suyo.
 *
 * Antes los dos eran el mismo: el Cliente veía el panel del negocio.
 */
class DashboardController extends Controller
{
    /**
     * Roles que ven el panel del negocio.
     */
    public const ROLES_NEGOCIO = ['Jefe', 'Administrador', 'Encargado', 'Vendedor', 'Deposito'];

    public function index(Request $request)
    {
        if ($this->esCliente($request)) {
            return view('dashboard.cliente', $this->datosDelCliente($request));
        }

        return view('dashboard');
    }

    /**
     * True si el usuario de la sesión tiene el rol Cliente.
     */
    private function esCliente(Request $request): bool
    {
        return $request->user()?->hasRole('Cliente') ?? false;
    }

    /**
     * Datos del panel personal del Cliente: SOLO los suyos.
     */
    private function datosDelCliente(Request $request): array
    {
        $customer = $request->user()->customer;

        if (! $customer) {
            return [
                'compras'         => 0,
                'totalGastado'    => 0.0,
                'ultimaCompra'    => null,
                'articulos'       => 0,
                'carritoPendiente'=> null,
                'recientes'       => collect(),
            ];
        }

        $ventas = Sale::where('id_customer', $customer->id_customer)
            ->with('cart.items.article')
            ->orderByDesc('created_at')
            ->get();

        // El total sale de los items: la tabla `sales` no tiene columna de
        // monto. Se hace con SQL para no traer todos los items al PHP solo
        // para sumarlos.
        $totalGastado = (float) DB::table('cart_items')
            ->join('sales', 'sales.id_cart', '=', 'cart_items.id_cart')
            ->where('sales.id_customer', $customer->id_customer)
            ->sum(DB::raw('cart_items.quantity * cart_items.unit_price'));

        $articulos = DB::table('cart_items')
            ->join('sales', 'sales.id_cart', '=', 'cart_items.id_cart')
            ->where('sales.id_customer', $customer->id_customer)
            ->distinct()
            ->count('cart_items.id_article');

        return [
            'compras'      => $ventas->count(),
            'totalGastado' => $totalGastado,
            'ultimaCompra' => $ventas->first(),
            'articulos'    => $articulos,
            'recientes'    => $ventas->take(5),
        ];
    }
}
