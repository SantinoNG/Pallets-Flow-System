<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\RawMaterial;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Borrar articulos y proveedores.
 *
 * Los dos danaban HTTP 500 por claves foraneas:
 *
 * - Un articulo que ya se vendio tiene items en carritos (cart_items.id_article).
 *   Borrarlo rompia el historial de esa venta.
 * - Un proveedor tiene materias primas (raw_materials.id_supplier), y esas
 *   pueden estar en la receta de un articulo (article_materials).
 *
 * Ahora avisan con un mensaje claro en vez de reventar.
 */
class BorradosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $admin = User::where('email', 'admin@ecommerce.com')->firstOrFail();
        $admin->asignarRol('Administrador');
        $this->actingAs($admin);
    }

    #[Test]
    public function un_articulo_sin_ventas_se_borra_bien(): void
    {
        $articulo = Article::create([
            'article_name'  => 'Articulo descartable',
            'description'   => 'Prueba',
            'unit_price'    => 1000,
        ]);

        $articulo->stock()->create([
            'remaining_quantity' => 5,
            'minimum_stock'      => 1,
        ]);

        $this->delete('/articles/' . $articulo->id_article)
            ->assertRedirect('/articles')
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('articles', ['id_article' => $articulo->id_article]);
    }

    #[Test]
    public function un_articulo_vendido_no_se_borra_y_avisa(): void
    {
        // Un artículo dentro de un carrito que tiene venta
        $customer = Customer::firstOrFail();
        $articulo = Article::firstOrFail();

        $cart = Cart::create([
            'id_customer' => $customer->id_customer,
            'status'      => 'completed',
        ]);

        CartItem::create([
            'id_cart'    => $cart->id_cart,
            'id_article' => $articulo->id_article,
            'quantity'   => 1,
            'unit_price' => $articulo->unit_price,
        ]);

        Sale::create([
            'id_cart'        => $cart->id_cart,
            'id_customer'    => $customer->id_customer,
            'id_employee'    => null,
            'payment_method' => 'Efectivo',
        ]);

        $this->delete('/articles/' . $articulo->id_article)
            ->assertRedirect('/articles')
            ->assertSessionHas('error');

        // El artículo sigue ahí: es el historial de la venta
        $this->assertDatabaseHas('articles', ['id_article' => $articulo->id_article]);
    }

    #[Test]
    public function un_proveedor_cuyas_materias_no_estan_en_recetas_se_borra(): void
    {
        $supplier = Supplier::create([
            'company_name'   => 'Proveedor descartable',
            'supplier_email' => 'descartable@proveedor.com',
            'supplier_phone' => '3764-000000',
        ]);

        $materia = RawMaterial::create([
            'description'  => 'Material descartable',
            'id_supplier'  => $supplier->id_supplier,
        ]);

        $materia->stock()->create([
            'remaining_quantity' => 10,
            'minimum_stock'      => 1,
        ]);

        $this->delete('/suppliers/' . $supplier->id_supplier)
            ->assertRedirect('/suppliers')
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('suppliers', ['id_supplier' => $supplier->id_supplier]);
        $this->assertDatabaseMissing('raw_materials', ['id_raw_material' => $materia->id_raw_material]);
    }

    #[Test]
    public function un_proveedor_cuyas_materias_estan_en_recetas_no_se_borra_y_avisa(): void
    {
        // El proveedor de "Tabla de pallet reciclada", que está en la receta
        // del sillón individual.
        $materia = RawMaterial::where('description', 'Tabla de pallet reciclada')->firstOrFail();
        $supplier = $materia->supplier;

        $this->delete('/suppliers/' . $supplier->id_supplier)
            ->assertRedirect('/suppliers')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('suppliers', ['id_supplier' => $supplier->id_supplier]);
    }

    #[Test]
    public function un_rol_que_alguien_tiene_no_se_borra_y_avisa(): void
    {
        $rol = \App\Models\Role::where('role_name', 'Administrador')->firstOrFail();

        $this->delete('/roles/' . $rol->id_role)
            ->assertRedirect('/roles')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['id_role' => $rol->id_role]);
    }
}
