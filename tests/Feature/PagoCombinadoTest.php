<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\ProductStock;
use App\Models\Receipt;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\User;
use App\Support\Iva;
use App\Support\MediosPago;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pago combinado, IVA y numeracion del comprobante.
 *
 * Tres cosas que antes no existian:
 *
 *  - Una venta tenia UN medio de pago. Ahora puede pagarse con varios a la
 *    vez (parte en efectivo, parte con transferencia) y el detalle queda
 *    en `sale_payments`.
 *
 *  - El comprobante no desglosaba el IVA. En Argentina los precios al
 *    publico lo incluyen, pero el documento tiene que mostrar neto + IVA.
 *
 *  - El comprobante mostraba el id de la venta. Ahora muestra un numero
 *    correlativo POR CLIENTE: su primera compra es la 1, la segunda la 2.
 */
class PagoCombinadoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    // ------------------------------------------------------------------
    //  Ayudas
    // ------------------------------------------------------------------

    private function vendedor(): User
    {
        return User::where('email', 'vendedor@ecommerce.com')->firstOrFail();
    }

    private function admin(): User
    {
        return User::where('email', 'admin@ecommerce.com')->firstOrFail();
    }

    /**
     * Un cliente nuevo, sin compras: asi la numeracion arranca en 1 y el
     * test no depende de las ventas sembradas.
     */
    private function clienteNuevo(): Customer
    {
        $user = User::factory()->create();

        return Customer::create([
            'id_user'    => $user->id_user,
            'first_name' => 'Nuevo',
            'last_name'  => 'Cliente',
        ]);
    }

    /**
     * Carrito con un articulo y stock suficiente para confirmarlo.
     */
    private function carritoDe(Customer $cliente, float $precio = 1000.00, int $cantidad = 1): Cart
    {
        $articulo = Article::factory()->create(['unit_price' => $precio]);

        ProductStock::create([
            'id_article'         => $articulo->id_article,
            'remaining_quantity' => 100,
            'minimum_stock'      => 1,
        ]);

        $cart = Cart::create([
            'id_customer' => $cliente->id_customer,
            'status'      => 'active',
        ]);

        CartItem::create([
            'id_cart'    => $cart->id_cart,
            'id_article' => $articulo->id_article,
            'quantity'   => $cantidad,
            'unit_price' => $precio,
        ]);

        return $cart;
    }

    private function confirmar(Cart $cart, array $pagos)
    {
        return $this->actingAs($this->vendedor())
            ->post(route('cart.confirm', $cart->id_cart), ['payments' => $pagos]);
    }

    // ------------------------------------------------------------------
    //  Pago combinado
    // ------------------------------------------------------------------

    #[Test]
    public function un_pago_combinado_guarda_un_renglon_por_medio(): void
    {
        $cart = $this->carritoDe($this->clienteNuevo(), 1000.00, 1);

        $this->confirmar($cart, [
            ['method' => MediosPago::EFECTIVO, 'amount' => 600.00],
            ['method' => MediosPago::TRANSFERENCIA, 'amount' => 400.00],
        ])->assertRedirect();

        $venta = Sale::where('id_cart', $cart->id_cart)->firstOrFail();

        $this->assertSame(2, $venta->payments()->count(), 'Tiene que haber un pago por medio.');
        $this->assertSame(
            'Pago combinado (Efectivo + Transferencia)',
            $venta->payment_method,
            'El resumen de la venta tiene que decir que fue combinado.'
        );

        $this->assertDatabaseHas('sale_payments', [
            'id_sale'        => $venta->id_sale,
            'payment_method' => MediosPago::EFECTIVO,
            'amount'         => 600.00,
        ]);

        $this->assertDatabaseHas('sale_payments', [
            'id_sale'        => $venta->id_sale,
            'payment_method' => MediosPago::TRANSFERENCIA,
            'amount'         => 400.00,
        ]);

        // La suma de los pagos es el total de la venta.
        $this->assertSame(1000.0, round($venta->payments->sum('amount'), 2));
    }

    #[Test]
    public function un_pago_simple_guarda_un_solo_renglon(): void
    {
        $cart = $this->carritoDe($this->clienteNuevo(), 1000.00, 1);

        $this->confirmar($cart, [
            ['method' => MediosPago::EFECTIVO, 'amount' => 1000.00],
        ])->assertRedirect();

        $venta = Sale::where('id_cart', $cart->id_cart)->firstOrFail();

        $this->assertSame(1, $venta->payments()->count());
        $this->assertSame(MediosPago::EFECTIVO, $venta->payment_method);
        $this->assertFalse($venta->esPagoCombinado());
    }

    #[Test]
    public function los_pagos_tienen_que_sumar_el_total(): void
    {
        $cart = $this->carritoDe($this->clienteNuevo(), 1000.00, 1);

        // 600 + 300 = 900, falta 100.
        $this->confirmar($cart, [
            ['method' => MediosPago::EFECTIVO, 'amount' => 600.00],
            ['method' => MediosPago::TRANSFERENCIA, 'amount' => 300.00],
        ])->assertSessionHasErrors('payments');

        $this->assertSame(
            0,
            Sale::where('id_cart', $cart->id_cart)->count(),
            'Con pagos que no cierran no se puede registrar la venta.'
        );

        // El carrito sigue activo: se puede reintentar el cobro.
        $this->assertSame('active', $cart->fresh()->status);
    }

    #[Test]
    public function los_pagos_de_mas_tambien_se_rechazan(): void
    {
        $cart = $this->carritoDe($this->clienteNuevo(), 1000.00, 1);

        $this->confirmar($cart, [
            ['method' => MediosPago::EFECTIVO, 'amount' => 1500.00],
        ])->assertSessionHasErrors('payments');

        $this->assertSame(0, Sale::where('id_cart', $cart->id_cart)->count());
    }

    #[Test]
    public function un_medio_de_pago_desconocido_se_rechaza(): void
    {
        $cart = $this->carritoDe($this->clienteNuevo(), 1000.00, 1);

        $this->confirmar($cart, [
            ['method' => 'Bitcoin', 'amount' => 1000.00],
        ])->assertSessionHasErrors('payments.0.method');

        $this->assertSame(0, Sale::where('id_cart', $cart->id_cart)->count());
    }

    #[Test]
    public function las_filas_vacias_del_formulario_se_ignoran(): void
    {
        $cart = $this->carritoDe($this->clienteNuevo(), 1000.00, 1);

        // El usuario agrego un segundo medio y no lo uso: la fila llega
        // vacia y no tiene que romper el cobro.
        $this->confirmar($cart, [
            ['method' => MediosPago::EFECTIVO, 'amount' => 1000.00],
            ['method' => MediosPago::TARJETA_CREDITO, 'amount' => ''],
        ])->assertRedirect();

        $venta = Sale::where('id_cart', $cart->id_cart)->firstOrFail();

        $this->assertSame(1, $venta->payments()->count());
    }

    #[Test]
    public function sin_ningun_pago_no_se_registra_la_venta(): void
    {
        $cart = $this->carritoDe($this->clienteNuevo(), 1000.00, 1);

        $this->confirmar($cart, [])->assertSessionHasErrors('payments');

        $this->assertSame(0, Sale::where('id_cart', $cart->id_cart)->count());
    }

    // ------------------------------------------------------------------
    //  Numeracion del comprobante
    // ------------------------------------------------------------------

    #[Test]
    public function el_numero_de_comprobante_es_correlativo_por_cliente(): void
    {
        $cliente = $this->clienteNuevo();

        $this->confirmar($this->carritoDe($cliente, 1000.00, 1), [
            ['method' => MediosPago::EFECTIVO, 'amount' => 1000.00],
        ]);

        $this->confirmar($this->carritoDe($cliente, 2000.00, 1), [
            ['method' => MediosPago::EFECTIVO, 'amount' => 2000.00],
        ]);

        $numeros = Receipt::whereHas('sale', fn ($q) => $q->where('id_customer', $cliente->id_customer))
            ->orderBy('id_receipt')
            ->pluck('receipt_number')
            ->all();

        $this->assertSame([1, 2], $numeros, 'La primera compra es la 1 y la segunda la 2.');
    }

    #[Test]
    public function la_numeracion_es_independiente_para_cada_cliente(): void
    {
        $ana = $this->clienteNuevo();
        $beto = $this->clienteNuevo();

        $this->confirmar($this->carritoDe($ana, 1000.00, 1), [
            ['method' => MediosPago::EFECTIVO, 'amount' => 1000.00],
        ]);

        // El primero de Beto tambien es el 1, aunque Ana ya tenga el suyo.
        $this->confirmar($this->carritoDe($beto, 1000.00, 1), [
            ['method' => MediosPago::EFECTIVO, 'amount' => 1000.00],
        ]);

        $reciboBeto = Receipt::whereHas('sale', fn ($q) => $q->where('id_customer', $beto->id_customer))
            ->firstOrFail();

        $this->assertSame(1, $reciboBeto->receipt_number);
        $this->assertSame('0001-00000001', $reciboBeto->numeroComprobante());
    }

    // ------------------------------------------------------------------
    //  IVA
    // ------------------------------------------------------------------

    #[Test]
    public function el_comprobante_desglosa_el_iva_al_21(): void
    {
        $cart = $this->carritoDe($this->clienteNuevo(), 12100.00, 1);

        $this->confirmar($cart, [
            ['method' => MediosPago::EFECTIVO, 'amount' => 12100.00],
        ]);

        $venta   = Sale::where('id_cart', $cart->id_cart)->firstOrFail();
        $recibo  = $venta->receipt;
        $desglose = $recibo->desglose();

        $this->assertSame(21.0, $desglose['tasa']);
        $this->assertEqualsWithDelta(10000.00, $desglose['neto'], 0.01);
        $this->assertEqualsWithDelta(2100.00, $desglose['iva'], 0.01);
        $this->assertEqualsWithDelta(12100.00, $desglose['total'], 0.01);

        // Neto + IVA tiene que dar exactamente el total.
        $this->assertEqualsWithDelta(
            $desglose['total'],
            $desglose['neto'] + $desglose['iva'],
            0.001
        );
    }

    #[Test]
    public function el_iva_se_guarda_y_no_cambia_despues(): void
    {
        $cart = $this->carritoDe($this->clienteNuevo(), 12100.00, 1);

        $this->confirmar($cart, [
            ['method' => MediosPago::EFECTIVO, 'amount' => 12100.00],
        ]);

        $venta = Sale::where('id_cart', $cart->id_cart)->firstOrFail();

        // Si despues cambia el precio del articulo, el comprobante emitido
        // no se tiene que recalcular.
        Article::where('id_article', $cart->items->first()->id_article)
            ->update(['unit_price' => 99999.00]);

        $this->assertEqualsWithDelta(12100.00, $venta->receipt->fresh()->desglose()['total'], 0.01);
    }

    // ------------------------------------------------------------------
    //  Lo que se ve
    // ------------------------------------------------------------------

    #[Test]
    public function el_comprobante_muestra_el_numero_y_no_el_id_de_la_venta(): void
    {
        $cart = $this->carritoDe($this->clienteNuevo(), 1000.00, 1);

        $this->confirmar($cart, [
            ['method' => MediosPago::EFECTIVO, 'amount' => 1000.00],
        ]);

        $venta = Sale::where('id_cart', $cart->id_cart)->firstOrFail();

        $html = $this->actingAs($this->vendedor())
            ->get(route('cart.receipt', $venta->id_sale))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('0001-00000001', $html);
        $this->assertStringContainsString('Compra N.º 1 del cliente', $html);
        $this->assertStringContainsString('IVA 21%', $html);
        $this->assertStringNotContainsString(
            'Comprobante #' . $venta->id_sale,
            $html,
            'El comprobante no tiene que mostrar el id interno de la venta.'
        );
    }

    #[Test]
    public function el_comprobante_lista_los_dos_medios_del_pago_combinado(): void
    {
        $cart = $this->carritoDe($this->clienteNuevo(), 1000.00, 1);

        $this->confirmar($cart, [
            ['method' => MediosPago::EFECTIVO, 'amount' => 600.00],
            ['method' => MediosPago::TRANSFERENCIA, 'amount' => 400.00],
        ]);

        $venta = Sale::where('id_cart', $cart->id_cart)->firstOrFail();

        $html = $this->actingAs($this->vendedor())
            ->get(route('cart.receipt', $venta->id_sale))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Pago combinado', $html);
        $this->assertStringContainsString('Efectivo', $html);
        $this->assertStringContainsString('Transferencia', $html);
    }

    #[Test]
    public function el_pdf_se_descarga_con_el_numero_de_comprobante(): void
    {
        $cart = $this->carritoDe($this->clienteNuevo(), 1000.00, 1);

        $this->confirmar($cart, [
            ['method' => MediosPago::EFECTIVO, 'amount' => 1000.00],
        ]);

        $venta = Sale::where('id_cart', $cart->id_cart)->firstOrFail();

        $this->actingAs($this->vendedor())
            ->get(route('sales.pdf', $venta->id_sale))
            ->assertOk()
            ->assertDownload('comprobante-venta-0001-00000001.pdf');
    }

    #[Test]
    public function el_listado_filtra_por_un_medio_de_un_pago_combinado(): void
    {
        $cliente = $this->clienteNuevo();

        $this->confirmar($this->carritoDe($cliente, 1000.00, 1), [
            ['method' => MediosPago::EFECTIVO, 'amount' => 600.00],
            ['method' => MediosPago::TRANSFERENCIA, 'amount' => 400.00],
        ]);

        $venta = Sale::where('id_customer', $cliente->id_customer)->firstOrFail();

        // Filtrar por Efectivo tiene que traer la venta, aunque el resumen
        // diga "Pago combinado (...)".
        $html = $this->actingAs($this->admin())
            ->get('/sales?pago=' . urlencode(MediosPago::EFECTIVO))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('0001-00000001', $html);
        $this->assertStringContainsString('Pago combinado (Efectivo + Transferencia)', $html);

        $this->assertSame(2, $venta->payments()->count());
    }

    #[Test]
    public function la_venta_sembrada_combinada_tiene_su_detalle(): void
    {
        // SaleSeeder deja una venta de Ana pagada mitad en efectivo y mitad
        // con tarjeta, para que el pago combinado se vea en la demo.
        $combinada = Sale::where('payment_method', 'like', 'Pago combinado%')->first();

        $this->assertNotNull($combinada, 'Tiene que haber una venta combinada sembrada.');
        $this->assertSame(2, $combinada->payments()->count());
        $this->assertEqualsWithDelta(
            $combinada->total(),
            $combinada->payments->sum('amount'),
            0.01
        );
    }

    // ------------------------------------------------------------------
    //  Cuotas con tarjeta de credito
    // ------------------------------------------------------------------

    #[Test]
    public function la_tarjeta_de_credito_guarda_las_cuotas(): void
    {
        $cart = $this->carritoDe($this->clienteNuevo(), 120000.00, 1);

        $this->confirmar($cart, [
            ['method' => MediosPago::TARJETA_CREDITO, 'amount' => 120000.00, 'installments' => 6],
        ])->assertRedirect();

        $pago = Sale::where('id_cart', $cart->id_cart)->firstOrFail()->payments()->firstOrFail();

        $this->assertSame(6, $pago->installments);
        $this->assertTrue($pago->enCuotas());
    }

    #[Test]
    public function un_medio_que_no_es_tarjeta_queda_en_una_cuota(): void
    {
        $cart = $this->carritoDe($this->clienteNuevo(), 1000.00, 1);

        // Aunque el formulario mande cuotas, el efectivo no se paga en
        // cuotas: el servidor lo normaliza a 1.
        $this->confirmar($cart, [
            ['method' => MediosPago::EFECTIVO, 'amount' => 1000.00, 'installments' => 6],
        ])->assertRedirect();

        $pago = Sale::where('id_cart', $cart->id_cart)->firstOrFail()->payments()->firstOrFail();

        $this->assertSame(1, $pago->installments);
        $this->assertFalse($pago->enCuotas());
    }

    #[Test]
    public function una_cantidad_de_cuotas_invalida_se_rechaza(): void
    {
        $cart = $this->carritoDe($this->clienteNuevo(), 1000.00, 1);

        $this->confirmar($cart, [
            ['method' => MediosPago::TARJETA_CREDITO, 'amount' => 1000.00, 'installments' => 5],
        ])->assertSessionHasErrors('payments.0.installments');

        $this->assertSame(0, Sale::where('id_cart', $cart->id_cart)->count());
    }

    #[Test]
    public function el_comprobante_muestra_las_cuotas(): void
    {
        $cart = $this->carritoDe($this->clienteNuevo(), 120000.00, 1);

        $this->confirmar($cart, [
            ['method' => MediosPago::TARJETA_CREDITO, 'amount' => 120000.00, 'installments' => 6],
        ])->assertRedirect();

        $venta = Sale::where('id_cart', $cart->id_cart)->firstOrFail();

        $this->actingAs($this->vendedor())
            ->get(route('cart.receipt', $venta->id_sale))
            ->assertOk()
            ->assertSee('6 cuotas de $20,000.00', false);
    }

    #[Test]
    public function el_medio_de_pago_muestra_las_cuotas(): void
    {
        $pago = new SalePayment([
            'payment_method' => MediosPago::TARJETA_CREDITO,
            'amount'         => 120000.00,
            'installments'   => 6,
        ]);

        $this->assertSame('Tarjeta de crédito (6 cuotas de $20,000.00)', $pago->etiqueta());
        $this->assertTrue($pago->enCuotas());

        // Sin cuotas, la etiqueta es solo el medio.
        $pago->installments = 1;
        $this->assertSame('Tarjeta de crédito', $pago->etiqueta());
        $this->assertFalse($pago->enCuotas());
    }

    // ------------------------------------------------------------------
    //  Las piezas sueltas
    // ------------------------------------------------------------------

    #[Test]
    public function el_desglose_de_iva_cierra_siempre(): void
    {
        foreach ([0.0, 1.0, 999.99, 12100.0, 29500.0, 123456.78] as $total) {
            $d = Iva::desglosar($total);

            $this->assertEqualsWithDelta(
                $total,
                $d['neto'] + $d['iva'],
                0.001,
                "El desglose de {$total} no cierra."
            );
        }
    }

    #[Test]
    public function el_resumen_de_medios_ordena_y_no_repite(): void
    {
        $this->assertSame(
            'Efectivo',
            MediosPago::resumen([MediosPago::EFECTIVO, MediosPago::EFECTIVO])
        );

        $this->assertSame(
            'Pago combinado (Efectivo + Tarjeta de crédito)',
            MediosPago::resumen([MediosPago::TARJETA_CREDITO, MediosPago::EFECTIVO])
        );
    }
}
