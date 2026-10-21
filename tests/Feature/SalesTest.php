<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SalesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Se corre el DatabaseSeeder completo: es el orden real de seeders
        // (roles -> usuario y sus empleados -> proveedores -> materias primas
        // -> articulos -> ventas) y cada uno depende del anterior por las
        // Foreign Keys.
        $this->seed();
    }

    /**
     * Usuario autenticado con rol, para las pruebas que necesitan uno.
     */
    private function usuarioConRol(string $rol): User
    {
        $user = User::factory()->create();
        $user->asignarRol($rol);

        return $user;
    }

    #[Test]
    public function el_listado_de_ventas_requiere_autenticacion()
    {
        $this->get('/sales')->assertRedirect('/login');
    }

    #[Test]
    public function el_listado_de_ventas_se_ve()
    {
        $user = $this->usuarioConRol('Administrador');

        $response = $this->actingAs($user)->get('/sales');

        $response->assertStatus(200);
        $response->assertViewIs('sales.index');
        $response->assertSee('Ventas');
    }

    #[Test]
    public function el_listado_muestra_las_ventas_con_su_total()
    {
        $user = $this->usuarioConRol('Administrador');

        $response = $this->actingAs($user)->get('/sales');

        $response->assertStatus(200);

        // El SaleSeeder crea ventas para TODOS los clientes (Ana Torres y los
        // 10 de CustomerSeeder), repartidas en los ultimos meses. El test
        // no fija un numero: comprueba que hay al menos una venta por
        // cliente, que es lo que el seeder garantiza.
        $response->assertSee('Venta');
        $this->assertGreaterThanOrEqual(Customer::count(), Sale::all()->count());
        $this->assertGreaterThan(0, Sale::all()->count());
    }

    #[Test]
    public function se_puede_filtrar_por_medio_de_pago()
    {
        $user = $this->usuarioConRol('Administrador');

        $response = $this->actingAs($user)->get('/sales?pago=Efectivo');

        $response->assertStatus(200);

        $efectivo = Sale::where('payment_method', 'Efectivo')->count();
        $this->assertGreaterThan(0, $efectivo);
        $this->assertLessThan(Sale::count(), $efectivo);
    }

    #[Test]
    public function el_total_de_una_venta_suma_sus_items()
    {
        $user = $this->usuarioConRol('Administrador');
        $venta = Sale::with('cart.items')->first();

        $esperado = $venta->cart->items->sum(fn ($i) => $i->quantity * $i->unit_price);

        $this->assertEqualsWithDelta($esperado, $venta->total(), 0.01);
    }

    #[Test]
    public function se_puede_ver_el_detalle_de_una_venta()
    {
        $user = $this->usuarioConRol('Administrador');
        $venta = Sale::first();

        $response = $this->actingAs($user)->get("/sales/{$venta->id_sale}");

        $response->assertStatus(200);
        $response->assertViewIs('sales.show');
        $response->assertSee('Cliente');
        $response->assertSee('Artículos vendidos');
    }

    #[Test]
    public function el_detalle_de_una_venta_inexistente_da_404()
    {
        $user = $this->usuarioConRol('Administrador');

        $this->actingAs($user)->get('/sales/9999')->assertNotFound();
    }

    #[Test]
    public function se_puede_descargar_el_comprobante_en_pdf()
    {
        $user = $this->usuarioConRol('Administrador');
        $venta = Sale::first();

        $response = $this->actingAs($user)->get("/sales/{$venta->id_sale}/pdf");

        $response->assertStatus(200);

        // dompdf devuelve un PDF (empieza con %PDF). No es una respuesta
        // "streamed", asi que se lee con getContent().
        $this->assertStringContainsString('%PDF', $response->getContent());
    }

    #[Test]
    public function el_comprobante_pdf_lleva_el_total_de_la_venta()
    {
        $user = $this->usuarioConRol('Administrador');
        $venta = Sale::with('cart.items')->first();

        $contenido = $this->textoDelPdf(
            $this->actingAs($user)
                ->get("/sales/{$venta->id_sale}/pdf")
                ->getContent()
        );

        // El total se imprime con el formato del helper: 1.234,56
        $this->assertStringContainsString(
            number_format($venta->total(), 2, ',', '.'),
            $contenido
        );
    }

    /**
     * Saca el texto de un PDF.
     *
     * dompdf comprime los streams del PDF (opcion 'compress', true por
     * default), asi que el texto no se puede buscar directamente en los bytes.
     *
     * Ademas, dompdf escribe cada glifo con un byte nulo de separacion
     * (el texto va codificado a 2 bytes por caracter), por eso hay que
     * quitar los nulos para poder buscar una cadena normal.
     */
    private function textoDelPdf(string $pdf): string
    {
        $texto = '';

        if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $matches)) {
            foreach ($matches[1] as $stream) {
                $descomprimido = @gzuncompress($stream);

                if ($descomprimido !== false) {
                    $texto .= $descomprimido;
                }
            }
        }

        return str_replace("\x00", '', $texto) . $pdf;
    }
}
