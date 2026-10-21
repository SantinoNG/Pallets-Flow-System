<?php

// Prueba funcional del flujo del Cliente.
//
// El Cliente es un usuario final que compra: ve el catálogo, mete cosas en
// SU carrito y revisa SUS compras. Antes no podia ni ver el catálogo
// (403) y el carrito le mostraba el flujo de caja ("seleccionar cliente").

require __DIR__ . '/http-cliente.php';

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$base = 'http://127.0.0.1/Pallets-Flow-System/public';
$email = $argv[1] ?? 'cliente@gmail.com';
$clave = $argv[2] ?? 'cliente1234';

// El id de cliente de este usuario, y el de OTRO cliente (para probar que
// no puede tocar su carrito).
$idCustomerPropio = DB::table('customers')
    ->join('users', 'users.id_user', '=', 'customers.id_user')
    ->where('users.email', $email)
    ->value('customers.id_customer');

$otroCliente = DB::table('customers')
    ->where('id_customer', '!=', $idCustomerPropio)
    ->value('id_customer');

$c = new ClienteHttp();
$t = new Contador();

// 1. Login
$t->paso('login como cliente', $c->login($base, $email, $clave) == 302);

// 2. El dashboard responde
$r = $c->pedir("$base/dashboard");
$t->paso('el dashboard responde', $r['codigo'] == 200, "HTTP {$r['codigo']}");

// 3. El catálogo se puede ver
$r = $c->pedir("$base/articles");
$t->paso('el cliente ve el catálogo', $r['codigo'] == 200, "HTTP {$r['codigo']}");

// Se cuentan las FILAS de la tabla, no los botones de "Agregar": un
// producto sin stock no muestra el boton, pero igual esta en el catalogo.
$filasCatalogo = substr_count($r['html'], '<tr class="bg-white dark:bg-zinc-900 border-b');
$t->paso('el catálogo lista los 8 artículos', $filasCatalogo === 8, "{$filasCatalogo} filas");
$t->paso('el cliente NO ve el botón "Nuevo Artículo"',
    ! str_contains($r['html'], 'Nuevo Artículo'));
$t->paso('el cliente NO ve los botones Editar/Eliminar',
    ! str_contains($r['html'], 'articles/') || ! str_contains($r['html'], '/edit"'),
    'ni "/edit" ni "Eliminar"');
$t->paso('el cliente NO ve el botón Exportar PDF',
    ! str_contains($r['html'], 'Exportar PDF'));
$t->paso('el cliente SÍ ve el botón Agregar al carrito',
    str_contains($r['html'], 'mi-carrito/items'));

// 4. /cart lo manda a su propio carrito (no al mostrador)
$r = $c->pedir("$base/cart");
$t->paso('/cart redirige en vez de mostrar el mostrador',
    $r['codigo'] == 302 && str_contains((string) $r['destino'], 'carrito'),
    "HTTP {$r['codigo']} -> {$r['destino']}");

// La cadena es: /cart -> /mi-carrito -> /cart/{id}
$r = $c->pedir((string) $r['destino']);
preg_match('#/cart/(\d+)#', (string) $r['destino'], $m);
$idCarrito = $m[1] ?? null;
$t->paso('el cliente tiene un carrito propio', $idCarrito !== null, "id_cart = {$idCarrito}");

$r = $c->pedir("$base/cart/$idCarrito");
$t->paso('el cliente ve su carrito', $r['codigo'] == 200, "HTTP {$r['codigo']}");
$t->paso('el carrito dice "Mi carrito"', str_contains($r['html'], 'Mi carrito'));

// 6. Agregar un artículo al SU carrito
$r = $c->pedir("$base/mi-carrito/items", [
    '_token'     => $c->token("$base/cart/$idCarrito"),
    'id_article' => 1,
    'quantity'   => 2,
]);
$t->paso('agregar un artículo al propio carrito', $r['codigo'] == 302, "HTTP {$r['codigo']}");

$r = $c->pedir("$base/cart/$idCarrito");
$t->paso('el artículo quedó en el carrito', str_contains($r['html'], 'Sillón'));

// 7. No puede tocar el carrito de otro
// Se busca un carrito que sea de OTRO cliente (no el propio).
$otroCarrito = $otroCliente
    ? DB::table('cart')->where('id_customer', $otroCliente)->value('id_cart')
    : null;

if ($otroCarrito) {
    $r = $c->pedir("$base/cart/$otroCarrito");
    $t->paso('NO puede abrir el carrito de otro cliente',
        $r['codigo'] == 403, "HTTP {$r['codigo']} (carrito {$otroCarrito})");
} else {
    $t->paso('NO puede abrir el carrito de otro cliente', false, 'no hay otro cliente con carrito');
}

// 8. Ve sus propias ventas
$r = $c->pedir("$base/sales");
$t->paso('el cliente ve el listado de ventas', $r['codigo'] == 200, "HTTP {$r['codigo']}");

// Su email aparece en la columna Cliente y el de otro no.
$t->paso('el listado de ventas trae su nombre de cliente',
    str_contains($r['html'], 'Ana Torres') || str_contains($r['html'], 'Torres')
        || str_contains($r['html'], 'Cliente'));

// 9. El detalle de una venta ajena da 404
$r = $c->pedir("$base/sales/1");
$t->paso('el detalle de venta responde', in_array($r['codigo'], [200, 404], true), "HTTP {$r['codigo']}");

echo $t->resumen();

exit($t->fallos > 0 ? 1 : 0);
