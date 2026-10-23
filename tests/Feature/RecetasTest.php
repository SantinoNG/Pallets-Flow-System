<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\RawMaterial;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Proveedores, materias primas y recetas.
 *
 * El taller compra maderas por metro, clavos y tachuelas por bolsa,
 * tornillos por caja, barniz por litro y espuma por plancha. Cada producto
 * tiene su receta con esas cantidades, y el detalle calcula el costo de
 * materiales y para cuantas unidades alcanza el stock.
 */
class RecetasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function admin(): User
    {
        return User::where('email', 'admin@ecommerce.com')->firstOrFail();
    }

    private function cliente(): User
    {
        return User::where('email', 'cliente@gmail.com')->firstOrFail();
    }

    #[Test]
    public function hay_diez_proveedores_sembrados(): void
    {
        $this->assertSame(10, Supplier::count());

        // Los dos originales siguen estando.
        $this->assertDatabaseHas('suppliers', ['company_name' => 'Distribuidora Norte S.A.']);
        $this->assertDatabaseHas('suppliers', ['company_name' => 'Materiales del Sur']);
    }

    #[Test]
    public function las_materias_primas_tienen_unidad_y_costo(): void
    {
        $materiales = RawMaterial::all();

        $this->assertSame(16, $materiales->count());

        foreach ($materiales as $material) {
            $this->assertContains(
                $material->unit,
                RawMaterial::unidadesValidas(),
                "El insumo {$material->description} no tiene una unidad valida."
            );

            $this->assertGreaterThan(0, $material->unit_cost);
            $this->assertNotNull($material->supplier);
        }

        // Hay variedad de unidades, no todo "unidad".
        $this->assertGreaterThanOrEqual(5, $materiales->pluck('unit')->unique()->count());
    }

    #[Test]
    public function cada_articulo_tiene_su_receta_con_cantidades(): void
    {
        $articulos = Article::with('rawMaterials')->get();

        $this->assertCount(8, $articulos);

        foreach ($articulos as $articulo) {
            $this->assertGreaterThanOrEqual(
                5,
                $articulo->rawMaterials->count(),
                "El articulo {$articulo->article_name} tiene muy pocos insumos."
            );

            foreach ($articulo->rawMaterials as $material) {
                $this->assertGreaterThan(
                    0,
                    (float) $material->pivot->quantity_required,
                    "El articulo {$articulo->article_name} tiene un insumo en cero."
                );
            }
        }
    }

    #[Test]
    public function la_unidad_se_muestra_en_singular_y_plural(): void
    {
        $bolsa = RawMaterial::where('description', 'Clavos para pallet (bolsa 1 kg)')->firstOrFail();
        $tela  = RawMaterial::where('description', 'Tela de tapicería')->firstOrFail();

        $this->assertSame('1 bolsa', $bolsa->cantidad(1));
        $this->assertSame('3 bolsas', $bolsa->cantidad(3));
        $this->assertSame('1.50 metros', $tela->cantidad(1.5));
    }

    #[Test]
    public function la_receta_muestra_cantidad_variedad_y_costo(): void
    {
        $articulo = Article::where('article_name', 'Sillón individual de pallet')->firstOrFail();

        $this->actingAs($this->admin())
            ->get(route('articles.show', $articulo->id_article))
            ->assertOk()
            ->assertSee('Materias primas que lleva una unidad')
            ->assertSee('Espuma de alta densidad')
            ->assertSee('3 metros')          // madera 2x4
            ->assertSee('1 bolsa')           // clavos
            ->assertSee('1.50 metros')       // tela
            ->assertSee('1 plancha')         // espuma
            ->assertSee('$24,100.00');       // costo total de materiales
    }

    #[Test]
    public function el_alcance_lo_limita_el_insumo_con_menos_stock(): void
    {
        $articulo = Article::where('article_name', 'Sillón individual de pallet')->firstOrFail();

        // La espuma es la que menos alcanza: 30 planchas, 1 por unidad.
        $this->actingAs($this->admin())
            ->get(route('articles.show', $articulo->id_article))
            ->assertOk()
            ->assertSee('alcanza para')
            ->assertSee('30 unidades')
            ->assertSee('El insumo que limita es');
    }

    #[Test]
    public function el_listado_muestra_cuantos_insumos_lleva_cada_producto(): void
    {
        $this->actingAs($this->admin())
            ->get('/articles')
            ->assertOk()
            ->assertSee('7 insumo(s) en la receta')
            ->assertSee('Receta');
    }

    #[Test]
    public function el_cliente_no_puede_ver_la_receta(): void
    {
        $articulo = Article::firstOrFail();

        $this->actingAs($this->cliente())
            ->get(route('articles.show', $articulo->id_article))
            ->assertForbidden();
    }
}
