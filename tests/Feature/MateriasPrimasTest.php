<?php

namespace Tests\Feature;

use App\Models\RawMaterial;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * El apartado de materias primas.
 *
 * Los insumos se cargaban solo desde el alta de un proveedor. Ahora tienen
 * su propia pantalla, separada de Proveedores: presentación (caja, bolsa,
 * unidad, metro...), stock, mínimo, costo y quién los brinda.
 *
 * El Depósito consulta el listado y el PDF; el alta, la edición y el borrado
 * son de Jefe, Administrador y Encargado.
 */
class MateriasPrimasTest extends TestCase
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

    private function deposito(): User
    {
        return User::where('email', 'deposito@ecommerce.com')->firstOrFail();
    }

    private function proveedor(string $nombre): Supplier
    {
        return Supplier::where('company_name', $nombre)->firstOrFail();
    }

    #[Test]
    public function el_listado_muestra_presentacion_stock_y_proveedor(): void
    {
        $this->actingAs($this->admin())
            ->get('/materias-primas')
            ->assertOk()
            ->assertSee('Depósito de materias primas')
            ->assertSee('Bisagras metálicas')
            ->assertSee('unidades')
            ->assertSee('Tornillos autoperforantes (caja x100)')
            ->assertSee('cajas')
            ->assertSee('Herrajes y Accesorios Centro')
            ->assertSee('Presentaciones')
            ->assertSee('Valor del stock')
            ->assertSee('Nueva materia prima');
    }

    #[Test]
    public function el_deposito_consulta_pero_no_gestiona(): void
    {
        $deposito = $this->deposito();

        $this->actingAs($deposito)
            ->get('/materias-primas')
            ->assertOk()
            ->assertSee('Bisagras metálicas')
            ->assertDontSee('Nueva materia prima');

        $this->actingAs($deposito)
            ->get('/materias-primas/create')
            ->assertForbidden();

        $this->actingAs($deposito)
            ->post('/materias-primas', [
                'description'        => 'Insumo del depósito',
                'unit'               => 'unidad',
                'unit_cost'          => 100,
                'id_supplier'        => $this->proveedor('Herrajes y Accesorios Centro')->id_supplier,
                'remaining_quantity' => 1,
                'minimum_stock'      => 1,
            ])
            ->assertForbidden();
    }

    #[Test]
    public function el_formulario_ofrece_caja_y_unidad(): void
    {
        $this->actingAs($this->admin())
            ->get('/materias-primas/create')
            ->assertOk()
            ->assertSee('Presentación')
            ->assertSee('Cajas')
            ->assertSee('Unidades')
            ->assertSee('Proveedor que la brinda');
    }

    #[Test]
    public function se_crea_una_materia_prima_con_su_stock(): void
    {
        $proveedor = $this->proveedor('Herrajes y Accesorios Centro');

        $this->actingAs($this->admin())
            ->post('/materias-primas', [
                'description'        => 'Bisagras de acero inoxidable',
                'unit'               => 'caja',
                'unit_cost'          => 8500,
                'id_supplier'        => $proveedor->id_supplier,
                'remaining_quantity' => 12,
                'minimum_stock'      => 3,
            ])
            ->assertRedirect('/materias-primas')
            ->assertSessionHas('success');

        $material = RawMaterial::where('description', 'Bisagras de acero inoxidable')->firstOrFail();

        $this->assertSame('caja', $material->unit);
        $this->assertSame(8500.0, (float) $material->unit_cost);
        $this->assertSame($proveedor->id_supplier, $material->id_supplier);

        $this->assertDatabaseHas('raw_material_stock', [
            'id_raw_material'    => $material->id_raw_material,
            'remaining_quantity' => 12,
            'minimum_stock'      => 3,
        ]);
    }

    #[Test]
    public function no_se_acepta_una_presentacion_inventada(): void
    {
        $this->actingAs($this->admin())
            ->post('/materias-primas', [
                'description'        => 'Insumo con presentación rara',
                'unit'               => 'docena',
                'unit_cost'          => 100,
                'id_supplier'        => $this->proveedor('Herrajes y Accesorios Centro')->id_supplier,
                'remaining_quantity' => 1,
                'minimum_stock'      => 1,
            ])
            ->assertSessionHasErrors('unit');

        $this->assertDatabaseMissing('raw_materials', ['description' => 'Insumo con presentación rara']);
    }

    #[Test]
    public function se_edita_la_presentacion_el_costo_y_el_stock(): void
    {
        $material = RawMaterial::where('description', 'Bisagras metálicas')->firstOrFail();
        $proveedor = $this->proveedor('Maderas del Litoral S.A.');

        $this->actingAs($this->admin())
            ->put('/materias-primas/' . $material->id_raw_material, [
                'description'        => 'Bisagras metálicas',
                'unit'               => 'caja',
                'unit_cost'          => 9500,
                'id_supplier'        => $proveedor->id_supplier,
                'remaining_quantity' => 50,
                'minimum_stock'      => 5,
            ])
            ->assertRedirect('/materias-primas')
            ->assertSessionHas('success');

        $material->refresh();

        $this->assertSame('caja', $material->unit);
        $this->assertSame(9500.0, (float) $material->unit_cost);
        $this->assertSame($proveedor->id_supplier, $material->id_supplier);

        $this->assertDatabaseHas('raw_material_stock', [
            'id_raw_material'    => $material->id_raw_material,
            'remaining_quantity' => 50,
            'minimum_stock'      => 5,
        ]);
    }

    #[Test]
    public function no_se_borra_una_materia_prima_que_esta_en_los_planos(): void
    {
        // Las bisagras están en los planos del mueble de TV.
        $material = RawMaterial::where('description', 'Bisagras metálicas')->firstOrFail();

        $this->actingAs($this->admin())
            ->delete('/materias-primas/' . $material->id_raw_material)
            ->assertRedirect('/materias-primas')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('raw_materials', ['id_raw_material' => $material->id_raw_material]);
    }

    #[Test]
    public function se_borra_una_materia_prima_sin_planos(): void
    {
        $material = RawMaterial::create([
            'description' => 'Insumo descartable',
            'unit'        => 'unidad',
            'unit_cost'   => 250,
            'id_supplier' => $this->proveedor('Herrajes y Accesorios Centro')->id_supplier,
        ]);

        $material->stock()->create([
            'remaining_quantity' => 4,
            'minimum_stock'      => 1,
        ]);

        $this->actingAs($this->admin())
            ->delete('/materias-primas/' . $material->id_raw_material)
            ->assertRedirect('/materias-primas')
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('raw_materials', ['id_raw_material' => $material->id_raw_material]);
        $this->assertDatabaseMissing('raw_material_stock', ['id_raw_material' => $material->id_raw_material]);
    }

    #[Test]
    public function el_filtro_por_proveedor_y_por_presentacion(): void
    {
        $clavos = $this->proveedor('Clavos y Tachuelas SRL');

        $this->actingAs($this->admin())
            ->get('/materias-primas?supplier=' . $clavos->id_supplier)
            ->assertOk()
            ->assertSee('Clavos para pallet (bolsa 1 kg)')
            ->assertSee('Tachuelas (bolsa 500 g)')
            ->assertDontSee('Bisagras metálicas');

        $this->actingAs($this->admin())
            ->get('/materias-primas?unit=caja')
            ->assertOk()
            ->assertSee('Tornillos autoperforantes (caja x100)')
            ->assertDontSee('Bisagras metálicas');
    }

    #[Test]
    public function el_pdf_se_genera(): void
    {
        $respuesta = $this->actingAs($this->admin())
            ->get('/materias-primas/pdf');

        $respuesta->assertOk();
        $respuesta->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $respuesta->getContent());
    }
}
