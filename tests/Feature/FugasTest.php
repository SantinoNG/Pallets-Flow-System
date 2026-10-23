<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Fugas de informacion entre roles.
 *
 * En este sistema un usuario es O empleado O cliente. Los dos comparten la
 * tabla `users`, asi que es facil que un formulario termine ofreciendo a los
 * clientes para algo que es del personal, o que un listado muestre el email
 * de todos.
 *
 * Lo que se verifica:
 * - El Cliente no ve datos de otros usuarios.
 * - El Cliente no ve al personal en su compra.
 * - El formulario de empleados no ofrece a los clientes.
 * - El email de un cliente solo lo ve quien gestiona las fichas (Jefe y
 *   Administrador); al resto del personal le alcanza con el telefono.
 */
class FugasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function como(string $email, ?string $rol = null): User
    {
        $user = User::where('email', $email)->firstOrFail();

        if ($rol) {
            $user->asignarRol($rol);
        }

        $this->actingAs($user);

        return $user;
    }

    /**
     * Los emails de todos los usuarios menos el propio.
     *
     * @return array<string, string>
     */
    private function emailsAjenos(User $propio): array
    {
        $emails = [];

        foreach (User::where('id_user', '!=', $propio->id_user)->get() as $u) {
            $emails[$u->email] = $u->email;
        }

        return $emails;
    }

    #[Test]
    public function el_cliente_no_ve_el_email_de_otros_usuarios(): void
    {
        $cliente = $this->como('cliente@gmail.com', 'Cliente');

        $paginas = [
            '/dashboard', '/articles', '/cart', '/sales', '/profile', '/tutorial',
            '/articles/pdf', '/employees/pdf', '/proveedores/pdf', '/materias-primas/pdf', '/usuarios/pdf',
        ];

        foreach ($paginas as $ruta) {
            $html = $this->get($ruta)->getContent();

            foreach ($this->emailsAjenos($cliente) as $email) {
                $this->assertStringNotContainsString(
                    $email,
                    $html,
                    "En {$ruta} al Cliente se le escapa el email {$email}."
                );
            }
        }
    }

    #[Test]
    public function el_cliente_no_ve_al_personal_en_su_compra(): void
    {
        $cliente = $this->como('carla.mendez@ecommerce.com', 'Cliente');

        $customer = Customer::where('id_user', $cliente->id_user)->firstOrFail();
        $venta = Sale::where('id_customer', $customer->id_customer)->firstOrFail();

        $html = $this->get('/sales/' . $venta->id_sale)->assertOk()->getContent();

        // El nombre del vendedor no es informacion del Cliente.
        $this->assertStringNotContainsString('>Empleado<', $html);
        $this->assertStringNotContainsString('Atendido por', $html);
    }

    #[Test]
    public function el_cliente_no_ve_el_filtro_por_empleado_en_sus_compras(): void
    {
        $this->como('cliente@gmail.com', 'Cliente');

        $html = $this->get('/sales')->assertOk()->getContent();

        // El filtro por empleado es del personal: sus opciones son los
        // nombres de los empleados, que no son informacion del Cliente.
        $this->assertStringNotContainsString('name="empleado"', $html);
        $this->assertStringNotContainsString('>Empleado<', $html);
        $this->assertStringNotContainsString('Carlos Gómez', $html);
    }

    #[Test]
    public function el_comprobante_html_no_filtra_el_email_del_cliente_al_personal_de_ventas(): void
    {
        $this->como('vendedor@ecommerce.com', 'Vendedor');

        $venta = Sale::with(['customer.user', 'cart'])
            ->whereHas('customer.user')
            ->firstOrFail();
        $email = $venta->customer->user->email;

        $html = $this->get(route('cart.receipt', $venta->id_sale))
            ->assertOk()
            ->getContent();

        // El Vendedor ve el telefono para atender al cliente, pero no su
        // email: ese dato queda para Jefe/Administrador.
        $this->assertStringNotContainsString($email, $html);

        $this->como('admin@ecommerce.com', 'Administrador');

        $html = $this->get(route('cart.receipt', $venta->id_sale))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($email, $html);
    }

    #[Test]
    public function el_deposito_no_entra_al_circuito_de_ventas(): void
    {
        $this->como('deposito@ecommerce.com', 'Deposito');

        $venta = Sale::whereHas('cart')->firstOrFail();

        $this->get('/sales')->assertForbidden();
        $this->get('/sales/' . $venta->id_sale)->assertForbidden();
        $this->get('/sales/' . $venta->id_sale . '/pdf')->assertForbidden();
        $this->get('/sales/' . $venta->id_sale . '/receipt')->assertForbidden();
        $this->get('/cart')->assertForbidden();
        $this->get('/cart/' . $venta->cart->id_cart)->assertForbidden();

        // Tampoco se le ofrece el acceso en el menu.
        $html = $this->get('/dashboard')->assertOk()->getContent();
        $this->assertStringNotContainsString('>Carrito<', $html);
    }

    #[Test]
    public function el_comprobante_en_pdf_del_cliente_no_trae_al_personal(): void
    {
        $cliente = $this->como('carla.mendez@ecommerce.com', 'Cliente');

        $customer = Customer::where('id_user', $cliente->id_user)->firstOrFail();
        $venta = Sale::where('id_customer', $customer->id_customer)->firstOrFail();

        // El PDF se arma con esta vista, asi que se revisa el HTML que
        // dompdf convierte en PDF.
        $html = view('sales.pdf', [
            'sale'     => $venta->load('cart.items.article', 'customer.user', 'employee', 'receipt', 'payments'),
            'esCliente' => true,
            'veEmails' => false,
        ])->render();

        $this->assertStringNotContainsString('Atendido por', $html);
        $this->assertStringNotContainsString($cliente->email, $html);
        $this->assertStringContainsString('Forma de pago', $html);
    }

    #[Test]
    public function el_cliente_descarga_su_comprobante_con_el_nombre_de_compra(): void
    {
        $cliente = $this->como('carla.mendez@ecommerce.com', 'Cliente');

        $customer = Customer::where('id_user', $cliente->id_user)->firstOrFail();
        $venta = Sale::where('id_customer', $customer->id_customer)->firstOrFail();

        $respuesta = $this->get('/sales/' . $venta->id_sale . '/pdf');

        $respuesta->assertOk();
        $respuesta->assertHeader('content-type', 'application/pdf');

        // El archivo se nombra con el numero de comprobante (el que el
        // cliente reconoce), no con el id interno de la venta.
        $this->assertStringContainsString(
            'comprobante-compra-' . $venta->receipt->numeroComprobante() . '.pdf',
            $respuesta->headers->get('content-disposition')
        );
    }

    #[Test]
    public function el_personal_descarga_su_comprobante_con_el_nombre_de_venta(): void
    {
        $this->como('admin@ecommerce.com', 'Administrador');

        $venta = Sale::firstOrFail();

        $respuesta = $this->get('/sales/' . $venta->id_sale . '/pdf');

        $respuesta->assertOk();

        $this->assertStringContainsString(
            'comprobante-venta-' . $venta->receipt->numeroComprobante() . '.pdf',
            $respuesta->headers->get('content-disposition')
        );
    }

    #[Test]
    public function el_formulario_de_empleados_no_ofrece_a_los_clientes(): void
    {
        $this->como('admin@ecommerce.com', 'Administrador');

        $html = $this->get('/employees/create')->assertOk()->getContent();

        // Ningun cliente puede aparecer en la lista de candidatos a empleado.
        $clientes = Customer::with('user')->get();

        $this->assertGreaterThan(0, $clientes->count());

        foreach ($clientes as $cliente) {
            $email = $cliente->user?->email;

            if (! $email) {
                continue;
            }

            $this->assertStringNotContainsString(
                $email,
                $html,
                "El formulario de empleados ofrece al cliente {$email}."
            );
        }
    }

    #[Test]
    public function el_vendedor_no_ve_el_email_de_los_clientes(): void
    {
        $vendedor = $this->como('vendedor@ecommerce.com', 'Vendedor');

        // El listado de ventas: nombre y telefono, no el email.
        $html = $this->get('/sales')->assertOk()->getContent();

        foreach ($this->emailsAjenos($vendedor) as $email) {
            // Los del propio personal tampoco se filtran en el listado.
            if (str_contains($email, '@ecommerce.com') && ! str_contains($email, 'cliente')) {
                continue;
            }

            $this->assertStringNotContainsString(
                $email,
                $html,
                'Al vendedor se le escapa el email ' . $email . '.'
            );
        }
    }

    #[Test]
    public function el_administrador_si_ve_el_email_de_los_clientes(): void
    {
        $this->como('admin@ecommerce.com', 'Administrador');

        $html = $this->get('/sales')->assertOk()->getContent();

        // El que gestiona las fichas necesita el email para contactarlos.
        $this->assertStringContainsString('carla.mendez@ecommerce.com', $html);
    }

    #[Test]
    public function el_listado_de_usuarios_se_puede_filtrar_por_tipo_y_por_rol(): void
    {
        $this->como('admin@ecommerce.com', 'Administrador');

        // Solo clientes: no tiene que aparecer ningun empleado.
        $html = $this->get('/usuarios?tipo=clientes')->assertOk()->getContent();

        $this->assertStringContainsString('carla.mendez@ecommerce.com', $html);
        $this->assertStringNotContainsString('vendedor@ecommerce.com', $html);
        $this->assertStringNotContainsString('deposito@ecommerce.com', $html);

        // Solo personal: lo contrario.
        $html = $this->get('/usuarios?tipo=personal')->assertOk()->getContent();

        $this->assertStringContainsString('vendedor@ecommerce.com', $html);
        $this->assertStringNotContainsString('carla.mendez@ecommerce.com', $html);
    }
}
