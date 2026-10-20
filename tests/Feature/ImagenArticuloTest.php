<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pruebas de la foto de los artículos.
 *
 * La columna `image` guarda la ruta relativa dentro del disco 'public'
 * (por ejemplo "articles/sillon.jpg"), no la URL completa.
 */
class ImagenArticuloTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        // El disco 'public' se reemplaza por uno temporal: las pruebas no
        // tocan los archivos de verdad.
        Storage::fake('public');
    }

    /**
     * Usuario con rol que puede entrar al módulo de artículos.
     */
    private function comoAdministrador(): User
    {
        $user = User::factory()->create();
        $user->asignarRol('Administrador');

        $this->actingAs($user);

        return $user;
    }

    /**
     * Datos mínimos y válidos para crear un artículo.
     */
    private function datos(array $extra = []): array
    {
        return array_merge([
            'article_name'       => 'Sillón de prueba',
            'description'        => 'Descripción de prueba',
            'unit_price'         => 15000,
            'remaining_quantity' => 5,
            'minimum_stock'      => 2,
        ], $extra);
    }

    #[Test]
    public function se_puede_subir_una_imagen_al_crear_un_articulo(): void
    {
        $this->comoAdministrador();

        $response = $this->post('/articles', $this->datos([
            'image' => UploadedFile::fake()->image('sillon.jpg', 800, 800),
        ]));

        $response->assertRedirect('/articles');

        $articulo = Article::where('article_name', 'Sillón de prueba')->firstOrFail();

        $this->assertNotNull($articulo->image);
        $this->assertStringStartsWith('articles/', $articulo->image);

        // El archivo quedó en el disco.
        Storage::disk('public')->assertExists($articulo->image);
    }

    #[Test]
    public function un_articulo_puede_crearse_sin_imagen(): void
    {
        $this->comoAdministrador();

        $this->post('/articles', $this->datos())->assertRedirect('/articles');

        $articulo = Article::where('article_name', 'Sillón de prueba')->firstOrFail();

        $this->assertNull($articulo->image);
        $this->assertFalse($articulo->tieneImagen());
        $this->assertNull($articulo->imagen_url);
    }

    #[Test]
    public function al_cambiar_la_imagen_se_borra_la_vieja_del_disco(): void
    {
        $this->comoAdministrador();

        $this->post('/articles', $this->datos([
            'image' => UploadedFile::fake()->image('vieja.jpg', 800, 800),
        ]));

        $articulo = Article::where('article_name', 'Sillón de prueba')->firstOrFail();
        $rutaVieja = $articulo->image;

        Storage::disk('public')->assertExists($rutaVieja);

        $this->put('/articles/' . $articulo->id_article, $this->datos([
            'image' => UploadedFile::fake()->image('nueva.jpg', 800, 800),
        ]))->assertRedirect('/articles');

        $articulo->refresh();

        $this->assertNotSame($rutaVieja, $articulo->image);
        Storage::disk('public')->assertExists($articulo->image);

        // La vieja ya no está.
        Storage::disk('public')->assertMissing($rutaVieja);
    }

    #[Test]
    public function al_actualizar_sin_subir_imagen_se_conserva_la_que_ya_tenia(): void
    {
        $this->comoAdministrador();

        $this->post('/articles', $this->datos([
            'image' => UploadedFile::fake()->image('sillon.jpg', 800, 800),
        ]));

        $articulo = Article::where('article_name', 'Sillón de prueba')->firstOrFail();
        $ruta = $articulo->image;

        // Se actualiza el precio, sin tocar la imagen.
        $this->put('/articles/' . $articulo->id_article, $this->datos([
            'unit_price' => 20000,
        ]))->assertRedirect('/articles');

        $articulo->refresh();

        $this->assertSame($ruta, $articulo->image);
        Storage::disk('public')->assertExists($ruta);
    }

    #[Test]
    public function al_borrar_el_articulo_se_borra_su_imagen(): void
    {
        $this->comoAdministrador();

        $this->post('/articles', $this->datos([
            'image' => UploadedFile::fake()->image('sillon.jpg', 800, 800),
        ]));

        $articulo = Article::where('article_name', 'Sillón de prueba')->firstOrFail();
        $ruta = $articulo->image;

        $this->delete('/articles/' . $articulo->id_article)
             ->assertRedirect('/articles');

        Storage::disk('public')->assertMissing($ruta);
    }

    #[Test]
    public function se_rechaza_un_archivo_que_no_es_una_imagen(): void
    {
        $this->comoAdministrador();

        $this->post('/articles', $this->datos([
            'image' => UploadedFile::fake()->create('malicioso.php', 10, 'application/x-php'),
        ]))->assertSessionHasErrors('image');

        $this->assertNull(
            Article::where('article_name', 'Sillón de prueba')->first()?->image
        );
    }

    #[Test]
    public function el_listado_muestra_la_imagen_del_articulo(): void
    {
        $this->comoAdministrador();

        $this->post('/articles', $this->datos([
            'image' => UploadedFile::fake()->image('sillon.jpg', 800, 800),
        ]));

        $articulo = Article::where('article_name', 'Sillón de prueba')->firstOrFail();

        $html = $this->get('/articles')->assertOk()->getContent();

        $this->assertStringContainsString($articulo->imagen_url, $html);
    }

    #[Test]
    public function sin_imagen_la_vista_dibuja_un_placeholder(): void
    {
        $this->comoAdministrador();

        $this->post('/articles', $this->datos());

        $html = $this->get('/articles')->assertOk()->getContent();

        // El placeholder es un SVG inline con la inicial del producto.
        $this->assertStringContainsString('hatch-industrial', $html);
    }
}
