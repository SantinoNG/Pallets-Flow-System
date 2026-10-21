<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * El total y las unidades de una venta.
 *
 * La relación `Sale::items()` estuvo mal armada durante un tiempo: era un
 * hasManyThrough que generaba `where cart.id_cart = ?` con el **id_sale**
 * en el binding, en vez del **id_cart**. Es decir, buscaba el carrito cuyo
 * id_cart coincidiera con el id_sale de la venta.
 *
 * Parecía funcionar porque en los datos sembrados id_sale e id_cart
 * coinciden (el seeder crea las ventas y sus carritos en el mismo orden).
 * Pero en una venta real no coinciden: la venta 39 tiene el carrito 40, y
 * la relación devolvía los items del carrito 39. El listado de ventas
 * mostraba el total y las unidades de otra venta.
 *
 * Estos tests usan a propósito una venta cuyo id_cart es distinto de su
 * id_sale.
 */
class SaleTotalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    /**
     * Crea una venta cuyo id_cart NO es su id_sale.
     *
     * Para lograrlo inserta un carrito de relleno antes, para que los
     * ids se corran.
     */
    private function ventaConCarritoDistinto(): Sale
    {
        // Un carrito de relleno: hace que el id_cart siguiente sea
        // distinto del id_sale siguiente.
        Cart::create(['id_customer' => 1, 'status' => 'abandoned']);

        $cart = Cart::create(['id_customer' => 1, 'status' => 'completed']);

        CartItem::create([
            'id_cart'    => $cart->id_cart,
            'id_article' => 1,
            'quantity'   => 3,
            'unit_price' => 1000.00,
        ]);

        CartItem::create([
            'id_cart'    => $cart->id_cart,
            'id_article' => 2,
            'quantity'   => 2,
            'unit_price' => 500.00,
        ]);

        $venta = Sale::create([
            'id_cart'        => $cart->id_cart,
            'id_customer'    => 1,
            'id_employee'    => null,
            'payment_method' => 'Efectivo',
        ]);

        $this->assertNotSame(
            $venta->id_sale,
            $venta->id_cart,
            'El test necesita una venta cuyo id_cart sea distinto del id_sale.'
        );

        return $venta;
    }

    #[Test]
    public function el_total_sale_de_los_items_de_su_propio_carrito(): void
    {
        $venta = $this->ventaConCarritoDistinto();

        $this->assertSame(
            2,
            $venta->items()->count(),
            'Los items tienen que ser los del carrito de la venta.'
        );

        // 3 x 1000 + 2 x 500 = 4000
        $this->assertSame(4000.00, $venta->total());
    }

    #[Test]
    public function las_unidades_suman_los_items_de_su_propio_carrito(): void
    {
        $venta = $this->ventaConCarritoDistinto();

        $this->assertSame(5, $venta->unidades());
    }

    #[Test]
    public function el_listado_muestra_el_total_correcto(): void
    {
        $venta = $this->ventaConCarritoDistinto();

        $admin = User::where('email', 'admin@ecommerce.com')->firstOrFail();
        $admin->asignarRol('Administrador');

        $html = $this->actingAs($admin)->get('/sales')->assertOk()->getContent();

        // El total de esta venta tiene que aparecer en el listado.
        // La vista lo formatea con number_format($total, 2, ',', '.').
        $this->assertStringContainsString('$4.000,00', $html);
    }

    #[Test]
    public function los_items_de_una_venta_no_se_confunden_con_otra(): void
    {
        $venta = $this->ventaConCarritoDistinto();

        // Otra venta, con otro carrito.
        $otroCart = Cart::create(['id_customer' => 1, 'status' => 'completed']);
        CartItem::create([
            'id_cart'    => $otroCart->id_cart,
            'id_article' => 3,
            'quantity'   => 1,
            'unit_price' => 99999.00,
        ]);

        $otra = Sale::create([
            'id_cart'        => $otroCart->id_cart,
            'id_customer'    => 1,
            'id_employee'    => null,
            'payment_method' => 'Efectivo',
        ]);

        $this->assertSame(4000.00, $venta->total());
        $this->assertSame(99999.00, $otra->total());
    }
}
