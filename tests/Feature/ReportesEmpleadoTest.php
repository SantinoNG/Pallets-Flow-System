<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\RawMaterial;
use App\Models\Role;
use App\Models\User;
use App\Support\ReporteEmpleado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Reporte interno que se genera desde Empleados.
 *
 * No es el listado de empleados: es una hoja que se le manda a una persona y
 * que cambia según su rol. Al Depósito le llega el pedido de reposición, al
 * Vendedor el resumen de ventas y disponibilidad, y al Encargado/Jefe la foto
 * operativa o general.
 */
class ReportesEmpleadoTest extends TestCase
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

    private function empleado(string $email): Employee
    {
        return Employee::whereHas('user', fn ($q) => $q->where('email', $email))
            ->firstOrFail();
    }

    /**
     * Un usuario con rol Encargado, sin ficha de empleado: alcanza para
     * probar el permiso de la ruta de reportes.
     */
    private function encargado(): User
    {
        $rol = Role::where('role_name', 'Encargado')->firstOrFail();

        $user = User::factory()->create(['id_role' => $rol->id_role]);
        $user->asignarRol('Encargado');

        return $user;
    }

    #[Test]
    public function el_reporte_cambia_segun_el_rol(): void
    {
        $this->assertSame('deposito', ReporteEmpleado::tipo('Deposito'));
        $this->assertSame('vendedor', ReporteEmpleado::tipo('Vendedor'));
        $this->assertSame('encargado', ReporteEmpleado::tipo('Encargado'));

        // El Jefe y el Administrador comparten la foto general.
        $this->assertSame('general', ReporteEmpleado::tipo('Jefe'));
        $this->assertSame('general', ReporteEmpleado::tipo('Administrador'));
    }

    #[Test]
    public function el_deposito_recibe_el_pedido_de_reposicion(): void
    {
        $espuma = RawMaterial::where('description', 'Espuma de alta densidad')->firstOrFail();
        $espuma->stock->update([
            'remaining_quantity' => 1,
            'minimum_stock'      => 10,
        ]);

        $datos = ReporteEmpleado::para($this->empleado('deposito@ecommerce.com'));

        $this->assertSame('deposito', $datos['tipo']);
        $this->assertSame(1, $datos['reposicion']['cantidad']);
        $this->assertGreaterThan(0, $datos['reposicion']['total']);

        $fila = collect($datos['insumos'])->first(
            fn ($f) => $f['material']->id_raw_material === $espuma->id_raw_material
        );

        $this->assertTrue($fila['bajo']);
        $this->assertSame(9, $fila['sugerida']);
        $this->assertEqualsWithDelta(
            $fila['sugerida'] * $espuma->unit_cost,
            $fila['subtotal'],
            0.01
        );
    }

    #[Test]
    public function el_reporte_del_deposito_se_descarga_como_pdf(): void
    {
        $deposito = $this->empleado('deposito@ecommerce.com');

        $respuesta = $this->actingAs($this->admin())
            ->get(route('employees.report', $deposito->id_employee));

        $respuesta->assertOk();
        $respuesta->assertHeader('content-type', 'application/pdf');
        $respuesta->assertDownload('reporte-deposito-martin-lopez.pdf');
        $this->assertStringStartsWith('%PDF-', $respuesta->getContent());
    }

    #[Test]
    public function el_vendedor_recibe_ventas_y_disponibilidad(): void
    {
        $datos = ReporteEmpleado::para($this->empleado('vendedor@ecommerce.com'));

        $this->assertSame('vendedor', $datos['tipo']);
        $this->assertGreaterThan(0, $datos['ventas']['cantidad']);
        $this->assertGreaterThan(0, $datos['ventas']['total']);
        $this->assertNotEmpty($datos['masVendidos']);

        // El catálogo completo, con el estado de cada artículo.
        $this->assertCount(8, $datos['articulos']);
    }

    #[Test]
    public function el_encargado_recibe_el_resumen_operativo(): void
    {
        $rol = Role::where('role_name', 'Encargado')->firstOrFail();
        $user = User::factory()->create(['id_role' => $rol->id_role]);

        $empleado = Employee::create([
            'id_user'    => $user->id_user,
            'id_role'    => $rol->id_role,
            'first_name' => 'Encargado',
            'last_name'  => 'de Prueba',
            'phone'      => '11 5555-0000',
        ]);

        $datos = ReporteEmpleado::para($empleado->load('role'));

        $this->assertSame('encargado', $datos['tipo']);
        $this->assertArrayHasKey('insumos', $datos);
        $this->assertArrayHasKey('ventas', $datos);
    }

    #[Test]
    public function el_jefe_recibe_el_resumen_general(): void
    {
        $datos = ReporteEmpleado::para($this->empleado('admin@ecommerce.com'));

        $this->assertSame('general', $datos['tipo']);
        $this->assertSame(8, $datos['totales']['articulos']);
        $this->assertSame(16, $datos['totales']['insumos']);
        $this->assertSame(10, $datos['totales']['proveedores']);
        $this->assertSame(3, $datos['totales']['empleados']);
        $this->assertGreaterThan(0, $datos['totales']['facturado']);
        $this->assertNotEmpty($datos['porRol']);
    }

    #[Test]
    public function los_tres_reportes_se_generan(): void
    {
        foreach (['deposito@ecommerce.com', 'vendedor@ecommerce.com', 'admin@ecommerce.com'] as $email) {
            $empleado = $this->empleado($email);

            $respuesta = $this->actingAs($this->admin())
                ->get(route('employees.report', $empleado->id_employee));

            $respuesta->assertOk();
            $respuesta->assertHeader('content-type', 'application/pdf');
            $this->assertStringStartsWith('%PDF-', $respuesta->getContent());
        }
    }

    #[Test]
    public function el_cliente_y_el_vendedor_no_pueden_descargar_reportes(): void
    {
        $deposito = $this->empleado('deposito@ecommerce.com');

        $cliente = User::where('email', 'cliente@gmail.com')->firstOrFail();
        $this->actingAs($cliente)
            ->get(route('employees.report', $deposito->id_employee))
            ->assertForbidden();

        $vendedor = User::where('email', 'vendedor@ecommerce.com')->firstOrFail();
        $this->actingAs($vendedor)
            ->get(route('employees.report', $deposito->id_employee))
            ->assertForbidden();
    }

    #[Test]
    public function el_listado_muestra_el_boton_reporte(): void
    {
        $deposito = $this->empleado('deposito@ecommerce.com');

        $this->actingAs($this->admin())
            ->get('/employees')
            ->assertOk()
            ->assertSee('Reporte')
            ->assertSee(route('employees.report', $deposito->id_employee), false);
    }

    #[Test]
    public function el_encargado_no_puede_bajar_el_reporte_general_de_un_jefe(): void
    {
        $admin = $this->empleado('admin@ecommerce.com');

        $this->actingAs($this->encargado())
            ->get(route('employees.report', $admin->id_employee))
            ->assertForbidden();
    }

    #[Test]
    public function el_encargado_si_puede_bajar_el_reporte_de_un_vendedor(): void
    {
        $vendedor = $this->empleado('vendedor@ecommerce.com');

        $this->actingAs($this->encargado())
            ->get(route('employees.report', $vendedor->id_employee))
            ->assertOk();
    }

    #[Test]
    public function el_listado_del_encargado_no_ofrece_el_reporte_general(): void
    {
        $admin = $this->empleado('admin@ecommerce.com');

        $this->actingAs($this->encargado())
            ->get('/employees')
            ->assertOk()
            ->assertDontSee(route('employees.report', $admin->id_employee), false);
    }
}
