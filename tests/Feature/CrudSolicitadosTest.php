<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ProductStock;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class CrudSolicitadosTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $role = Role::factory()->create([
            'role_name' => 'Administrador',
            'description' => 'Administrador de pruebas',
        ]);

        $user = User::factory()->create([
            'id_role' => $role->id_role,
            'password' => Hash::make('password'),
        ]);

        if (class_exists(SpatieRole::class)) {
            try {
                SpatieRole::findOrCreate('Administrador');
                $user->assignRole('Administrador');
            } catch (\Throwable $e) {
            }
        }

        return $user;
    }

    public function test_alta_articulo(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('articles.store'), [
            'article_name'       => 'Pallet Prueba',
            'description'        => 'Artículo creado por test',
            'unit_price'         => 1500.50,
            'remaining_quantity' => 100,
            'minimum_stock'      => 10,
        ]);

        if (session()->has('errors')) {
            $this->fail('Erroros de validación: ' . json_encode(session('errors')->getMessages()));
        }

        $response->assertStatus(302);
        $this->assertDatabaseHas('articles', [
            'article_name' => 'Pallet Prueba',
        ]);
        $this->assertDatabaseHas('product_stock', [
            'remaining_quantity' => 100,
            'minimum_stock'      => 10,
        ]);
    }

    public function test_baja_articulo(): void
    {
        $admin = $this->admin();

        $article = Article::factory()->create([
            'article_name' => 'Pallet a Eliminar',
        ]);

        ProductStock::create([
            'id_article'         => $article->id_article,
            'remaining_quantity' => 10,
            'minimum_stock'      => 5,
        ]);

        $this->assertDatabaseHas('articles', ['id_article' => $article->id_article]);

        $response = $this->actingAs($admin)->delete(route('articles.destroy', $article));

        $response->assertStatus(302);
        $this->assertDatabaseMissing('articles', ['id_article' => $article->id_article]);
    }

    public function test_registrar_usuario(): void
    {
        $admin = $this->admin();
        $role  = Role::factory()->create(['role_name' => 'Vendedor']);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name'                  => 'Juan Perez',
            'email'                 => 'juan.perez.test@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
            'id_role'               => $role->id_role,
        ]);

        if (session()->has('errors')) {
            $this->fail('Errores de validación: ' . json_encode(session('errors')->getMessages()));
        }

        $response->assertStatus(302);
        $this->assertDatabaseHas('users', [
            'email' => 'juan.perez.test@example.com',
            'name'  => 'Juan Perez',
        ]);
    }

    public function test_registrar_empleado(): void
    {
        $admin = $this->admin();
        $role  = Role::factory()->create(['role_name' => 'Vendedor']);
        $user  = User::factory()->create(['id_role' => $role->id_role]);

        $response = $this->actingAs($admin)->post(route('employees.store'), [
            'id_user'    => $user->id_user,
            'id_role'    => $role->id_role,
            'first_name' => 'Carlos',
            'last_name'  => 'Lopez',
            'phone'      => '3510000000',
        ]);

        if (session()->has('errors')) {
            $this->fail('Errores de validación: ' . json_encode(session('errors')->getMessages()));
        }

        $response->assertStatus(302);
        $this->assertDatabaseHas('employees', [
            'first_name' => 'Carlos',
            'last_name'  => 'Lopez',
        ]);
    }

    public function test_alta_proveedor(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('suppliers.store'), [
            'company_name'   => 'Proveedor Test SRL',
            'supplier_email' => 'proveedor.test@example.com',
            'supplier_phone' => '3511111111',
            'materials'      => ['Madera'],
            'units'          => ['unidad'],
        ]);

        if (session()->has('errors')) {
            $this->fail('Errores de validación: ' . json_encode(session('errors')->getMessages()));
        }

        $response->assertStatus(302);
        $this->assertDatabaseHas('suppliers', [
            'company_name'   => 'Proveedor Test SRL',
            'supplier_email' => 'proveedor.test@example.com',
        ]);
        $this->assertDatabaseHas('raw_materials', [
            'description' => 'Madera',
        ]);
    }
}
