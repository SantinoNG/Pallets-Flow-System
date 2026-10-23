<?php

namespace App\Http\Middleware;

use App\Models\Cart;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Impide que el rol Cliente toque el carrito de otro.
 *
 * Las rutas del carrito (/cart/{cart}...) son de `auth`, porque las usa el
 * personal de ventas en el mostrador: el vendedor abre el carrito del
 * cliente que tiene enfrente. Para el Cliente eso no sirve: su carrito es el
 * suyo y no tiene por que ver el de los demas.
 *
 * Sin este middleware, un Cliente con el id de otro carrito en la URL
 * podria ver sus productos, sacar items y hasta confirmar la venta.
 */
class CarritoPropio
{
    public function handle(Request $request, Closure $next): Response
    {
        $cart = $request->route('cart');

        // Solo aplica al rol Cliente; el personal opera cualquier carrito.
        if ($cart instanceof Cart && $request->user()?->hasRole('Cliente')) {
            $idCustomer = $request->user()->customer?->id_customer;

            if ($idCustomer === null || (int) $cart->id_customer !== (int) $idCustomer) {
                abort(403, 'Ese carrito no pertenece a tu usuario.');
            }
        }

        return $next($request);
    }
}
