<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Los datos de ejemplo: once clientes con compras repartidas en los ultimos
 * cuatro meses.
 *
 * Con un solo cliente el dashboard no mostraba movimiento: los graficos de
 * ventas por mes y de medios de pago no tenian con que trabajar, y el filtro
 * por cliente no podia comparar nada.
 */
class DatosSemillaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    #[Test]
    public function hay_once_clientes_con_su_usuario_y_su_rol(): void
    {
        $this->assertSame(11, Customer::count());

        $clientes = User::whereHas('roles', fn ($q) => $q->where('name', 'Cliente'))->count();

        $this->assertSame(11, $clientes);
    }

    #[Test]
    public function cada_cliente_tiene_al_menos_una_compra(): void
    {
        foreach (Customer::all() as $customer) {
            $ventas = Sale::where('id_customer', $customer->id_customer)->count();

            $this->assertGreaterThan(
                0,
                $ventas,
                "El cliente {$customer->id_customer} no tiene ninguna compra."
            );
        }
    }

    #[Test]
    public function hay_movimiento_de_compras_en_los_ultimos_cuatro_meses(): void
    {
        $cuatroMeses = Sale::where('created_at', '>=', now()->subMonths(4))->count();

        $this->assertGreaterThan(
            0,
            $cuatroMeses,
            'Tiene que haber compras en los ultimos cuatro meses.'
        );

        // Y tiene que haber movimiento en CADA mes de la ventana, no todo
        // amontonado en un solo mes.
        $meses = Sale::where('created_at', '>=', now()->subMonths(4))
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as mes")
            ->distinct()
            ->count();

        $this->assertGreaterThanOrEqual(3, $meses, 'Las compras tienen que estar repartidas en varios meses.');
    }

    #[Test]
    public function el_dashboard_tiene_datos_para_cada_grafico(): void
    {
        // Ventas por mes
        $porMes = Sale::where('created_at', '>=', now()->subMonths(6))
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as mes")
            ->distinct()
            ->count();
        $this->assertGreaterThanOrEqual(3, $porMes);

        // Medios de pago: tiene que haber más de uno, si no el gráfico de
        // torta no muestra nada.
        $medios = Sale::distinct()->count('payment_method');
        $this->assertGreaterThanOrEqual(2, $medios);

        // Top de productos
        $articulosVendidos = DB::table('cart_items')->distinct()->count('id_article');
        $this->assertGreaterThanOrEqual(3, $articulosVendidos);
    }

    #[Test]
    public function los_clientes_no_se_duplican_al_resembrar(): void
    {
        // CustomerSeeder usa updateOrInsert: correrlo dos veces no puede
        // duplicar clientes ni sus usuarios.
        //
        // OJO: el SaleSeeder NO es idempotente (inserta las ventas con
        // insertGetId), a proposito: el flujo previsto es
        // `migrate:fresh --seed`, que dropea todo y lo vuelve a crear.
        $antes = [
            'clientes' => Customer::count(),
            'usuarios' => DB::table('users')->count(),
        ];

        $this->artisan('db:seed', [
            '--class' => \Database\Seeders\CustomerSeeder::class,
            '--force' => true,
        ]);

        $despues = [
            'clientes' => Customer::count(),
            'usuarios' => DB::table('users')->count(),
        ];

        $this->assertSame($antes['clientes'], $despues['clientes']);
        $this->assertSame($antes['usuarios'], $despues['usuarios']);
    }
}
