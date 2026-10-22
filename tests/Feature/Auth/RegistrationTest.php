<?php

namespace Tests\Feature\Auth;

use App\Models\Cart;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Registro publico de clientes.
 *
 * El registro tiene que dejar DOS filas: la cuenta (`users`) y la ficha del
 * cliente (`customers`). Antes creaba solo la cuenta, y el cliente nuevo
 * entraba a un sistema donde no podia comprar: /mi-carrito rebotaba a /cart y
 * /cart rebotaba a /mi-carrito, o sea un bucle de redirecciones.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function registrarse(array $datos = []): void
    {
        $this->seed(RoleSeeder::class);

        $this->post('/register', array_merge([
            'first_name'            => 'Test',
            'last_name'             => 'User',
            'email'                 => 'test@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ], $datos));
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $this->registrarse();

        $this->assertAuthenticated();

        // El usuario nuevo queda con el rol Cliente y el nombre completo.
        $this->assertDatabaseHas('users', [
            'email'   => 'test@example.com',
            'name'    => 'Test User',
            'id_role' => Role::where('role_name', 'Cliente')->value('id_role'),
        ]);
    }

    public function test_el_registro_crea_la_ficha_del_cliente(): void
    {
        $this->registrarse();

        $user = User::where('email', 'test@example.com')->firstOrFail();

        /*
         * Sin esta fila el cliente no tiene carrito ni historial: toda la
         * logica de compras se apoya en customers.id_user.
         */
        $this->assertDatabaseHas('customers', [
            'id_user'    => $user->id_user,
            'first_name' => 'Test',
            'last_name'  => 'User',
        ]);
    }

    public function test_el_email_se_guarda_en_minusculas(): void
    {
        $this->registrarse(['email' => 'Pepe@Mail.com']);

        // Si no se normaliza, despues no puede entrar como pepe@mail.com.
        $this->assertDatabaseHas('users', ['email' => 'pepe@mail.com']);
        $this->assertDatabaseMissing('users', ['email' => 'Pepe@Mail.com']);
    }

    public function test_no_se_puede_registrar_un_email_repetido(): void
    {
        $this->seed(RoleSeeder::class);

        // Una cuenta ya existente, creada sin pasar por el formulario: asi
        // el segundo POST se hace como invitado y no rebota por `guest`.
        $user = new User();
        $user->forceFill([
            'name'     => 'Ya Existe',
            'email'    => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        $user->asignarRol('Cliente');

        $this->post('/register', [
            'first_name'            => 'Otro',
            'last_name'             => 'Usuario',
            'email'                 => 'test@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('email');
    }

    public function test_el_cliente_nuevo_puede_abrir_su_carrito(): void
    {
        $this->registrarse();

        $user = User::where('email', 'test@example.com')->firstOrFail();

        $response = $this->get('/mi-carrito');

        $response->assertRedirect();

        /*
         * Esta es la regresion que importa: antes la respuesta era un
         * redirect a /cart, y /cart redirigia a /mi-carrito. El navegador
         * terminaba con ERR_TOO_MANY_REDIRECTS y la cuenta parecia rota.
         */
        $this->assertNotSame(
            route('cart.index', absolute: false),
            $response->headers->get('Location'),
            'El carrito del cliente no puede rebotar al mostrador: es un bucle.'
        );

        // Y el carrito quedo creado, listo para cargarle articulos.
        // OJO: la tabla se llama `cart` (singular), igual que el modelo.
        $this->assertDatabaseHas('cart', [
            'id_customer' => $user->customer->id_customer,
            'status'      => 'active',
        ]);
    }

    public function test_el_cliente_recien_registrado_puede_agregar_al_carrito(): void
    {
        // Catalogo completo: hace falta un articulo con stock.
        $this->seed();

        $this->post('/register', [
            'first_name'            => 'Martin',
            'last_name'             => 'Paz',
            'email'                 => 'martin@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'martin@example.com')->firstOrFail();

        $this->actingAs($user)->post('/mi-carrito/items', [
            'id_article' => 1,
            'quantity'   => 2,
        ])->assertRedirect();

        $carrito = Cart::where('id_customer', $user->customer->id_customer)
            ->where('status', 'active')
            ->first();

        $this->assertNotNull($carrito, 'El cliente nuevo deberia tener carrito propio.');
        $this->assertCount(1, $carrito->items);
        $this->assertSame(2, $carrito->items->first()->quantity);
    }

    public function test_un_cliente_sin_ficha_se_arregla_solo_al_entrar(): void
    {
        $this->seed(RoleSeeder::class);

        /*
         * Un usuario como los que quedaron huerfanos: tiene el rol Cliente
         * pero no la ficha (PEPE, por ejemplo, que se creo antes del
         * arreglo). No hace falta migracion ni script: se arregla al entrar.
         */
        $user = new User();
        $user->forceFill([
            'name'     => 'Pepe Sin Ficha',
            'email'    => 'pepe@example.com',
            'password' => bcrypt('password'),
        ]);
        $user->asignarRol('Cliente');

        $this->assertDatabaseMissing('customers', ['id_user' => $user->id_user]);

        $this->actingAs($user)->get('/dashboard')->assertOk();

        $this->assertDatabaseHas('customers', [
            'id_user'    => $user->id_user,
            'first_name' => 'Pepe',
            'last_name'  => 'Sin Ficha',
        ]);
    }

    public function test_el_login_ofrece_el_boton_para_crear_cuenta(): void
    {
        $html = $this->get('/login')->assertOk()->getContent();

        // El alta publica no se encontraba desde ningun lado: la ruta
        // existia, pero no habia boton ni enlace que llevara a ella.
        $this->assertStringContainsString(route('register'), $html);
        $this->assertStringContainsString('Crear una Cuenta', $html);
    }

    public function test_el_formulario_de_registro_pide_nombre_y_apellido(): void
    {
        $html = $this->get('/register')->assertOk()->getContent();

        $this->assertStringContainsString('name="first_name"', $html);
        $this->assertStringContainsString('name="last_name"', $html);
        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringContainsString('name="password_confirmation"', $html);
    }
}
