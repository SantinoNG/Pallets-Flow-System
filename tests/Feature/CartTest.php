<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\ProductStock;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Carga los roles iniciales necesarios para UserFactory
        $this->seed(RoleSeeder::class);
    }

    #[Test]
    public function un_usuario_autenticado_puede_agregar_un_articulo_al_carrito()
    {
        $user = User::factory()->create();

        // El carrito cuelga de un customer, no del user directo:
        // la tabla `cart` tiene id_customer, no id_user.
        $customer = Customer::create([
            'id_user'     => $user->id_user,
            'first_name' => 'Ana',
            'last_name'  => 'Torres',
        ]);

        $article = Article::factory()->create([
            'unit_price' => 12000.00,
        ]);

        // addItem() valida el stock: sin una fila en product_stock devuelve
        // "Stock insuficiente" y no agrega el item.
        ProductStock::create([
            'id_article'         => $article->id_article,
            'remaining_quantity' => 50,
            'minimum_stock'      => 5,
        ]);

        // Paso 1: crear (o recuperar) el carrito activo del cliente.
        // La ruta se llama 'cart.create' (POST /cart), no 'cart.add'.
        $response = $this->actingAs($user)->post(route('cart.create'), [
            'id_customer' => $customer->id_customer,
        ]);

        $response->assertStatus(302);

        $cart = Cart::where('id_customer', $customer->id_customer)
                    ->where('status', 'active')
                    ->first();

        $this->assertNotNull($cart, 'El carrito deberia haberse creado para el cliente.');

        // Paso 2: agregar el articulo al carrito.
        // La ruta se llama 'cart.addItem' (POST /cart/{cart}/items).
        $response = $this->actingAs($user)->post(route('cart.addItem', $cart->id_cart), [
            'id_article' => $article->id_article,
            'quantity'   => 2,
        ]);

        $response->assertStatus(302);

        // La tabla se llama `cart_items` (en singular y plural no: `cart_items`).
        $this->assertDatabaseHas('cart_items', [
            'id_cart'    => $cart->id_cart,
            'id_article' => $article->id_article,
            'quantity'   => 2,
        ]);
    }

    #[Test]
    public function un_usuario_puede_ver_el_contenido_de_su_carrito()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('cart.index'));

        $response->assertStatus(200);
    }
}
