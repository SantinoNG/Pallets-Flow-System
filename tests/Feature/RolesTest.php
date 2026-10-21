<?php

namespace Tests\Feature;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Coherencia del catalogo de roles.
 *
 * Spatie compara los nombres de rol como cadenas de PHP (exacto, con tilde
 * si la tiene). MySQL, en cambio, no distingue tildes al comparar. Esa
 * diferencia hace que un rol escrito "Depósito" en el seeder y "Deposito"
 * en las rutas pase los chequeos de la base de datos y aun asi tumbe con
 * HTTP 403 en el middleware `role:`.
 *
 * Estos tests fijan que todo el codigo use exactamente los mismos nombres
 * que el catalogo.
 */
class RolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    /**
     * Nombres de rol que usan las rutas, las directivas de Blade y los
     * seeders, leidos del codigo fuente.
     *
     * @return array<string, array<int, string>>
     */
    private function rolesUsadosEnElCodigo(): array
    {
        $raiz = base_path();

        $fuentes = [
            'routes/web.php'       => file_get_contents($raiz . '/routes/web.php'),
            'layouts/app.blade.php' => file_get_contents($raiz . '/resources/views/layouts/app.blade.php'),
            'RoleSeeder.php'       => file_get_contents($raiz . '/database/seeders/RoleSeeder.php'),
            'UserSeeder.php'       => file_get_contents($raiz . '/database/seeders/UserSeeder.php'),
            'RoleFactory.php'      => file_get_contents($raiz . '/database/factories/RoleFactory.php'),
        ];

        $usados = [];

        foreach ($fuentes as $archivo => $contenido) {
            // Middleware: role:Jefe|Administrador|Encargado
            preg_match_all("/role:([A-Za-zÁÉÍÓÚáéíóúñÑ|]+)/", $contenido, $m);
            foreach ($m[1] as $lista) {
                foreach (explode('|', $lista) as $rol) {
                    $usados[$archivo][] = $rol;
                }
            }

            // Directiva de Blade: @role('Jefe|Administrador')
            preg_match_all("/@role\(([^)]+)\)/", $contenido, $m);
            foreach ($m[1] as $lista) {
                foreach (explode('|', trim($lista, "' \"")) as $rol) {
                    $usados[$archivo][] = trim($rol);
                }
            }

            // Seeder y factory: role_name => 'X' / 'rol' => 'X'
            preg_match_all("/'(role_name|rol)'\s*=>\s*'([^']+)'/", $contenido, $m);
            foreach ($m[2] as $rol) {
                $usados[$archivo][] = $rol;
            }
        }

        return $usados;
    }

    #[Test]
    public function el_catalogo_tiene_los_seis_roles(): void
    {
        $nombres = Role::orderBy('id_role')->pluck('role_name')->all();

        $this->assertSame(
            ['Jefe', 'Administrador', 'Encargado', 'Cliente', 'Vendedor', 'Deposito'],
            $nombres
        );
    }

    #[Test]
    public function todo_rol_usado_en_el_codigo_existe_en_el_catalogo(): void
    {
        $catalogo = Role::pluck('role_name')->all();

        foreach ($this->rolesUsadosEnElCodigo() as $archivo => $roles) {
            foreach (array_unique($roles) as $rol) {
                if ($rol === '') {
                    continue;
                }

                // Comparacion EXACTA, como la que hace Spatie. No se puede
                // usar el where de MySQL: su collation ignora las tildes y
                // daria un falso "existe".
                $this->assertContains(
                    $rol,
                    $catalogo,
                    "En {$archivo} se usa el rol '{$rol}', que no esta en el catalogo "
                        . '[' . implode(', ', $catalogo) . ']. '
                        . 'Spatie compara los nombres exactos, asi que daria HTTP 403.'
                );
            }
        }
    }

    #[Test]
    public function el_espejo_de_spatie_tiene_los_mismos_nombres_que_el_catalogo(): void
    {
        $catalogo = Role::orderBy('id_role')->pluck('role_name')->sort()->values()->all();
        $espejo = \Spatie\Permission\Models\Role::orderBy('id')
            ->pluck('name')->sort()->values()->all();

        $this->assertSame(
            $catalogo,
            $espejo,
            'La tabla spatie_roles es el espejo de roles: tienen que tener los mismos nombres exactos.'
        );
    }

    #[Test]
    public function el_usuario_de_deposito_puede_entrar_al_modulo_de_articulos(): void
    {
        $deposito = \App\Models\User::where('email', 'deposito@ecommerce.com')->firstOrFail();

        $this->assertTrue($deposito->hasRole('Deposito'));

        // Si el nombre no coincide exacto, estas dos llamadas devuelven
        // false y el usuario cae en 403.
        $this->assertTrue($deposito->hasAnyRole(['Jefe', 'Administrador', 'Encargado', 'Deposito']));

        $this->actingAs($deposito)
            ->get('/articles')
            ->assertOk();
    }
}
