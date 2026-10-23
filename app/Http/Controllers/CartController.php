<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Article;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\Receipt;
use App\Models\ProductStock;
use App\Support\Iva;
use App\Support\MediosPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CartController extends Controller
{
    // PASO 1 — Muestra el formulario para iniciar una venta
    public function index(Request $request)
    {
        /*
         * El rol Cliente no pasa por el mostrador: su carrito es el suyo.
         * Si lo dejamos en la pagina de "seleccionar cliente", un cliente
         * veria un formulario para elegir a quien le compra, que no tiene
         * sentido. Se lo manda a su propio carrito.
         *
         * El Depósito tampoco entra al mostrador: la ruta lo bloquea antes
         * de llegar acá y el menú no le muestra el acceso.
         */
        if ($this->esCliente($request)) {
            return redirect()->route('cart.mine');
        }

        $customers = Customer::with('user')->get();
        $articles  = Article::with('stock')->get();
        $cart      = null;

        return view('cart.index', compact('customers', 'articles', 'cart'));
    }

    /**
     * El carrito del usuario con rol Cliente.
     *
     * Si no tiene uno activo se le crea. Si su usuario no tiene ficha de
     * cliente, se le avisa (el error lo ve el administrador).
     */
    public function miCarrito(Request $request)
    {
        $cart = $this->carritoActivoDelCliente($request);

        if (! $cart) {
            /*
             * OJO: aca NO se puede redirigir a cart.index. Esa pagina, para
             * el rol Cliente, redirige a su vez a /mi-carrito: el navegador
             * quedaba en un bucle infinito (ERR_TOO_MANY_REDIRECTS).
             *
             * Al panel: es una pagina que el Cliente si puede ver.
             */
            return redirect()->route('dashboard')
                ->with('error', 'Tu usuario no tiene una ficha de cliente. Contactá al administrador.');
        }

        return redirect()->route('cart.show', $cart->id_cart);
    }

    /**
     * Agrega un artículo al carrito del Cliente.
     *
     * El carrito se resuelve desde la ficha del usuario, no desde un
     * parámetro: así un Cliente no puede meter cosas en el carrito de otro.
     */
    public function agregarAlMiCarrito(Request $request)
    {
        $request->validate([
            'id_article' => 'required|exists:articles,id_article',
            'quantity'   => 'required|integer|min:1',
        ]);

        $cart = $this->carritoActivoDelCliente($request);

        if (! $cart) {
            return back()->with('error', 'Tu usuario no tiene una ficha de cliente. Contactá al administrador.');
        }

        return $this->agregarItem($request, $cart);
    }

    /**
     * Carrito activo del cliente de la sesión (o null si no es Cliente).
     *
     * Si al usuario le falta la ficha de cliente, se le crea en el momento:
     * sin ficha no hay carrito, y el cliente se quedaba sin poder comprar.
     */
    private function carritoActivoDelCliente(Request $request): ?Cart
    {
        $user = $request->user();

        // El personal no tiene carrito propio: usa el mostrador (/cart).
        if (! $user || ! $user->hasRole('Cliente')) {
            return null;
        }

        $customer = $user->asegurarFichaCliente();

        return Cart::firstOrCreate(
            ['id_customer' => $customer->id_customer, 'status' => 'active'],
            []
        );
    }

    /**
     * True si el usuario de la sesión tiene el rol Cliente.
     */
    private function esCliente(Request $request): bool
    {
        return $request->user()?->hasRole('Cliente') ?? false;
    }

    // PASO 2 — Crea o recupera el carrito activo del cliente
    public function createOrGetCart(Request $request)
    {
        $request->validate([
            'id_customer' => 'required|exists:customers,id_customer',
        ]);

        // Buscar carrito activo o crear uno nuevo (DSD-4 paso 3)
        $cart = Cart::where('id_customer', $request->id_customer)
                    ->where('status', 'active')
                    ->first();

        if (!$cart) {
            $cart = Cart::create([
                'id_customer' => $request->id_customer,
                'status'      => 'active',
            ]);
        }

        return redirect()->route('cart.show', $cart->id_cart);
    }

    // PASO 3 — Muestra el carrito con sus items
    public function show(Cart $cart)
    {
        $cart->load('items.article', 'customer.user');
        $articles = Article::with('stock')->get();
        $subtotal = $cart->items->sum(fn($item) => $item->unit_price * $item->quantity);

        // El Cliente ve "Mi carrito"; el personal, la venta que está armando.
        $esCliente = request()->user()?->hasRole('Cliente') ?? false;

        return view('cart.show', compact('cart', 'articles', 'subtotal', 'esCliente'));
    }

    // PASO 4 — Agrega un artículo al carrito (DSD-4)
    public function addItem(Request $request, Cart $cart)
    {
        $request->validate([
            'id_article' => 'required|exists:articles,id_article',
            'quantity'   => 'required|integer|min:1',
        ]);

        return $this->agregarItem($request, $cart);
    }

    /**
     * Agrega un artículo a un carrito concreto.
     *
     * La lógica está separada porque la usan dos entradas distintas: el
     * mostrador (donde el carrito viene en la URL) y el carrito del Cliente
     * (que se resuelve desde su ficha).
     */
    private function agregarItem(Request $request, Cart $cart)
    {
        $article = Article::with('stock')->findOrFail($request->id_article);

        // Verificar stock (DSD-4 paso 2)
        $stock = $article->stock;
        if (!$stock || $stock->remaining_quantity < $request->quantity) {
            return back()->with('error', 'Stock insuficiente. Disponible: ' . ($stock->remaining_quantity ?? 0));
        }

        // Verificar si el artículo ya existe en el carrito (DSD-4 paso 4)
        $item = CartItem::where('id_cart', $cart->id_cart)
                        ->where('id_article', $request->id_article)
                        ->first();

        if ($item) {
            // Verificar que la nueva cantidad total no supere el stock
            $newQty = $item->quantity + $request->quantity;
            if ($newQty > $stock->remaining_quantity) {
                return back()->with('error', 'Stock insuficiente para esa cantidad.');
            }
            $item->quantity = $newQty;
            $item->save();
        } else {
            // Crear nuevo CartItem (DSD-4 paso 5)
            CartItem::create([
                'id_cart'    => $cart->id_cart,
                'id_article' => $request->id_article,
                'quantity'   => $request->quantity,
                'unit_price' => $article->unit_price,
            ]);
        }

        return back()->with('success', '«' . $article->article_name . '» agregado al carrito.');
    }

    // PASO 5 — Elimina o reduce un item del carrito (DSD-5)
    public function removeItem(Request $request, Cart $cart, CartItem $item)
    {
        // El item tiene que ser de ESTE carrito: si no, se podria borrar el
        // item de otro cambiando el id de la URL.
        abort_unless((int) $item->id_cart === (int) $cart->id_cart, 404);

        // Sin cantidad, se entiende que se quita el renglon entero.
        $cantidad = (int) ($request->quantity ?? $item->quantity);

        // Si la cantidad a eliminar >= cantidad actual, borrar el item completo
        if ($cantidad >= $item->quantity) {
            $item->delete();
        } else {
            $item->quantity -= $cantidad;
            $item->save();
        }

        /*
         * Si el carrito queda vacío, marcarlo como abandonado (DSD-5 paso 6).
         *
         * Se vuelve al carrito y no a `cart.index`: desde el checkout, si se
         * quita el ultimo articulo, el carrito vacio no tiene nada que
         * confirmar. Ademas `cart.index` para el rol Cliente redirige a
         * /mi-carrito, y el usuario terminaba en otra pantalla.
         */
        if ($cart->items()->count() === 0) {
            $cart->status = 'abandoned';
            $cart->save();
            return redirect()->route('cart.show', $cart->id_cart)
                             ->with('success', 'Carrito vaciado.');
        }

        return back()->with('success', 'Artículo eliminado del carrito.');
    }

    /**
     * Cambia el producto o la cantidad de un renglon del carrito.
     *
     * Lo usa el checkout, para poder corregir el pedido sin volver al
     * carrito: se elige otro articulo en el desplegable o se cambia la
     * cantidad, y la pantalla se recarga ya con el total nuevo.
     *
     * Si el articulo elegido ya esta en OTRO renglon del carrito, los dos se
     * fusionan en uno solo (no tiene sentido tener el mismo producto dos
     * veces).
     */
    public function updateItem(Request $request, Cart $cart, CartItem $item)
    {
        abort_unless((int) $item->id_cart === (int) $cart->id_cart, 404);

        $datos = $request->validate([
            'id_article' => 'required|exists:articles,id_article',
            'quantity'   => 'required|integer|min:1',
        ]);

        $article = Article::with('stock')->findOrFail($datos['id_article']);
        $stock   = $article->stock;

        // ¿El articulo elegido ya esta en otro renglon del mismo carrito?
        $repetido = CartItem::where('id_cart', $cart->id_cart)
            ->where('id_article', $article->id_article)
            ->where('id_cart_item', '!=', $item->id_cart_item)
            ->first();

        // La cantidad a controlar contra el stock es la suma de los dos.
        $cantidadTotal = (int) $datos['quantity'] + (int) ($repetido->quantity ?? 0);

        if (! $stock || $stock->remaining_quantity < $cantidadTotal) {
            return back()->with(
                'error',
                'Stock insuficiente para «' . $article->article_name . '». Disponible: '
                    . ($stock->remaining_quantity ?? 0)
            );
        }

        if ($repetido) {
            // Se fusionan: el renglon repetido se queda con la suma y este
            // desaparece.
            $repetido->quantity = $cantidadTotal;
            $repetido->save();
            $item->delete();
        } else {
            /*
             * El precio solo se actualiza cuando cambia el producto. Si solo
             * cambia la cantidad se respeta el precio con el que entro al
             * carrito: no se le cambia el precio al cliente por corregir
             * una cantidad.
             */
            if ((int) $item->id_article !== (int) $article->id_article) {
                $item->unit_price = $article->unit_price;
            }

            $item->id_article = $article->id_article;
            $item->quantity   = $datos['quantity'];
            $item->save();
        }

        return back()->with('success', 'Artículo actualizado.');
    }

    // PASO 6 — Muestra el formulario de confirmación de venta
    public function checkout(Cart $cart)
    {
        $cart->load('items.article', 'customer.user');
        $subtotal = $cart->items->sum(fn($item) => $item->unit_price * $item->quantity);

        // Sin articulos no hay nada que confirmar: se vuelve al carrito.
        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.show', $cart->id_cart)
                ->with('error', 'El carrito está vacío.');
        }

        // Para el Cliente, una venta es su COMPRA: cambian el titulo y los
        // textos del checkout.
        $esCliente = request()->user()?->hasRole('Cliente') ?? false;

        // El catalogo va a la vista para poder cambiar el producto de un
        // renglon sin volver al carrito.
        $articles = Article::with('stock')->get();

        return view('cart.checkout', compact('cart', 'subtotal', 'esCliente', 'articles'));
    }

    // PASO 7 — Confirma la venta, descuenta stock, genera Sale y Receipt
    public function confirmSale(Request $request, Cart $cart)
    {
        /*
         * El cobro llega como una LISTA de pagos, no como un medio suelto.
         *
         * El pago simple es una lista de un elemento; el combinado, una
         * lista de varios. Asi no hay dos caminos distintos para cobrar y
         * el comprobante siempre sabe cuanto se pago con cada medio.
         *
         * Las filas vacias del formulario (el usuario agrego un medio y no
         * lo uso) se descartan antes de validar. Si no, el "min:0.01" las
         * rechazaria y el usuario no entenderia por que.
         */
        $pagos = collect($request->input('payments', []))
            ->filter(fn ($p) => is_array($p) && ($p['amount'] ?? '') !== '' && (float) $p['amount'] > 0)
            ->map(fn ($p) => [
                'method'       => (string) ($p['method'] ?? ''),
                'amount'       => round((float) $p['amount'], 2),
                'installments' => (int) ($p['installments'] ?? 1),
            ])
            ->values()
            ->all();

        $request->merge(['payments' => $pagos]);

        $request->validate([
            'payments'               => ['required', 'array', 'min:1'],
            'payments.*.method'      => ['required', 'string', Rule::in(MediosPago::todos())],
            'payments.*.amount'      => ['required', 'numeric', 'min:0.01'],
            'payments.*.installments' => ['nullable', 'integer', Rule::in(MediosPago::CUOTAS)],
        ], [
            'payments.required'          => 'Elegí al menos un medio de pago.',
            'payments.*.method.required' => 'Cada pago necesita un medio.',
            'payments.*.method.in'       => 'El medio de pago no es válido.',
            'payments.*.amount.required' => 'Cada pago necesita un importe.',
            'payments.*.amount.min'      => 'El importe de cada pago tiene que ser mayor a cero.',
            'payments.*.installments.in' => 'La cantidad de cuotas no es válida.',
        ]);

        // Verificar que el carrito tenga items
        if ($cart->items->count() === 0) {
            return back()->with('error', 'El carrito está vacío.');
        }

        /*
         * Los pagos tienen que sumar EXACTAMENTE el total. Si suman menos,
         * la venta queda con saldo; si suman mas, el comprobante no cierra.
         * Se compara con una tolerancia de un centavo para no pelear con
         * los redondeos de los decimales.
         */
        $total  = round($cart->items->sum(fn ($item) => $item->unit_price * $item->quantity), 2);
        $pagado = round(collect($pagos)->sum('amount'), 2);

        if (abs($pagado - $total) > 0.009) {
            return back()->withInput()->withErrors([
                'payments' => 'Los pagos suman $' . number_format($pagado, 2)
                    . ' y el total es $' . number_format($total, 2)
                    . '. Ajustá los importes hasta que coincidan.',
            ]);
        }

        /*
         * Todo el proceso va DENTRO de la transacción, incluida la
         * validación de stock.
         *
         * Antes la validación estaba fuera y el descuento adentro, con
         * un read-modify-write sin lock. Dos checkouts simultáneos podían
         * pasar los dos la validación y sobre-vender: el stock ya estaba
         * en 1, los dos leían 1, los dos lo dejaban en 0.
         *
         * Con lockForUpdate() la fila de stock queda bloqueada hasta que
         * termina la transacción, así que el segundo checkout la espera y
         * ve el valor ya descontado.
         */
        try {
            DB::transaction(function () use ($cart, $pagos, $total) {

                // Bloquear las filas de stock que van a tocarse
                $idsArticulo = $cart->items->pluck('id_article')->unique();

                $stocks = ProductStock::whereIn('id_article', $idsArticulo)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id_article');

                // Verificar stock con los valores ya bloqueados
                foreach ($cart->items as $item) {
                    $stock = $stocks->get($item->id_article);

                    if (!$stock || $stock->remaining_quantity < $item->quantity) {
                        // Lanza la excepción: se revierte toda la transacción
                        throw new \RuntimeException(
                            'Stock insuficiente para «' . $item->article->article_name . '».'
                        );
                    }
                }

                // Marcar carrito como completado
                $cart->status = 'completed';
                $cart->save();

                // Crear la venta (Sale). El medio de pago se guarda como
                // resumen para el listado; el detalle va en sale_payments.
                $sale = Sale::create([
                    'id_cart'        => $cart->id_cart,
                    'id_customer'    => $cart->id_customer,
                    'id_employee'    => auth()->user()->employee?->id_employee ?? null,
                    'payment_method' => MediosPago::resumen(array_column($pagos, 'method')),
                ]);

                // Un renglon por medio de pago, con su importe y sus cuotas
                foreach ($pagos as $pago) {
                    SalePayment::create([
                        'id_sale'        => $sale->id_sale,
                        'payment_method' => $pago['method'],
                        'amount'         => $pago['amount'],
                        'installments'   => MediosPago::cuotas($pago['method'], $pago['installments']),
                    ]);
                }

                // Descontar stock (las filas ya están bloqueadas)
                foreach ($cart->items as $item) {
                    $stock = $stocks->get($item->id_article);

                    $stock->remaining_quantity -= $item->quantity;
                    $stock->save();
                }

                /*
                 * Numeracion del comprobante.
                 *
                 * El numero es POR CLIENTE: su primera compra es la 1, la
                 * segunda la 2. No se usa el id de la venta porque es
                 * interno y no significa nada para el cliente.
                 *
                 * Si dos cajas cobraran al mismo cliente en el mismo
                 * instante podrian sacar el mismo numero. En este sistema
                 * hay una sola caja, asi que no se bloquea; si algun dia
                 * hay varias, aca va un lock.
                 */
                $numero = (int) Receipt::whereHas(
                    'sale',
                    fn ($q) => $q->where('id_customer', $cart->id_customer)
                )->max('receipt_number') + 1;

                // Los precios del catalogo ya incluyen IVA: se desglosa
                // hacia atras y se guarda el resultado en el comprobante.
                $iva = Iva::desglosar($total);

                // Crear el comprobante (Receipt)
                Receipt::create([
                    'id_sale'        => $sale->id_sale,
                    'receipt_number' => $numero,
                    'point_of_sale'  => '0001',
                    'issue_date'     => now()->toDateString(),
                    'due_date'       => now()->addDays(30)->toDateString(),
                    'tax_rate'       => $iva['tasa'],
                    'net_amount'     => $iva['neto'],
                    'tax_amount'     => $iva['iva'],
                    'total_amount'   => $iva['total'],
                ]);
            });
        } catch (\RuntimeException $e) {
            // La transacción ya se revirtió sola
            return back()->with('error', $e->getMessage());
        }

        // Obtener la venta recién creada para redirigir al comprobante
        $sale = Sale::where('id_cart', $cart->id_cart)->first();

        return redirect()->route('cart.receipt', $sale->id_sale)
                         ->with('success', 'Venta registrada exitosamente.');
    }

    // PASO 8 — Muestra el comprobante de la venta
    public function showReceipt(Request $request, Sale $sale)
    {
        /*
         * El comprobante es la MISMA informacion que /sales/{sale}, asi que
         * lleva el mismo control: el Cliente solo puede ver los comprobantes
         * de sus propias compras.
         */
        $user = $request->user();

        if ($user->hasRole('Cliente')) {
            abort_if(
                $sale->id_customer !== $user->customer?->id_customer,
                404,
                'La venta no existe.'
            );
        }

        $sale->load('cart.items.article', 'customer.user', 'employee', 'receipt', 'payments');
        $subtotal = $sale->cart->items->sum(fn($item) => $item->unit_price * $item->quantity);

        // Neto / IVA / total guardados al emitir. Si el comprobante es
        // viejo y no los tiene, se calculan al vuelo.
        $desglose = $sale->receipt?->desglose() ?? Iva::desglosar((float) $subtotal);

        // Para el Cliente, una venta es su COMPRA: el comprobante se titula
        // distinto.
        $esCliente = $user->hasRole('Cliente');

        // El email del cliente es un dato personal: solo lo ve quien
        // gestiona las fichas (Jefe y Administrador). El Vendedor o el
        // Encargado ven nombre y teléfono, que alcanza para atenderlo. El
        // Cliente ve su propio comprobante, así que no necesita el email.
        $veEmails = $user->isAdmin();

        return view('cart.receipt', compact('sale', 'subtotal', 'desglose', 'esCliente', 'veEmails'));
    }
}