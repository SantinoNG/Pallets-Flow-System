<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Sale;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
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
     * Un usuario es cliente si tiene el rol "Cliente" (el del catalogo de
     * `roles`, que es el que se asigna con User::asignarRol()).
     */
    private function esCliente(User $user): bool
    {
        return $user->hasRole('Cliente');
    }

    /**
     * Las ventas que el usuario puede ver.
     *
     * El Cliente solo ve las SUYAS. El personal (Jefe, Administrador,
     * Vendedor, Encargado, Depósito) ve todas.
     *
     * Antes estas rutas solo tenían middleware('auth'), así que el Cliente
     * veía las ventas de todos los clientes.
     */
    private function scopeVisibles(Builder $q, User $user): Builder
    {
        if (! $this->esCliente($user)) {
            return $q;
        }

        $idCustomer = $user->customer?->id_customer;

        // Un Cliente sin ficha de `customers` no tiene ventas que ver.
        // Se filtra por un id imposible en vez de mostrar todas.
        return $q->where('id_customer', $idCustomer ?? -1);
    }

    /**
     * Aborta con 404 si el usuario no puede ver esa venta.
     *
     * Para el Cliente, una venta de otro cliente no debe existir en su
     * pantalla: 404 y no 403, para no confirmar que ese id existe.
     */
    private function abortarSiNoVisible(Sale $sale, User $user): void
    {
        if (! $this->esCliente($user)) {
            return;
        }

        $idCustomer = $user->customer?->id_customer;

        abort_if(
            $sale->id_customer !== $idCustomer,
            404,
            'La venta no existe.'
        );
    }

    /**
     * Listado de ventas con filtros.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        /*
         * `items` va en el eager load a propósito: la vista llama
         * $venta->total() y $venta->unidades(), que usan la relación
         * items() (hasManyThrough). Sin cargarla, Eloquent la resuelve con
         * una consulta por fila (N+1): el listado llegaba a 25 consultas,
         * 12 de ellas idénticas.
         */
        /*
         * El email del cliente es un dato personal. Solo lo ve quien
         * gestiona las fichas (Jefe y Administrador); el resto del personal
         * (vendedor, encargado, deposito) ve nombre y telefono, que alcanza
         * para saber a quien hay que atender.
         */
        $veEmails = $user->isAdmin();

        $ventas = $this->scopeVisibles(
            Sale::with(['customer.user', 'employee', 'cart.items', 'items']),
            $user
        )
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
        $totalFiltrado = $this->scopeVisibles(
            Sale::with('items'),
            $user
        )
            ->when($request->filled('pago'), fn ($q) => $q->where('payment_method', $request->pago))
            ->when($request->filled('empleado'), fn ($q) => $q->where('id_employee', $request->empleado))
            ->when($request->filled('desde'), fn ($q) => $q->where('created_at', '>=', $request->desde))
            ->when($request->filled('hasta'), fn ($q) => $q->where('created_at', '<=', Carbon::parse($request->hasta)->endOfDay()))
            ->get()
            ->sum(fn ($s) => $s->total());

        /*
         * Para el Cliente, el listado es el de SUS compras: la vista cambia
         * el titulo y las etiquetas.
         *
         * El flag lo pasa el controlador y no se calcula en la vista: un
         * bloque @php dentro del slot de un componente <x-app-layout> no
         * define variables para el resto de la vista.
         */
        $esCliente = $request->user()?->hasRole('Cliente') ?? false;

        return view('sales.index', compact(
            'ventas',
            'mediosPago',
            'empleados',
            'totalFiltrado',
            'esCliente',
            'veEmails'
        ));
    }

    /**
     * Detalle de una venta.
     */
    public function show(Request $request, Sale $sale)
    {
        $this->abortarSiNoVisible($sale, $request->user());

        $sale->load('cart.items.article', 'customer.user', 'employee', 'receipt');

        // Para el Cliente, una venta es su COMPRA.
        $esCliente = $request->user()?->hasRole('Cliente') ?? false;

        // El email del cliente solo lo ve quien gestiona las fichas.
        $veEmails = $request->user()?->isAdmin() ?? false;

        return view('sales.show', compact('sale', 'esCliente', 'veEmails'));
    }

    /**
     * Comprobante de la venta en PDF.
     *
     * Usa dompdf, que ya venia instalado en el proyecto.
     */
    public function pdf(Request $request, Sale $sale)
    {
        $this->abortarSiNoVisible($sale, $request->user());

        $sale->load('cart.items.article', 'customer.user', 'employee', 'receipt');

        // Para el Cliente, una venta es su COMPRA.
        $esCliente = $request->user()?->hasRole('Cliente') ?? false;

        // El email del cliente solo lo ve quien gestiona las fichas.
        $veEmails = $request->user()?->isAdmin() ?? false;

        $pdf = Pdf::loadView('sales.pdf', compact('sale', 'esCliente', 'veEmails'))
            ->setPaper('a4')
            ->setOption('isRemoteEnabled', true);

        // Para el Cliente el comprobante es de su COMPRA.
        $nombre = 'comprobante-' . ($esCliente ? 'compra' : 'venta') . "-{$sale->id_sale}.pdf";

        return $pdf->download($nombre);
    }
}
