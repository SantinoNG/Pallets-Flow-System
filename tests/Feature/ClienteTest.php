<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * El flujo del rol Cliente.
 *
 * El Cliente es un usuario final que COMPRA: ve el catálogo, arma su propio
 * carrito y revisa sus compras. Antes no podía ni ver el catálogo (403) y el
 * carrito le mostraba el flujo de caja del mostrador ("seleccionar
 * cliente"), que es para el personal.
 */
class ClienteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    /**
     * Un usuario con rol Cliente y su ficha de cliente.
     */
    private function cliente(): User
    {
        $user = User::where('email', 'cliente@gmail.com')->firstOrFail();
        $user->asignarRol('Cliente');

        return $user;
    }

    /**
     * Un cliente distinto, para probar los límites.
     */
    private function otroCliente(): User
    {
        $user = User::where('email', 'bruno.ferrari@ecommerce.com')->firstOrFail();
        $user->asignarRol('Cliente');

        return $user;
    }

    #[Test]
    public function el_cliente_puede_ver_el_catalogo(): void
    {
        $response = $this->actingAs($this->cliente())->get('/articles');

        $response->assertOk();
        $response->assertSee('Sillón individual de pallet');
    }

    #[Test]
    public function el_catalogo_no_le_muestra_los_controles_de_gestion(): void
    {
        $html = $this->actingAs($this->cliente())->get('/articles')->assertOk()->getContent();

        // No ve el alta de artículos, ni la edición, ni el borrado, ni el
        // reporte PDF: el catálogo es de lectura para el Cliente.
        $this->assertStringNotContainsString('Nuevo Artículo', $html);
        $this->assertStringNotContainsString('Exportar PDF', $html);
        $this->assertStringNotContainsString('/edit', $html);
        $this->assertStringNotContainsString('Eliminar', $html);
    }

    #[Test]
    public function el_catalogo_le_muestra_el_boton_de_agregar_al_carrito(): void
    {
        $html = $this->actingAs($this->cliente())->get('/articles')->assertOk()->getContent();

        $this->assertStringContainsString('mi-carrito/items', $html);
    }

    #[Test]
    public function el_cliente_no_puede_gestionar_el_inventario(): void
    {
        $cliente = $this->cliente();

        $this->actingAs($cliente)->get('/articles/create')->assertForbidden();
        $this->actingAs($cliente)->get('/articles/pdf')->assertForbidden();
        $this->actingAs($cliente)->post('/articles', [
            'article_name' => 'Articulo injections', 'unit_price' => 100,
            'remaining_quantity' => 1, 'minimum_stock' => 1,
        ])->assertForbidden();
    }

    #[Test]
    public function el_personal_sigue_viendo_el_mostrador_con_el_selector_de_clientes(): void
    {
        $vendedor = User::where('email', 'vendedor@ecommerce.com')->firstOrFail();
        $vendedor->asignarRol('Vendedor');

        $html = $this->actingAs($vendedor)->get('/cart')->assertOk()->getContent();

        // El mostrador sigue siendo el del personal: elige a quién le vende.
        $this->assertStringContainsString('name="id_customer"', $html);
        $this->assertStringContainsString('Seleccioná un cliente', $html);
    }

    #[Test]
    public function el_carrito_del_cliente_redirige_a_su_propio_carrito(): void
    {
        $response = $this->actingAs($this->cliente())->get('/cart');

        $response->assertRedirect(route('cart.mine'));
    }

    #[Test]
    public function el_cliente_puede_agregar_articulos_a_su_carrito(): void
    {
        $cliente = $this->cliente();

        $this->actingAs($cliente)->post('/mi-carrito/items', [
            'id_article' => 1,
            'quantity'   => 2,
        ])->assertRedirect();

        $customer = Customer::where('id_user', $cliente->id_user)->firstOrFail();

        $carrito = Cart::where('id_customer', $customer->id_customer)
            ->where('status', 'active')
            ->first();

        $this->assertNotNull($carrito, 'Se debería haber creado el carrito del cliente.');
        $this->assertCount(1, $carrito->items);
        $this->assertSame(2, $carrito->items->first()->quantity);
    }

    #[Test]
    public function el_cliente_no_puede_abrir_el_carrito_de_otro(): void
    {
        $cliente = $this->cliente();
        $otro = $this->otroCliente();

        $customerOtro = Customer::where('id_user', $otro->id_user)->firstOrFail();

        // Un carrito de otro cliente.
        $carritoAjeno = Cart::create([
            'id_customer' => $customerOtro->id_customer,
            'status'      => 'active',
        ]);

        $this->actingAs($cliente)
            ->get('/cart/' . $carritoAjeno->id_cart)
            ->assertForbidden();
    }

    #[Test]
    public function el_cliente_solo_ve_sus_propias_ventas(): void
    {
        $cliente = $this->cliente();

        $customer = Customer::where('id_user', $cliente->id_user)->firstOrFail();

        $ventasPropias = Sale::where('id_customer', $customer->id_customer)->count();
        $ventasTotales = Sale::count();

        $this->assertGreaterThan(0, $ventasPropias);
        $this->assertLessThan($ventasTotales, $ventasPropias, 'El Cliente debe ver solo las suyas.');

        $html = $this->actingAs($cliente)->get('/sales')->assertOk()->getContent();

        $this->assertStringContainsString('Ana Torres', $html);
        $this->assertStringNotContainsString('Bruno Ferrari', $html);
    }

    #[Test]
    public function el_detalle_de_una_venta_ajena_no_le_muestra(): void
    {
        $cliente = $this->cliente();
        $otro = $this->otroCliente();

        $customerOtro = Customer::where('id_user', $otro->id_user)->firstOrFail();

        $ventaAjena = Sale::where('id_customer', $customerOtro->id_customer)->firstOrFail();

        $this->actingAs($cliente)
            ->get('/sales/' . $ventaAjena->id_sale)
            ->assertNotFound();
    }

    #[Test]
    public function para_el_cliente_una_venta_se_llama_compra(): void
    {
        $cliente = $this->cliente();

        $customer = Customer::where('id_user', $cliente->id_user)->firstOrFail();
        $venta = Sale::where('id_customer', $customer->id_customer)->firstOrFail();

        $html = $this->actingAs($cliente)
            ->get('/sales/' . $venta->id_sale)
            ->assertOk()
            ->getContent();

        // Para el Cliente, una venta es su COMPRA.
        $this->assertStringContainsString('Compra #' . $venta->id_sale, $html);
        $this->assertStringNotContainsString('Venta #' . $venta->id_sale, $html);

        // Y no le muestra el vendedor: en una compra propia no hay.
        // OJO: se busca la etiqueta del campo exacta, no la palabra suelta:
        // el sidebar tiene un link "Empleados" que no tiene nada que ver.
        $this->assertStringNotContainsString('>Empleado<', $html);
        $this->assertStringContainsString('Artículos comprados', $html);
    }

    #[Test]
    public function el_listado_del_cliente_se_titula_mis_compras(): void
    {
        $html = $this->actingAs($this->cliente())
            ->get('/sales')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Mis compras', $html);
    }

    #[Test]
    public function el_menu_que_ve_el_cliente_esta_en_espanol(): void
    {
        $html = $this->actingAs($this->cliente())
            ->get('/dashboard')
            ->assertOk()
            ->getContent();

        // El link del panel de control estaba en ingles ("Dashboard").
        $this->assertStringContainsString('Panel de control', $html);
        $this->assertStringNotContainsString('>Dashboard<', $html);

        // Y el de ventas es "Mis compras".
        $this->assertStringContainsString('Mis compras', $html);
    }

    #[Test]
    public function el_cliente_no_ve_el_panel_de_la_empresa(): void
    {
        $html = $this->actingAs($this->cliente())
            ->get('/dashboard')
            ->assertOk()
            ->getContent();

        // Se miran los comentarios HTML: el sidebar tiene "<!-- Proveedores -->"
        // como comentario del source, que se renderiza siempre aunque el link
        // no aparezca para el Cliente.
        $visible = preg_replace('/<!--.*?-->/s', '', $html);

        // El panel del negocio muestra datos de la empresa: la venta total,
        // el stock, los proveedores y los medios de pago. Un Cliente no
        // tiene por que verlos.
        $this->assertStringNotContainsString('Rendimiento de Ventas', $visible);
        $this->assertStringNotContainsString('Estado de Inventario', $visible);
        $this->assertStringNotContainsString('Facturación por Medio de Pago', $visible);
        $this->assertStringNotContainsString('Materias Primas', $visible);
        $this->assertStringNotContainsString('Proveedores', $visible);
        $this->assertStringNotContainsString('Alertas de Stock', $visible);

        // Y ve el suyo, con sus compras.
        $this->assertStringContainsString('Mi panel', $visible);
        $this->assertStringContainsString('Compras realizadas', $visible);
        $this->assertStringContainsString('Total gastado', $visible);
        $this->assertStringContainsString('Mis últimas compras', $visible);
    }

    #[Test]
    public function el_cliente_no_puede_pedir_el_panel_de_la_empresa_por_livewire(): void
    {
        $cliente = $this->cliente();

        // El componente Livewire se puede pedir directo por su endpoint.
        // Sin el chequeo dentro del componente, el Cliente se llevaria los
        // KPIs de la empresa: la venta total, el stock y los proveedores.
        //
        // OJO: la sesion se activa con actingAs() del TestCase y NO con el
        // del Testable de Livewire, porque Livewire::test() ya renderiza el
        // componente antes de que el actingAs() de la cadena se aplique:
        // el componente veia auth()->user() en null y no abortaba.
        $this->actingAs($cliente);

        \Livewire\Livewire::test(\App\Livewire\Dashboard::class)
            ->assertStatus(403);
    }

    #[Test]
    public function el_cliente_solo_ve_sus_propias_compras_en_su_panel(): void
    {
        $cliente = $this->cliente();

        $customer = Customer::where('id_user', $cliente->id_user)->firstOrFail();

        $propias = Sale::where('id_customer', $customer->id_customer)->count();
        $totales = Sale::count();

        $html = $this->actingAs($cliente)
            ->get('/dashboard')
            ->assertOk()
            ->getContent();

        // El panel muestra la cantidad de compras SUyas, no las de todos.
        $this->assertStringContainsString('>' . $propias . '<', $html);
        $this->assertLessThan($totales, $propias, 'El Cliente debe tener menos compras que el total.');
    }
}
