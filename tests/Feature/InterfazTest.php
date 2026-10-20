<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pruebas de la interfaz: paleta industrial y modo oscuro.
 *
 * Estas pruebas fijan el comportamiento para que no retroceda: si alguien
 * agrega una vista nueva con clases claras sin su variante `dark:`, o
 * cambia el acento de la marca a otro color, las pruebas avisan.
 */
class InterfazTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Clases que representan superficies claras y su equivalente oscuro.
     * Si aparece la clara sin la oscura, el modo oscuro se rompe.
     */
    private const PARES = [
        'bg-white'        => 'dark:bg-zinc-900',
        'bg-gray-50'      => 'dark:bg-zinc-950',
        'bg-gray-100'     => 'dark:bg-zinc-950',
        'text-gray-900'   => 'dark:text-zinc-100',
        'text-gray-800'   => 'dark:text-zinc-100',
        'text-gray-700'   => 'dark:text-zinc-200',
        'text-gray-600'   => 'dark:text-zinc-300',
        'text-gray-500'   => 'dark:text-zinc-400',
        'border-gray-200' => 'dark:border-zinc-800',
        'border-gray-300' => 'dark:border-zinc-700',
    ];

    /**
     * Paginas que se revisan en modo oscuro.
     */
    private const PAGINAS = [
        '/dashboard',
        '/articles',
        '/articles/create',
        '/suppliers',
        '/employees',
        '/roles',
        '/roles/create',
        '/cart',
        '/sales',
        '/profile',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    /**
     * Se autentica como administrador para poder ver todas las paginas.
     */
    private function comoAdmin(): User
    {
        $admin = User::where('email', 'admin@ecommerce.com')->first();
        $admin->asignarRol('Administrador');

        $this->actingAs($admin);

        return $admin;
    }

    #[Test]
    public function el_script_del_tema_se_carga_antes_del_contenido(): void
    {
        $respuesta = $this->get('/login');

        $respuesta->assertOk();

        $html = $respuesta->getContent();

        // El script decide el tema ANTES de pintar: sin esto, al recargar
        // en modo oscuro se ve un destello blanco.
        $this->assertStringContainsString("localStorage.getItem('tema')", $html);
        $this->assertStringContainsString("classList.add('dark')", $html);

        // Y tiene que estar en el <head>, no al final del body.
        $posicionScript = strpos($html, "localStorage.getItem('tema')");
        $posicionBody = strpos($html, '<body');

        $this->assertLessThan($posicionBody, $posicionScript);
    }

    #[Test]
    public function el_interruptor_de_modo_oscuro_esta_en_el_login_y_en_el_panel(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Activar modo oscuro', escape: false);

        $this->comoAdmin();

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Activar modo oscuro', escape: false);
    }

    #[Test]
    public function ninguna_pagina_deja_superficies_sin_variante_oscura(): void
    {
        $this->comoAdmin();

        foreach (self::PAGINAS as $ruta) {
            $html = $this->get($ruta)->assertOk()->getContent();

            preg_match_all('/class="([^"]*)"/', $html, $coincidencias);

            foreach ($coincidencias[1] as $bloque) {
                $clases = preg_split('/\s+/', trim($bloque));

                foreach (self::PARES as $clara => $oscura) {
                    if (! in_array($clara, $clases, true)) {
                        continue;
                    }

                    $this->assertContains(
                        $oscura,
                        $clases,
                        "En {$ruta} la clase '{$clara}' necesita su variante '{$oscura}'."
                    );
                }
            }
        }
    }

    #[Test]
    public function la_paleta_antigua_no_sobrevive_en_las_vistas(): void
    {
        $this->comoAdmin();

        // Se busca como clase de Tailwind, no como substring: "translate"
        // contiene "slate-" y daria un falso positivo.
        $paletaVieja = '/(?<![a-z0-9-])(indigo|blue|amber|slate)-\d{2,3}(?![a-z0-9-])/';

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(base_path('resources/views'))
        );

        foreach ($iterator as $archivo) {
            if (! $archivo->isFile() || $archivo->getExtension() !== 'php') {
                continue;
            }

            $contenido = file_get_contents($archivo->getPathname());

            $this->assertSame(
                0,
                preg_match_all($paletaVieja, $contenido, $encontradas),
                'La paleta vieja quedo en ' . $archivo->getFilename() . ': '
                    . implode(', ', array_unique($encontradas[0]))
            );
        }
    }

    #[Test]
    public function el_acento_de_la_marca_es_el_naranja_de_seguridad(): void
    {
        $this->comoAdmin();

        // El color de marca es un unico naranja de seguridad (`hazard`).
        $html = $this->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('bg-hazard-', $html);
        $this->assertStringNotContainsString('bg-amber-', $html);
    }
}
