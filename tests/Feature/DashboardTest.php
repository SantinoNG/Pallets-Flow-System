<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * El dashboard y sus gráficos.
 *
 * Los <canvas> de Chart.js están dentro de un `wire:ignore`: si no,
 * Livewire los reemplaza al cambiar el periodo y quedan dos gráficos
 * superpuestos. Pero si el `wire:ignore` cubre el div que ENVUELVE al
 * canvas, los atributos `data-*` tampoco se actualizan, y el gráfico se
 * queda con los datos del primer render: el filtro de periodo cambiaba el
 * KPI y el gráfico no se enteraba.
 *
 * La solución: el `wire:ignore` va solo sobre el <canvas> y los datos
 * viven en un <span> aparte, que es lo que Livewire sí actualiza.
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function comoAdmin(): void
    {
        $admin = User::where('email', 'admin@ecommerce.com')->firstOrFail();
        $admin->asignarRol('Administrador');

        $this->actingAs($admin);
    }

    #[Test]
    public function el_dashboard_muestra_los_cuatro_graficos(): void
    {
        $this->comoAdmin();
        $html = $this->get('/dashboard')->assertOk()->getContent();

        foreach (['salesChart', 'stockChart', 'topProductsChart', 'paymentChart'] as $id) {
            $this->assertStringContainsString('id="' . $id . '"', $html);
            $this->assertStringContainsString('id="datos-' . $id . '"', $html);
        }
    }

    #[Test]
    public function los_datos_de_los_graficos_no_estan_dentro_del_wire_ignore(): void
    {
        $this->comoAdmin();
        $html = $this->get('/dashboard')->assertOk()->getContent();

        // El canvas NO debe llevar los datos: si los lleva, no se actualizan
        // al cambiar el periodo.
        foreach (['salesChart', 'stockChart', 'topProductsChart', 'paymentChart'] as $id) {
            $this->assertMatchesRegularExpression(
                '/<canvas id="' . $id . '"[^>]*>/',
                $html,
                "El canvas {$id} tiene que existir."
            );

            $this->assertSame(
                0,
                preg_match('/<canvas id="' . $id . '"[^>]*data-labels/', $html),
                "El canvas {$id} no debe llevar los datos: van en el span datos-{$id}, "
                    . 'que es lo que Livewire actualiza.'
            );
        }
    }

    #[Test]
    public function cada_periodo_muestra_datos_distintos(): void
    {
        // El componente es del personal: se entra como Administrador para
        // que el chequeo de rol no lo bloquee.
        $this->comoAdmin();

        $componente = new \App\Livewire\Dashboard();

        // 30 días
        $componente->periodo = '30_dias';
        $datos = $componente->render()->getData();
        $meses30 = count($datos['ventasMes']['labels']);

        // 90 días
        $componente = new \App\Livewire\Dashboard();
        $componente->periodo = '90_dias';
        $datos = $componente->render()->getData();
        $meses90 = count($datos['ventasMes']['labels']);

        $this->assertGreaterThan(0, $meses30);
        $this->assertGreaterThan($meses30, $meses90, 'Con 90 días tiene que haber más meses que con 30.');
    }

    #[Test]
    public function el_filtro_de_periodo_ofrece_las_opciones(): void
    {
        $this->comoAdmin();
        $html = $this->get('/dashboard')->assertOk()->getContent();

        foreach (['30_dias', '90_dias', '6_meses', 'anio', 'todo'] as $periodo) {
            $this->assertStringContainsString('value="' . $periodo . '"', $html);
        }
    }
}
