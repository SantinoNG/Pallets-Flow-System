<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Article;
use App\Models\Sale;
use App\Models\Receipt;
use App\Models\ProductStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        // Si la cantidad a eliminar >= cantidad actual, borrar el item completo
        if ($request->quantity >= $item->quantity) {
            $item->delete();
        } else {
            $item->quantity -= $request->quantity;
            $item->save();
        }

        // Si el carrito queda vacío, marcarlo como abandonado (DSD-5 paso 6)
        if ($cart->items()->count() === 0) {
            $cart->status = 'abandoned';
            $cart->save();
            return redirect()->route('cart.index')
                             ->with('success', 'Carrito vaciado.');
        }

        return back()->with('success', 'Artículo eliminado del carrito.');
    }

    // PASO 6 — Muestra el formulario de confirmación de venta
    public function checkout(Cart $cart)
    {
        $cart->load('items.article', 'customer.user');
        $subtotal = $cart->items->sum(fn($item) => $item->unit_price * $item->quantity);

        // Para el Cliente, una venta es su COMPRA: cambian el titulo y los
        // textos del checkout.
        $esCliente = request()->user()?->hasRole('Cliente') ?? false;

        return view('cart.checkout', compact('cart', 'subtotal', 'esCliente'));
    }

    // PASO 7 — Confirma la venta, descuenta stock, genera Sale y Receipt
    public function confirmSale(Request $request, Cart $cart)
    {
        $request->validate([
            'payment_method' => 'required|string|in:Efectivo,Tarjeta de crédito,Tarjeta de débito,Transferencia',
        ]);

        // Verificar que el carrito tenga items
        if ($cart->items->count() === 0) {
            return back()->with('error', 'El carrito está vacío.');
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
            DB::transaction(function () use ($cart, $request) {

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

                // Crear la venta (Sale)
                $sale = Sale::create([
                    'id_cart'        => $cart->id_cart,
                    'id_customer'    => $cart->id_customer,
                    'id_employee'    => auth()->user()->employee?->id_employee ?? null,
                    'payment_method' => $request->payment_method,
                ]);

                // Descontar stock (las filas ya están bloqueadas)
                foreach ($cart->items as $item) {
                    $stock = $stocks->get($item->id_article);

                    $stock->remaining_quantity -= $item->quantity;
                    $stock->save();
                }

                // Crear el comprobante (Receipt)
                Receipt::create([
                    'id_sale'    => $sale->id_sale,
                    'issue_date' => now()->toDateString(),
                    'due_date'   => now()->addDays(30)->toDateString(),
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

        $sale->load('cart.items.article', 'customer.user', 'employee', 'receipt');
        $subtotal = $sale->cart->items->sum(fn($item) => $item->unit_price * $item->quantity);

        // Para el Cliente, una venta es su COMPRA: el comprobante se titula
        // distinto.
        $esCliente = $user->hasRole('Cliente');

        return view('cart.receipt', compact('sale', 'subtotal', 'esCliente'));
    }
}