<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Alta de usuario y de personal.
 *
 * En este sistema un usuario es O empleado O cliente. El flujo correcto es:
 *
 *   1. Se crea la cuenta en "Usuarios y Roles" con un rol.
 *   2. Si el rol es un rol de personal, esa cuenta aparece en el formulario
 *      de empleados y se completa la ficha del empleado.
 *   3. Si el rol es Cliente, la ficha se crea sola (fila en `customers`).
 *
 * Sin el paso 1 el alta de personal quedaba en banda muerta: no habia
 * ninguna cuenta libre y el desplegable salia vacio.
 */
class UsuariosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function comoAdmin(): User
    {
        $admin = User::where('email', 'admin@ecommerce.com')->firstOrFail();

        $this->actingAs($admin);

        return $admin;
    }

    #[Test]
    public function el_administrador_ve_el_formulario_de_alta_de_usuario(): void
    {
        $this->comoAdmin();

        $this->get('/usuarios/create')
            ->assertOk()
            ->assertSee('Nuevo Usuario', false)
            ->assertSee('Nombre y apellido', false);
    }

    #[Test]
    public function el_cliente_no_puede_crear_usuarios(): void
    {
        $cliente = User::where('email', 'cliente@gmail.com')->firstOrFail();

        $this->actingAs($cliente);

        $this->get('/usuarios/create')->assertForbidden();
        $this->post('/usuarios', [])->assertForbidden();
    }

    #[Test]
    public function se_crea_un_usuario_con_su_rol(): void
    {
        $this->comoAdmin();

        $idRolVendedor = \App\Models\Role::where('role_name', 'Vendedor')->value('id_role');

        $this->post('/usuarios', [
            'name'     => 'Rita Gomez',
            'email'    => 'rita.gomez@ecommerce.com',
            'password' => 'clave12345',
            'password_confirmation' => 'clave12345',
            'id_role'  => $idRolVendedor,
        ])->assertRedirect(route('users.index'));

        $nuevo = User::where('email', 'rita.gomez@ecommerce.com')->first();

        $this->assertNotNull($nuevo);
        $this->assertTrue($nuevo->hasRole('Vendedor'));
        $this->assertTrue(Hash::check('clave12345', $nuevo->password));
    }

    #[Test]
    public function un_usuario_con_rol_cliente_tambien_tiene_ficha_de_cliente(): void
    {
        $this->comoAdmin();

        $idRolCliente = \App\Models\Role::where('role_name', 'Cliente')->value('id_role');

        $this->post('/usuarios', [
            'name'     => 'Nora Benitez',
            'email'    => 'nora.benitez@ecommerce.com',
            'password' => 'clave12345',
            'password_confirmation' => 'clave12345',
            'id_role'  => $idRolCliente,
            'first_name' => 'Nora',
            'last_name'  => 'Benitez',
            'phone'      => '3764-555000',
            'address'    => 'Av. Siempreviva 742, Posadas',
        ])->assertRedirect(route('users.index'));

        $nuevo = User::where('email', 'nora.benitez@ecommerce.com')->first();

        $this->assertNotNull($nuevo);

        // La ficha es lo que permite comprar: sin ella no hay carrito.
        $ficha = Customer::where('id_user', $nuevo->id_user)->first();

        $this->assertNotNull($ficha);
        $this->assertSame('Nora', $ficha->first_name);
        $this->assertSame('3764-555000', $ficha->phone);
    }

    #[Test]
    public function con_rol_cliente_la_ficha_es_obligatoria(): void
    {
        $this->comoAdmin();

        $idRolCliente = \App\Models\Role::where('role_name', 'Cliente')->value('id_role');

        $this->post('/usuarios', [
            'name'     => 'Sin Ficha',
            'email'    => 'sin.ficha@ecommerce.com',
            'password' => 'clave12345',
            'password_confirmation' => 'clave12345',
            'id_role'  => $idRolCliente,
        ])->assertSessionHasErrors(['first_name', 'last_name']);

        $this->assertNull(User::where('email', 'sin.ficha@ecommerce.com')->first());
    }

    #[Test]
    public function no_se_puede_repetir_el_email(): void
    {
        $this->comoAdmin();

        $idRolCliente = \App\Models\Role::where('role_name', 'Cliente')->value('id_role');

        $this->post('/usuarios', [
            'name'     => 'Repetido',
            'email'    => 'cliente@gmail.com',
            'password' => 'clave12345',
            'password_confirmation' => 'clave12345',
            'id_role'  => $idRolCliente,
            'first_name' => 'Repetido',
            'last_name'  => 'Uno',
        ])->assertSessionHasErrors('email');
    }

    #[Test]
    public function una_cuenta_nueva_aparece_en_el_formulario_de_empleados(): void
    {
        $this->comoAdmin();

        $idRolVendedor = \App\Models\Role::where('role_name', 'Vendedor')->value('id_role');

        $this->post('/usuarios', [
            'name'     => 'Tulio Paz',
            'email'    => 'tulio.paz@ecommerce.com',
            'password' => 'clave12345',
            'password_confirmation' => 'clave12345',
            'id_role'  => $idRolVendedor,
        ]);

        // La cuenta ya no esta en `customers`, asi que puede ser empleado.
        $html = $this->get('/employees/create')->assertOk()->getContent();

        $this->assertStringContainsString('tulio.paz@ecommerce.com', $html);
    }

    #[Test]
    public function un_cliente_no_aparece_en_el_formulario_de_empleados(): void
    {
        $this->comoAdmin();

        $html = $this->get('/employees/create')->assertOk()->getContent();

        $this->assertStringNotContainsString('cliente@gmail.com', $html);
        $this->assertStringNotContainsString('carla.mendez@ecommerce.com', $html);
    }

    #[Test]
    public function sin_cuentas_libres_el_formulario_explica_que_hacer(): void
    {
        $this->comoAdmin();

        // Todos los usuarios sembrados ya son empleados o clientes.
        $this->assertSame(0, User::whereDoesntHave('employee')->esPersonal()->count());

        $html = $this->get('/employees/create')->assertOk()->getContent();

        $this->assertStringContainsString('No hay cuentas de usuario libres', $html);
        $this->assertStringContainsString(route('users.create'), $html);
    }

    #[Test]
    public function el_listado_se_puede_filtrar_por_rol(): void
    {
        $this->comoAdmin();

        $idRolVendedor = \App\Models\Role::where('role_name', 'Vendedor')->value('id_role');

        $html = $this->get('/usuarios?id_role=' . $idRolVendedor)->assertOk()->getContent();

        $this->assertStringContainsString('vendedor@ecommerce.com', $html);
        $this->assertStringNotContainsString('carla.mendez@ecommerce.com', $html);
    }

    #[Test]
    public function el_buscador_encuentra_por_nombre_o_email(): void
    {
        $this->comoAdmin();

        $html = $this->get('/usuarios?search=Carla')->assertOk()->getContent();

        $this->assertStringContainsString('carla.mendez@ecommerce.com', $html);
        $this->assertStringNotContainsString('vendedor@ecommerce.com', $html);
    }

    #[Test]
    public function el_filtro_personal_muestra_a_los_que_no_son_empleados_todavia(): void
    {
        $this->comoAdmin();

        // Un usuario con rol de personal pero sin ficha de empleado igual
        // es del personal: el rol es la fuente de verdad.
        $idRolVendedor = \App\Models\Role::where('role_name', 'Vendedor')->value('id_role');

        $user = new User();
        $user->forceFill([
            'name'     => 'Sin Ficha De Empleado',
            'email'    => 'sin.ficha.empleado@ecommerce.com',
            'password' => 'clave12345',
            'id_role'  => $idRolVendedor,
        ])->save();
        $user->asignarRol('Vendedor');

        $html = $this->get('/usuarios?tipo=personal')->assertOk()->getContent();

        $this->assertStringContainsString('sin.ficha.empleado@ecommerce.com', $html);
        $this->assertStringNotContainsString('carla.mendez@ecommerce.com', $html);
    }

    #[Test]
    public function cambiar_el_rol_a_cliente_crea_la_ficha_si_falta(): void
    {
        $this->comoAdmin();

        $idRolCliente = \App\Models\Role::where('role_name', 'Cliente')->value('id_role');

        // Un usuario de personal, sin ficha de cliente.
        $vendedor = User::where('email', 'vendedor@ecommerce.com')->firstOrFail();

        $this->assertNull(Customer::where('id_user', $vendedor->id_user)->first());

        $this->put('/usuarios/' . $vendedor->id_user . '/roles', [
            'id_role' => $idRolCliente,
        ])->assertRedirect();

        // Con la ficha creada, el usuario ya puede comprar.
        $ficha = Customer::where('id_user', $vendedor->id_user)->first();

        $this->assertNotNull($ficha);
        $this->assertSame('Vendedor', $ficha->first_name);
    }

    #[Test]
    public function cambiar_el_rol_no_rompe_la_ficha_que_ya_existia(): void
    {
        $this->comoAdmin();

        $idRolCliente = \App\Models\Role::where('role_name', 'Cliente')->value('id_role');
        $idRolVendedor = \App\Models\Role::where('role_name', 'Vendedor')->value('id_role');

        $cliente = User::where('email', 'cliente@gmail.com')->firstOrFail();
        $antes = Customer::where('id_user', $cliente->id_user)->firstOrFail();

        $this->put('/usuarios/' . $cliente->id_user . '/roles', [
            'id_role' => $idRolVendedor,
        ])->assertRedirect();

        $despues = Customer::where('id_user', $cliente->id_user)->firstOrFail();

        $this->assertSame($antes->first_name, $despues->first_name);
        $this->assertSame($antes->phone, $despues->phone);
    }

    #[Test]
    public function el_cliente_no_puede_gestionar_roles(): void
    {
        $cliente = User::where('email', 'cliente@gmail.com')->firstOrFail();

        $this->actingAs($cliente);

        $this->get('/usuarios/' . $cliente->id_user . '/roles')->assertForbidden();
    }

    #[Test]
    public function el_filtro_ignora_los_parametros_vacios(): void
    {
        $this->comoAdmin();

        // Contar filas en vez de mirar nombres: el listado pagina de a 10
        // y que un usuario caiga en la pagina 1 o en la 2 depende del
        // orden, que no es lo que se quiere verificar aca.
        $filas = function (string $url): int {
            $html = $this->get($url)->assertOk()->getContent();

            return substr_count($html, 'Gestionar Roles');
        };

        // Los filtros vacios no recortan nada.
        $this->assertSame($filas('/usuarios'), $filas('/usuarios?tipo=&id_role=&search='));

        // Y con la busqueda acortada el filtro se puede comprobar bien:
        // con tipo=clientes sale el cliente y NO el personal.
        $html = $this->get('/usuarios?tipo=clientes&search=carla')->assertOk()->getContent();

        $this->assertStringContainsString('carla.mendez@ecommerce.com', $html);
        $this->assertStringNotContainsString('vendedor@ecommerce.com', $html);

        // Con tipo=personal, al reves.
        $html = $this->get('/usuarios?tipo=personal&search=vendedor')->assertOk()->getContent();

        $this->assertStringContainsString('vendedor@ecommerce.com', $html);
        $this->assertStringNotContainsString('carla.mendez@ecommerce.com', $html);
    }

    #[Test]
    public function crear_un_empleado_desde_el_formulario_sigue_funcionando(): void
    {
        $this->comoAdmin();

        $idRolVendedor = \App\Models\Role::where('role_name', 'Vendedor')->value('id_role');

        $this->post('/usuarios', [
            'name'     => 'Tulio Paz',
            'email'    => 'tulio.paz@ecommerce.com',
            'password' => 'clave12345',
            'password_confirmation' => 'clave12345',
            'id_role'  => $idRolVendedor,
        ]);

        $user = User::where('email', 'tulio.paz@ecommerce.com')->firstOrFail();

        $this->post('/employees', [
            'id_user'    => $user->id_user,
            'id_role'    => $idRolVendedor,
            'first_name' => 'Tulio',
            'last_name'  => 'Paz',
            'phone'      => '3764-777000',
        ])->assertRedirect(route('employees.index'));

        $this->assertNotNull(Employee::where('id_user', $user->id_user)->first());
    }
}
