<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Las descargas y exportaciones en PDF.
 *
 * Tres cosas se rompen sin que se note mirando la pagina:
 *
 * 1. Un link de descarga con `target="_blank"` abre una pestania VACIA y
 *    baja el archivo en silencio. El usuario ve "no pasa nada" y dice que la
 *    descarga no funciona.
 *
 * 2. dompdf con `enable_font_subsetting => false` embebe la fuente
 *    COMPLETA (la DejaVu Sans pesa ~850 KB) y cada comprobante se va a
 *    casi un megabyte.
 *
 * 3. Una imagen PNG con canal alfa se embebe doble (imagen + mascara), asi
 *    que para el PDF conviene un JPEG.
 */
class ExportacionesTest extends TestCase
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

    /**
     * Las paginas que tienen boton de exportacion, con la ruta de la pagina
     * y la URL del PDF que enlaza.
     *
     * OJO: el nombre de la ruta es 'articles.pdf' pero la URL es
     * '/articles/pdf'. Para el link hay que buscar por URL.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    private function exportaciones(): array
    {
        return [
            '/articles'  => ['articles/pdf', 'articulos'],
            '/employees' => ['employees/pdf', 'empleados'],
            '/suppliers' => ['proveedores/pdf', 'proveedores'],
            '/usuarios'  => ['usuarios/pdf', 'usuarios'],
        ];
    }

    #[Test]
    public function los_links_de_exportacion_no_abren_una_pestania_vacia(): void
    {
        foreach ($this->exportaciones() as $ruta => $datos) {
            [$rutaPdf, $nombre] = $datos;

            $html = $this->get($ruta)->assertOk()->getContent();

            // El link al PDF de ESA pagina.
            preg_match('~<a\b[^>]*href="[^"]*' . preg_quote($rutaPdf, '~') . '[^"]*"[^>]*>~i', $html, $m);

            $this->assertNotEmpty($m, "No encontre el link al PDF de {$ruta}.");
            $this->assertStringNotContainsString(
                'target="_blank"',
                $m[0],
                "El link de exportacion de {$ruta} no debe tener target=\"_blank\": "
                    . 'abre una pestania vacia y el usuario cree que no funciono.'
            );
        }
    }

    #[Test]
    public function los_reportes_en_pdf_se_generan(): void
    {
        foreach ($this->exportaciones() as $datos) {
            [$rutaPdf, $nombre] = $datos;

            $respuesta = $this->get("/{$rutaPdf}");

            $respuesta->assertOk();
            $respuesta->assertHeader('content-type', 'application/pdf');

            $cuerpo = $respuesta->getContent();
            $this->assertStringStartsWith('%PDF-', $cuerpo, "El PDF de {$nombre} no es un PDF valido.");
        }
    }

    #[Test]
    public function el_comprobante_de_venta_es_un_pdf_valido_y_no_pesado(): void
    {
        $sale = Sale::with('cart.items.article', 'customer.user', 'employee', 'receipt')->firstOrFail();

        $respuesta = $this->get('/sales/' . $sale->id_sale . '/pdf');

        $respuesta->assertOk();
        $respuesta->assertHeader('content-type', 'application/pdf');

        $cuerpo = $respuesta->getContent();

        $this->assertStringStartsWith('%PDF-', $cuerpo);
        $this->assertStringContainsString('/FontFile', $cuerpo, 'El PDF tiene que tener la fuente embebida.');

        // Con el subsetting activo y las imagenes en JPEG, un comprobante
        // ronda los 40 KB. Si vuelve a 900 KB, se esta embebiendo la fuente entera.
        $this->assertLessThan(
            150 * 1024,
            strlen($cuerpo),
            'El comprobante pesa mucho: revisá enable_font_subsetting en config/dompdf.php '
                . 'y que las imagenes del PDF sean JPEG.'
        );
    }

    #[Test]
    public function el_comprobante_lleva_la_marca_de_agua_difuminada(): void
    {
        $sale = Sale::with('cart.items.article', 'customer.user', 'employee', 'receipt')->firstOrFail();

        $cuerpo = $this->get('/sales/' . $sale->id_sale . '/pdf')->getContent();

        // La marca de agua se dibuja con una opacidad baja (el /CA del
        // ExtGState). El logo de la cabecera va al 1.
        $this->assertMatchesRegularExpression(
            '~/CA\s+0\.0[0-9]+~',
            $cuerpo,
            'La marca de agua tiene que ir difuminada (/CA bajo en el ExtGState).'
        );
    }

    #[Test]
    public function la_configuracion_de_dompdf_hace_subset_de_las_fuentes(): void
    {
        // Sin esto, cada PDF embebe la fuente entera (~850 KB).
        //
        // OJO: en dompdf 3.x las opciones viven dentro de 'options' (el
        // service provider mueve ahi las claves de nivel superior), asi que
        // la ruta es dompdf.options.enable_font_subsetting.
        $subsetting = config('dompdf.options.enable_font_subsetting')
            ?? config('dompdf.enable_font_subsetting');

        $this->assertTrue(
            $subsetting,
            'config/dompdf.php tiene que tener enable_font_subsetting en true.'
        );
    }

    #[Test]
    public function los_articulos_con_imagen_siguen_siendo_validos(): void
    {
        // El catalogo muestra la foto del articulo: si el archivo se
        // perdio, la pagina muestra el placeholder en vez de la foto.
        $articulo = Article::whereNotNull('image')->first();

        $this->assertNotNull($articulo, 'Los articulos deberian tener imagen.');
        $this->assertTrue(
            Storage::disk('public')->exists($articulo->image),
            "Falta el archivo de imagen del articulo: {$articulo->image}"
        );
    }
}
