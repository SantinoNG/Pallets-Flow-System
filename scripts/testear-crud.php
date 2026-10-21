<?php

// Testeo funcional de las operaciones de ESCRITURA (ABM) sobre HTTP real.
//
// Los tests de Laravel usan el kernel de testing (sin pasar por el servidor
// web), asi que no cubrian el flujo real de los formularios. Este script
// recorre cada alta, edicion y baja con la sesion de un usuario.
//
// OJO con las URLs: el nombre de la ruta y la URL no coinciden siempre.
//   route('articles.pdf')  ->  /articles/pdf
//   route('password.update') ->  /password
//   POST /suppliers        ->  NO es /proveedores
//
// Uso: php scripts/testear-crud.php

require __DIR__ . '/http-cliente.php';

// La base se consulta para saber los ids reales de lo que se crea: el
// listado no siempre los muestra primero.
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$base = 'http://127.0.0.1/Pallets-Flow-System/public';

$t = new Contador();
$admin = new ClienteHttp();
$admin->login($base, 'admin@ecommerce.com', 'admin1234');

/**
 * Devuelve el id de un registro a partir del listado.
 */
function idDe(ClienteHttp $c, string $base, string $listado, string $patron): ?int
{
    $html = $c->pedir("$base/$listado")['html'];

    if (preg_match($patron, $html, $m)) {
        return (int) $m[1];
    }

    return null;
}

/**
 * Id de un rol recien creado, por su nombre.
 */
function idRol(string $nombre): ?int
{
    $id = DB::table('roles')->where('role_name', $nombre)->value('id_role');

    return $id ? (int) $id : null;
}

/**
 * Id de un proveedor recien creado, por su nombre.
 */
function idProveedor(string $empresa): ?int
{
    $id = DB::table('suppliers')->where('company_name', $empresa)->value('id_supplier');

    return $id ? (int) $id : null;
}

/**
 * Id de un empleado recien creado, por su apellido.
 */
function idEmpleado(string $apellido): ?int
{
    $id = DB::table('employees')->where('last_name', $apellido)->value('id_employee');

    return $id ? (int) $id : null;
}

/**
 * Id de un articulo recien creado, por su nombre.
 */
function idArticulo(string $nombre): ?int
{
    $id = DB::table('articles')->where('article_name', $nombre)->value('id_article');

    return $id ? (int) $id : null;
}

/**
 * Id de un articulo que tenga stock (para probar el carrito).
 */
function idArticuloConStock(): ?int
{
    $id = DB::table('product_stock')
        ->where('remaining_quantity', '>', 0)
        ->orderBy('id_article')
        ->value('id_article');

    return $id ? (int) $id : null;
}

/**
 * Devuelve el valor de un <option> por su texto.
 */
function optionPorTexto(string $html, string $texto): ?string
{
    // El <option> del proyecto tiene el value en la linea siguiente.
    if (preg_match('~<option[^>]*value="(\d+)"[^>]*>\s*' . preg_quote($texto, '~') . '~i', $html, $m)) {
        return $m[1];
    }

    return null;
}

// ---------------------------------------------------------------- ARTICULOS
echo "  === ARTICULOS ===\n";

$sufijo = random_int(1000, 9999);
$nombre = "Articulo de prueba $sufijo";

$r = $admin->pedir("$base/articles", [
    '_token'            => $admin->token("$base/articles/create"),
    'article_name'      => $nombre,
    'description'       => 'Creado por el testeo automatico',
    'unit_price'        => '12345.67',
    'remaining_quantity' => '7',
    'minimum_stock'      => '2',
]);
$t->paso('crear artículo', $r['codigo'] == 302, "HTTP {$r['codigo']}");

$idArt = idArticulo($nombre);
$t->paso('el artículo aparece en el listado', $idArt !== null, "id {$idArt}");

$r = $admin->pedir("$base/articles/$idArt", [
    '_token'            => $admin->token("$base/articles/$idArt/edit"),
    '_method'           => 'PUT',
    'article_name'      => "$nombre EDITADO",
    'description'       => 'Editado por el testeo',
    'unit_price'        => '9999.99',
    'remaining_quantity' => '3',
    'minimum_stock'      => '1',
]);
$t->paso('editar artículo', $r['codigo'] == 302, "HTTP {$r['codigo']}");

$html = $admin->pedir("$base/articles")['html'];
$t->paso('el artículo muestra el nombre nuevo', str_contains($html, "$nombre EDITADO"));

// Nombre duplicado: tiene que rebotar
$r = $admin->pedir("$base/articles", [
    '_token'            => $admin->token("$base/articles/create"),
    'article_name'      => 'Sillón individual de pallet',
    'unit_price'        => '100',
    'remaining_quantity' => '1',
    'minimum_stock'      => '1',
]);
$t->paso('rechazar nombre duplicado', $r['codigo'] == 302, "HTTP {$r['codigo']}");

// Borrar uno que NO se vendio: tiene que funcionar
$r = $admin->pedir("$base/articles/$idArt", [
    '_token'  => $admin->token("$base/articles"),
    '_method' => 'DELETE',
]);
$t->paso('borrar artículo sin ventas', $r['codigo'] == 302, "HTTP {$r['codigo']}");

$html = $admin->pedir("$base/articles")['html'];
$t->paso('el artículo ya no está en el listado', ! str_contains($html, "$nombre EDITADO"));

// Borrar uno que SI se vendio: tiene que avisar, no dar 500
$idVendido = DB::table('cart_items')
    ->join('sales', 'sales.id_cart', '=', 'cart_items.id_cart')
    ->value('cart_items.id_article');
$r = $admin->pedir("$base/articles/$idVendido", [
    '_token'  => $admin->token("$base/articles"),
    '_method' => 'DELETE',
]);
$t->paso('borrar artículo VENDIDO avisa en vez de dar 500',
    $r['codigo'] == 302, "HTTP {$r['codigo']}");

// ---------------------------------------------------------------- PROVEEDORES
echo "  === PROVEEDORES ===\n";

$empresa = "Proveedor Test $sufijo";
$email = "test$sufijo@proveedor.com";

// OJO: el alta es POST /suppliers (no /proveedores).
$r = $admin->pedir("$base/suppliers", [
    '_token'         => $admin->token("$base/suppliers/create"),
    'company_name'   => $empresa,
    'supplier_email' => $email,
    'supplier_phone' => '3764-000000',
    'materials'      => ['Tela algodón 100%'],
]);
$t->paso('crear proveedor', $r['codigo'] == 302, "HTTP {$r['codigo']}");

$idProv = idProveedor($empresa);
$t->paso('el proveedor aparece en el listado', $idProv !== null, "id {$idProv}");

$r = $admin->pedir("$base/suppliers/$idProv", [
    '_token'         => $admin->token("$base/suppliers/$idProv/edit"),
    '_method'        => 'PUT',
    'company_name'   => "$empresa EDITADO",
    'supplier_email' => $email,
    'supplier_phone' => '3764-111111',
]);
$t->paso('editar proveedor', $r['codigo'] == 302, "HTTP {$r['codigo']}");

$r = $admin->pedir("$base/suppliers/$idProv", [
    '_token'  => $admin->token("$base/suppliers"),
    '_method' => 'DELETE',
]);
$t->paso('borrar proveedor sin recetas', $r['codigo'] == 302, "HTTP {$r['codigo']}");

// ---------------------------------------------------------------- EMPLEADOS
echo "  === EMPLEADOS ===\n";

// El alta necesita un usuario SIN ficha de empleado.
$html = $admin->pedir("$base/employees/create")['html'];
preg_match_all('~<option[^>]*value="(\d+)"~', $html, $mu);
$idUserLibre = $mu[1][count($mu[1]) - 1] ?? null;
$idRolEmpleado = optionPorTexto($html, 'Encargado') ?? optionPorTexto($html, 'Vendedor');

if ($idUserLibre && $idRolEmpleado) {
    $r = $admin->pedir("$base/employees", [
        '_token'     => $admin->token("$base/employees/create"),
        'id_user'    => $idUserLibre,
        'id_role'    => $idRolEmpleado,
        'first_name' => 'Empleado',
        'last_name'  => "Test $sufijo",
        'phone'      => '3764-222222',
    ]);
    $t->paso('crear empleado', $r['codigo'] == 302, "HTTP {$r['codigo']}");

    $idEmpleado = idEmpleado("Test $sufijo");
    $t->paso('el empleado aparece en el listado', $idEmpleado !== null, "id {$idEmpleado}");

    if ($idEmpleado) {
        $r = $admin->pedir("$base/employees/$idEmpleado", [
            '_token'     => $admin->token("$base/employees/$idEmpleado/edit"),
            '_method'    => 'PUT',
            'id_user'    => $idUserLibre,
            'id_role'    => $idRolEmpleado,
            'first_name' => 'Empleado',
            'last_name'  => "Test $sufijo EDITADO",
            'phone'      => '3764-333333',
        ]);
        $t->paso('editar empleado', $r['codigo'] == 302, "HTTP {$r['codigo']}");

        $r = $admin->pedir("$base/employees/$idEmpleado", [
            '_token'  => $admin->token("$base/employees"),
            '_method' => 'DELETE',
        ]);
        $t->paso('borrar empleado', $r['codigo'] == 302, "HTTP {$r['codigo']}");
    }
} else {
    $t->paso('empleados', false, 'no hay usuario libre ni rol en el formulario');
}

// ---------------------------------------------------------------- ROLES
echo "  === ROLES ===\n";

$rol = "Rol Test $sufijo";
$r = $admin->pedir("$base/roles", [
    '_token'      => $admin->token("$base/roles/create"),
    'role_name'   => $rol,
    'description' => 'Creado por el testeo',
]);
$t->paso('crear rol', $r['codigo'] == 302, "HTTP {$r['codigo']}");

$idRol = idRol($rol);
$t->paso('el rol aparece en el listado', $idRol !== null, "id {$idRol}");

$r = $admin->pedir("$base/roles/$idRol", [
    '_token'      => $admin->token("$base/roles/$idRol/edit"),
    '_method'     => 'PUT',
    'role_name'   => "$rol EDITADO",
    'description' => 'Editado',
]);
$t->paso('editar rol', $r['codigo'] == 302, "HTTP {$r['codigo']}");

$r = $admin->pedir("$base/roles/$idRol", [
    '_token'  => $admin->token("$base/roles"),
    '_method' => 'DELETE',
]);
$t->paso('borrar rol', $r['codigo'] == 302, "HTTP {$r['codigo']}");

// ---------------------------------------------------------------- USUARIOS
echo "  === USUARIOS: ASIGNAR ROL ===\n";

$html = $admin->pedir("$base/usuarios")['html'];
preg_match_all('~usuarios/(\d+)/roles~', $html, $m);
$idUsuario = $m[1][0] ?? null;
$t->paso('hay usuarios en el listado', $idUsuario !== null, "id {$idUsuario}");

$html = $admin->pedir("$base/usuarios/$idUsuario/roles")['html'];
$idRolCliente = optionPorTexto($html, 'Cliente');

if ($idUsuario && $idRolCliente) {
    $r = $admin->pedir("$base/usuarios/$idUsuario/roles", [
        '_token'  => $admin->token("$base/usuarios/$idUsuario/roles"),
        '_method' => 'PUT',
        'id_role'  => $idRolCliente,
    ]);
    $t->paso('asignar rol a un usuario', $r['codigo'] == 302, "HTTP {$r['codigo']}");
} else {
    $t->paso('asignar rol a un usuario', false, 'no se encontraron los ids');
}

// ---------------------------------------------------------------- CARRITO
echo "  === CARRITO DEL PERSONAL (MOSTRADOR) ===\n";

$html = $admin->pedir("$base/cart")['html'];
preg_match('~<option value="(\d+)"~', $html, $mc);
$idCustomer = $mc[1] ?? null;
$t->paso('el mostrador lista clientes', $idCustomer !== null, "cliente {$idCustomer}");

$r = $admin->pedir("$base/cart", [
    '_token'      => $admin->token("$base/cart"),
    'id_customer' => $idCustomer,
]);
$t->paso('crear carrito para el cliente', $r['codigo'] == 302, "HTTP {$r['codigo']}");

preg_match('#/cart/(\d+)#', (string) $r['destino'], $md);
$idCart = $md[1] ?? null;
$t->paso('el carrito se creo', $idCart !== null, "carrito {$idCart}");

// Un articulo CON stock (el 1 puede estar en cero y el add rebota).
$idArtConStock = idArticuloConStock();

$r = $admin->pedir("$base/cart/$idCart/items", [
    '_token'     => $admin->token("$base/cart/$idCart"),
    'id_article' => $idArtConStock,
    'quantity'   => 1,
]);
$t->paso('agregar artículo al carrito', $r['codigo'] == 302, "HTTP {$r['codigo']}");

$html = $admin->pedir("$base/cart/$idCart")['html'];
preg_match('~items/(\d+)~', $html, $mi);
$idItem = $mi[1] ?? null;
$t->paso('el item está en el carrito', $idItem !== null, "item {$idItem}");

if ($idItem) {
    $r = $admin->pedir("$base/cart/$idCart/items/$idItem", [
        '_token'  => $admin->token("$base/cart/$idCart"),
        '_method' => 'DELETE',
    ]);
    $t->paso('quitar el item del carrito', $r['codigo'] == 302, "HTTP {$r['codigo']}");
}

// ---------------------------------------------------------------- PERFIL
echo "  === PERFIL ===\n";

$nombreAdmin = "Admin Test $sufijo";
$r = $admin->pedir("$base/profile", [
    '_token'  => $admin->token("$base/profile"),
    '_method' => 'PATCH',
    'name'    => $nombreAdmin,
    'email'   => 'admin@ecommerce.com',
]);
$t->paso('actualizar el perfil', $r['codigo'] == 302, "HTTP {$r['codigo']}");

// OJO: la ruta es PUT /password, no /profile/password.
$r = $admin->pedir("$base/password", [
    '_token'               => $admin->token("$base/profile"),
    '_method'              => 'PUT',
    'current_password'     => 'admin1234',
    'password'             => 'admin1234',
    'password_confirmation' => 'admin1234',
]);
$t->paso('cambiar la contraseña', $r['codigo'] == 302, "HTTP {$r['codigo']}");

// ---------------------------------------------------------------- CLIENTE
echo "  === CARRITO DEL CLIENTE ===\n";

$cliente = new ClienteHttp();
$cliente->login($base, 'cliente@gmail.com', 'cliente1234');

// El token CSRF se saca de una pagina con formulario (el catalogo).
$tokenCliente = $cliente->token("$base/articles");

$r = $cliente->pedir("$base/mi-carrito/items", [
    '_token'     => $tokenCliente,
    'id_article' => 2,
    'quantity'   => 1,
]);
$t->paso('el cliente agrega a su carrito', $r['codigo'] == 302, "HTTP {$r['codigo']}");

$r = $cliente->pedir("$base/mi-carrito");
preg_match('#/cart/(\d+)#', (string) $r['destino'], $md);
$idCartCliente = $md[1] ?? null;
$t->paso('el cliente tiene su propio carrito', $idCartCliente !== null, "carrito {$idCartCliente}");

$r = $cliente->pedir("$base/cart/1");
$t->paso('el cliente NO puede abrir el carrito de otro', $r['codigo'] == 403, "HTTP {$r['codigo']}");

echo "\n";
echo $t->resumen();
exit($t->fallos > 0 ? 1 : 0);
