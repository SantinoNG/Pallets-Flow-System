<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\ProductStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Edicion del pedido desde el checkout.
 *
 * Antes, para cambiar un producto o sacarlo habia que volver al carrito,
 * corregir y entrar de nuevo a confirmar. Ahora el checkout tiene los
 * controles: se cambia el producto en el desplegable, se cambia la cantidad
 * o se quita el renglon, y la pantalla se recarga con el total nuevo.
 *
 * Lo importante que se fija aca:
 *  - cambiar el producto actualiza el precio unitario,
 *  - cambiar solo la cantidad NO cambia el precio con el que entro,
 *  - si el producto elegido ya estaba en el carrito, los renglones se
 *    fusionan (no queda el mismo producto dos veces),
 *  - el stock se controla con la cantidad final,
 *  - quitar el ultimo producto vuelve al carrito (el checkout vacio no
 *    tiene nada que confirmar),
 *  - nadie puede tocar el item de un carrito ajeno.
 */
class CheckoutEdicionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function vendedor(): User
    {
        return User::where('email', 'vendedor@ecommerce.com')->firstOrFail();
    }

    private function cliente(): User
    {
        return User::where('email', 'cliente@gmail.com')->firstOrFail();
    }

    /**
     * Arma un carrito con los articulos pedidos.
     *
     * @param  array<int, array{precio: float, stock?: int, cantidad?: int}>  $definicion
     * @return array{0: Cart, 1: array<int, CartItem>}
     */
    private function carritoCon(array $definicion, ?Customer $cliente = null): array
    {
        if (! $cliente) {
            $user = User::factory()->create();

            $cliente = Customer::create([
                'id_user'    => $user->id_user,
                'first_name' => 'Cliente',
                'last_name'  => 'De Prueba',
            ]);
        }

        $cart = Cart::create([
            'id_customer' => $cliente->id_customer,
            'status'      => 'active',
        ]);

        $items = [];

        foreach ($definicion as $fila) {
            $article = Article::factory()->create([
                'unit_price' => $fila['precio'],
            ]);

            ProductStock::create([
                'id_article'         => $article->id_article,
                'remaining_quantity' => $fila['stock'] ?? 100,
                'minimum_stock'      => 1,
            ]);

            $items[] = CartItem::create([
                'id_cart'    => $cart->id_cart,
                'id_article' => $article->id_article,
                'quantity'   => $fila['cantidad'] ?? 1,
                'unit_price' => $fila['precio'],
            ]);
        }

        return [$cart, $items];
    }

    /**
     * Crea un articulo con stock que NO esta en el carrito, para probar el
     * cambio de producto sin que se fusione con otro renglon.
     */
    private function articuloSuelto(float $precio, int $stock = 100): Article
    {
        $article = Article::factory()->create(['unit_price' => $precio]);

        ProductStock::create([
            'id_article'         => $article->id_article,
            'remaining_quantity' => $stock,
            'minimum_stock'      => 1,
        ]);

        return $article;
    }

    private function actualizar(Cart $cart, CartItem $item, array $datos)
    {
        return $this->from(route('cart.checkout', $cart->id_cart))
            ->actingAs($this->vendedor())
            ->patch(route('cart.updateItem', [$cart->id_cart, $item->id_cart_item]), $datos);
    }

    private function quitar(Cart $cart, CartItem $item)
    {
        return $this->from(route('cart.checkout', $cart->id_cart))
            ->actingAs($this->vendedor())
            ->delete(route('cart.removeItem', [$cart->id_cart, $item->id_cart_item]), [
                'quantity' => $item->quantity,
            ]);
    }

    // ------------------------------------------------------------------
    //  Cantidad
    // ------------------------------------------------------------------

    #[Test]
    public function desde_el_checkout_se_puede_cambiar_la_cantidad(): void
    {
        [$cart, $items] = $this->carritoCon([['precio' => 1000.00, 'cantidad' => 1]]);

        $this->actualizar($cart, $items[0], [
            'id_article' => $items[0]->id_article,
            'quantity'   => 3,
        ])->assertRedirect(route('cart.checkout', $cart->id_cart));

        $this->assertSame(3, $items[0]->fresh()->quantity);
    }

    #[Test]
    public function cambiar_la_cantidad_no_cambia_el_precio_del_carrito(): void
    {
        [$cart, $items] = $this->carritoCon([['precio' => 1000.00, 'cantidad' => 1]]);

        // El precio del catalogo sube DESPUES de que el articulo entro al
        // carrito. Corregir la cantidad no tiene que re-preciar la venta.
        Article::where('id_article', $items[0]->id_article)->update(['unit_price' => 2000.00]);

        $this->actualizar($cart, $items[0], [
            'id_article' => $items[0]->id_article,
            'quantity'   => 2,
        ]);

        $item = $items[0]->fresh();

        $this->assertSame(2, $item->quantity);
        $this->assertEqualsWithDelta(1000.00, (float) $item->unit_price, 0.01);
    }

    // ------------------------------------------------------------------
    //  Producto
    // ------------------------------------------------------------------

    #[Test]
    public function desde_el_checkout_se_puede_cambiar_el_producto(): void
    {
        [$cart, $items] = $this->carritoCon([['precio' => 1000.00]]);

        // Otro producto del catalogo que no esta en el carrito.
        $nuevo = $this->articuloSuelto(4500.00);

        $this->actualizar($cart, $items[0], [
            'id_article' => $nuevo->id_article,
            'quantity'   => 2,
        ])->assertRedirect(route('cart.checkout', $cart->id_cart));

        // El renglon ahora es el otro producto, con SU precio.
        $this->assertSame($nuevo->id_article, $items[0]->fresh()->id_article);
        $this->assertEqualsWithDelta(4500.00, (float) $items[0]->fresh()->unit_price, 0.01);
        $this->assertSame(2, $items[0]->fresh()->quantity);
    }

    #[Test]
    public function si_el_producto_elegido_ya_estaba_los_renglones_se_fusionan(): void
    {
        [$cart, $items] = $this->carritoCon([
            ['precio' => 1000.00, 'cantidad' => 1],
            ['precio' => 4500.00, 'cantidad' => 2],
        ]);

        // El renglon 1 pasa a ser el producto del renglon 2, con 3 unidades:
        // 3 + las 2 que ya habia = 5, en un solo renglon.
        $this->actualizar($cart, $items[0], [
            'id_article' => $items[1]->id_article,
            'quantity'   => 3,
        ]);

        $this->assertSame(1, $cart->items()->count(), 'No puede quedar el mismo producto dos veces.');
        $this->assertSame(5, $cart->items()->first()->quantity);
        $this->assertSame($items[1]->id_article, $cart->items()->first()->id_article);
    }

    #[Test]
    public function no_se_puede_pasar_del_stock(): void
    {
        [$cart, $items] = $this->carritoCon([['precio' => 1000.00, 'stock' => 4]]);

        $this->actualizar($cart, $items[0], [
            'id_article' => $items[0]->id_article,
            'quantity'   => 10,
        ])->assertSessionHas('error');

        $this->assertSame(1, $items[0]->fresh()->quantity, 'La cantidad no se tiene que tocar.');
    }

    #[Test]
    public function la_cantidad_tiene_que_ser_al_menos_uno(): void
    {
        [$cart, $items] = $this->carritoCon([['precio' => 1000.00]]);

        $this->actualizar($cart, $items[0], [
            'id_article' => $items[0]->id_article,
            'quantity'   => 0,
        ])->assertSessionHasErrors('quantity');

        $this->assertSame(1, $items[0]->fresh()->quantity);
    }

    // ------------------------------------------------------------------
    //  Quitar
    // ------------------------------------------------------------------

    #[Test]
    public function desde_el_checkout_se_puede_quitar_un_producto(): void
    {
        [$cart, $items] = $this->carritoCon([
            ['precio' => 1000.00],
            ['precio' => 2000.00],
        ]);

        $this->quitar($cart, $items[0])
            ->assertRedirect(route('cart.checkout', $cart->id_cart));

        $this->assertDatabaseMissing('cart_items', ['id_cart_item' => $items[0]->id_cart_item]);
        $this->assertSame(1, $cart->items()->count());
    }

    #[Test]
    public function quitar_el_ultimo_producto_vuelve_al_carrito(): void
    {
        [$cart, $items] = $this->carritoCon([['precio' => 1000.00]]);

        // Quitar el unico renglon deja el carrito vacio: no tiene sentido
        // quedarse en el checkout, que no tendria nada que confirmar.
        $this->quitar($cart, $items[0])
            ->assertRedirect(route('cart.show', $cart->id_cart));

        $this->assertSame(0, $cart->items()->count());
        $this->assertSame('abandoned', $cart->fresh()->status);
    }

    // ------------------------------------------------------------------
    //  Seguridad y pantalla
    // ------------------------------------------------------------------

    #[Test]
    public function no_se_puede_editar_un_item_de_otro_carrito(): void
    {
        [$cartA] = $this->carritoCon([['precio' => 1000.00]]);
        [$cartB, $itemsB] = $this->carritoCon([['precio' => 2000.00]]);

        // El item es del carrito B, pero la URL dice carrito A.
        $this->actingAs($this->vendedor())
            ->patch(route('cart.updateItem', [$cartA->id_cart, $itemsB[0]->id_cart_item]), [
                'id_article' => $itemsB[0]->id_article,
                'quantity'   => 5,
            ])
            ->assertNotFound();

        $this->assertSame(1, $itemsB[0]->fresh()->quantity);
    }

    #[Test]
    public function el_cliente_puede_editar_su_propio_carrito(): void
    {
        $cliente = Customer::whereHas('user', fn ($q) => $q->where('email', 'cliente@gmail.com'))
            ->firstOrFail();

        [$cart, $items] = $this->carritoCon([['precio' => 1000.00]], $cliente);

        $this->from(route('cart.checkout', $cart->id_cart))
            ->actingAs($this->cliente())
            ->patch(route('cart.updateItem', [$cart->id_cart, $items[0]->id_cart_item]), [
                'id_article' => $items[0]->id_article,
                'quantity'   => 4,
            ])
            ->assertRedirect(route('cart.checkout', $cart->id_cart));

        $this->assertSame(4, $items[0]->fresh()->quantity);
    }

    #[Test]
    public function el_checkout_tiene_los_controles_de_edicion(): void
    {
        [$cart, $items] = $this->carritoCon([['precio' => 1000.00]]);

        $html = $this->actingAs($this->vendedor())
            ->get(route('cart.checkout', $cart->id_cart))
            ->assertOk()
            ->getContent();

        // Formulario de cambio de producto/cantidad y de quita, y el alta de
        // otro producto, todos en la misma pantalla.
        $this->assertStringContainsString(
            route('cart.updateItem', [$cart->id_cart, $items[0]->id_cart_item]),
            $html
        );
        $this->assertStringContainsString(
            route('cart.removeItem', [$cart->id_cart, $items[0]->id_cart_item]),
            $html
        );
        $this->assertStringContainsString(route('cart.addItem', $cart->id_cart), $html);
        $this->assertStringContainsString('Agregar otro producto', $html);
        $this->assertStringContainsString('Quitar', $html);
    }

    #[Test]
    public function el_checkout_vacio_vuelve_al_carrito(): void
    {
        [$cart] = $this->carritoCon([['precio' => 1000.00]]);

        $cart->items()->delete();

        $this->actingAs($this->vendedor())
            ->get(route('cart.checkout', $cart->id_cart))
            ->assertRedirect(route('cart.show', $cart->id_cart));
    }
}
