<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Modulo de Ventas.
 *
 * Antes solo se llegaba a una venta por el flujo del carrito (el boton
 * "confirmar compra"), y `routes/web.php` importaba este controller sin que
 * el archivo existiera. No había forma de listar las ventas hechas ni de
 * ver su detalle.
 */
class SaleController extends Controller
{
    /**
     * Listado de ventas con filtros.
     */
    public function index(Request $request)
    {
        $ventas = Sale::with(['customer.user', 'employee', 'cart.items'])
            // Filtro por medio de pago
            ->when($request->filled('pago'), fn ($q) => $q->where('payment_method', $request->pago))
            // Filtro por empleado que procesó la venta
            ->when($request->filled('empleado'), fn ($q) => $q->where('id_employee', $request->empleado))
            // Filtro por rango de fechas
            ->when($request->filled('desde'), fn ($q) => $q->where('created_at', '>=', $request->desde))
            ->when($request->filled('hasta'), fn ($q) => $q->where('created_at', '<=', Carbon::parse($request->hasta)->endOfDay()))
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        // Opciones de los filtros
        $mediosPago = Sale::select('payment_method')->distinct()->pluck('payment_method');
        $empleados = Employee::with('user')->get();

        // Total del conjunto filtrado (no solo de la pagina)
        $totalFiltrado = Sale::with('cart.items')
            ->when($request->filled('pago'), fn ($q) => $q->where('payment_method', $request->pago))
            ->when($request->filled('empleado'), fn ($q) => $q->where('id_employee', $request->empleado))
            ->when($request->filled('desde'), fn ($q) => $q->where('created_at', '>=', $request->desde))
            ->when($request->filled('hasta'), fn ($q) => $q->where('created_at', '<=', Carbon::parse($request->hasta)->endOfDay()))
            ->get()
            ->sum(fn ($s) => $s->total());

        return view('sales.index', compact(
            'ventas',
            'mediosPago',
            'empleados',
            'totalFiltrado'
        ));
    }

    /**
     * Detalle de una venta.
     */
    public function show(Sale $sale)
    {
        $sale->load('cart.items.article', 'customer.user', 'employee', 'receipt');

        return view('sales.show', compact('sale'));
    }

    /**
     * Comprobante de la venta en PDF.
     *
     * Usa dompdf, que ya venia instalado en el proyecto.
     */
    public function pdf(Sale $sale)
    {
        $sale->load('cart.items.article', 'customer.user', 'employee', 'receipt');

        $pdf = Pdf::loadView('sales.pdf', compact('sale'))
            ->setPaper('a4')
            ->setOption('isRemoteEnabled', true);

        $nombre = "comprobante-venta-{$sale->id_sale}.pdf";

        return $pdf->download($nombre);
    }
}
