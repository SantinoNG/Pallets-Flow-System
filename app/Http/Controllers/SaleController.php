<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\User;
use App\Support\MediosPago;
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
     * El Cliente solo ve las SUYAS. El personal de ventas (Jefe,
     * Administrador, Encargado y Vendedor) ve todas. El Depósito no entra a
     * este módulo: las rutas lo bloquean y su tarea es el stock.
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
         * Para el Cliente, el listado es el de SUS compras: la vista cambia
         * el título y las etiquetas. El flag se calcula acá y no en la
         * vista: un bloque @php dentro del slot de un componente
         * <x-app-layout> no define variables para el resto de la vista.
         */
        $esCliente = $user?->hasRole('Cliente') ?? false;

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
         * de ventas (vendedor y encargado) ve nombre y telefono, que alcanza
         * para saber a quien hay que atender.
         */
        $veEmails = $user->isAdmin();

        $ventas = $this->scopeVisibles(
            Sale::with(['customer.user', 'employee', 'cart.items', 'items', 'receipt', 'payments']),
            $user
        )
            /*
             * Filtro por medio de pago.
             *
             * Se mira el DETALLE (sale_payments), no el resumen: una venta
             * pagada mitad en efectivo y mitad con transferencia tiene que
             * aparecer al filtrar por cualquiera de los dos. El "or" deja
             * entrar las ventas viejas, que solo tienen el resumen.
             */
            ->when($request->filled('pago'), fn ($q) => $q->where(function ($sub) use ($request) {
                $sub->where('payment_method', $request->pago)
                    ->orWhereHas('payments', fn ($p) => $p->where('payment_method', $request->pago));
            }))
            // Filtro por empleado que procesó la venta
            ->when(! $esCliente && $request->filled('empleado'), fn ($q) => $q->where('id_employee', $request->empleado))
            // Filtro por rango de fechas
            ->when($request->filled('desde'), fn ($q) => $q->where('created_at', '>=', $request->desde))
            ->when($request->filled('hasta'), fn ($q) => $q->where('created_at', '<=', Carbon::parse($request->hasta)->endOfDay()))
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        /*
         * Opciones del filtro: los medios del catalogo mas los que ya esten
         * guardados en ventas viejas (por ejemplo, "Tarjeta de debito" sin
         * acento, de una siembra anterior). Asi el filtro no deja afuera
         * datos que existen.
         */
        $mediosPago = collect(MediosPago::todos())
            ->merge(SalePayment::select('payment_method')->distinct()->pluck('payment_method'))
            ->merge(Sale::select('payment_method')->distinct()->pluck('payment_method'))
            ->filter(fn ($m) => ! MediosPago::esCombinado((string) $m))
            ->unique()
            ->values();

        /*
         * El filtro por empleado es del personal. Al Cliente no se le
         * muestran los nombres del personal: su compra ya está filtrada por
         * su propia ficha y el empleado que lo atendió no es dato suyo.
         */
        $empleados = $esCliente ? collect() : Employee::with('user')->get();

        // Total del conjunto filtrado (no solo de la pagina)
        $totalFiltrado = $this->scopeVisibles(
            Sale::with('items'),
            $user
        )
            ->when($request->filled('pago'), fn ($q) => $q->where(function ($sub) use ($request) {
                $sub->where('payment_method', $request->pago)
                    ->orWhereHas('payments', fn ($p) => $p->where('payment_method', $request->pago));
            }))
            ->when(! $esCliente && $request->filled('empleado'), fn ($q) => $q->where('id_employee', $request->empleado))
            ->when($request->filled('desde'), fn ($q) => $q->where('created_at', '>=', $request->desde))
            ->when($request->filled('hasta'), fn ($q) => $q->where('created_at', '<=', Carbon::parse($request->hasta)->endOfDay()))
            ->get()
            ->sum(fn ($s) => $s->total());

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

        $sale->load('cart.items.article', 'customer.user', 'employee', 'receipt', 'payments');

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

        $sale->load('cart.items.article', 'customer.user', 'employee', 'receipt', 'payments');

        // Para el Cliente, una venta es su COMPRA.
        $esCliente = $request->user()?->hasRole('Cliente') ?? false;

        // El email del cliente solo lo ve quien gestiona las fichas.
        $veEmails = $request->user()?->isAdmin() ?? false;

        $pdf = Pdf::loadView('sales.pdf', compact('sale', 'esCliente', 'veEmails'))
            ->setPaper('a4')
            ->setOption('isRemoteEnabled', true);

        // El nombre del archivo usa el numero de comprobante, no el id
        // interno de la venta: es el numero que el cliente reconoce.
        $numero = $sale->receipt?->numeroComprobante() ?? $sale->id_sale;

        $nombre = 'comprobante-' . ($esCliente ? 'compra' : 'venta') . "-{$numero}.pdf";

        return $pdf->download($nombre);
    }
}
