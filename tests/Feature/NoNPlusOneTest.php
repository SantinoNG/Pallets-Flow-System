<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Guarda contra el N+1.
 *
 * El listado de ventas tenía un N+1: la vista llama $venta->total() y
 * $venta->unidades(), que usan la relación items() (hasManyThrough), que no
 * estaba en el eager load. Eloquent la resolvía con una consulta por fila:
 * 25 consultas en total, 12 de ellas idénticas.
 *
 * Con 'items' en el eager load bajó a 13 consultas. Este test falla si
 * vuelve a dispararse, porque el número de consultas crecería con cada
 * venta que se agregue.
 */
class NoNPlusOneTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Máximo tolerable de consultas para el listado de ventas.
     *
     * Con N+1 eran 25 con 6 ventas (y crecería: 1 consulta más por venta).
     * Ahora son 13 con cualquier cantidad.
     */
    private const MAXIMO = 18;

    #[Test]
    public function el_listado_de_ventas_no_hace_una_consulta_por_fila()
    {
        $this->seed();

        $admin = User::where('email', 'admin@ecommerce.com')->first();
        $this->actingAs($admin);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get('/sales')->assertStatus(200);

        $consultas = count(DB::getQueryLog());

        // Además de contar, se comprueba que no haya la misma consulta
        // repetida muchas veces (la firma del N+1).
        $formas = [];
        foreach (DB::getQueryLog() as $q) {
            $forma = preg_replace('/\b\d+\b/', '?', $q['query']);
            $formas[$forma] = ($formas[$forma] ?? 0) + 1;
        }

        $maxRepeticiones = max($formas);

        $this->assertLessThanOrEqual(
            self::MAXIMO,
            $consultas,
            "El listado de ventas hace $consultas consultas (máximo tolerado: " . self::MAXIMO . "). "
                . "Suele indicar que falta un eager load."
        );

        $this->assertLessThanOrEqual(
            2,
            $maxRepeticiones,
            "Una consulta se repite $maxRepeticiones veces en el listado de ventas: "
                . "es un N+1. Revisar el eager load de SaleController@index."
        );
    }
}
