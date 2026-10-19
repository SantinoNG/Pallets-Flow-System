<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Article;
use Database\Seeders\RoleSeeder;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        $article = Article::factory()->create([
            'unit_price' => 12000.00,
        ]);

        $response = $this->actingAs($user)->post(route('cart.add'), [
            'id_article' => $article->id_article,
            'quantity'   => 2,
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('carts', [
            'id_user' => $user->id_user ?? $user->id,
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