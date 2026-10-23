<?php

// Prueba funcional del flujo de venta completo:
//   crear carrito -> agregar item -> checkout -> confirmar -> comprobante
//
// Se hace sobre HTTP real (no con el kernel de testing) para que se
// compruebe el middleware, los redirects y el PDF final.

$base = 'http://127.0.0.1/Pallets-Flow-System/public';

class Cliente
{
    private array $cookies = [];

    public function pedir(string $url, ?array $post = null, bool $pdf = false): array
    {
        $ch = curl_init($url);

        $opciones = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HEADER => true,
        ];

        if ($this->cookies) {
            $cabecera = [];
            foreach ($this->cookies as $n => $v) {
                $cabecera[] = $n . '=' . $v;
            }
            $opciones[CURLOPT_HTTPHEADER] = ['Cookie: ' . implode('; ', $cabecera)];
        }

        if ($post !== null) {
            $opciones[CURLOPT_POST] = true;

            // http_build_query y no el array crudo: curl convierte los
            // arrays anidados (payments[0][method]) a "Array" y el cobro
            // combinado no llega. Con la query string, los corchetes
            // viajan tal cual y Laravel los parsea.
            $opciones[CURLOPT_POSTFIELDS] = is_array($post)
                ? http_build_query($post)
                : $post;
        }

        curl_setopt_array($ch, $opciones);
        $respuesta = (string) curl_exec($ch);
        $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $destino = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        $tipo = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $tamano = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $cabeceras = substr($respuesta, 0, $tamano);
        $cuerpo = substr($respuesta, $tamano);

        if (preg_match_all('/^Set-Cookie:\s*([^=]+)=([^;]*)/mi', $cabeceras, $m)) {
            foreach ($m[1] as $i => $nombre) {
                $this->cookies[trim($nombre)] = trim($m[2][$i]);
            }
        }

        return [
            'html'    => $pdf ? '' : $cuerpo,
            'binario' => $pdf ? $cuerpo : '',
            'codigo'  => $codigo,
            'destino' => $destino,
            'tipo'    => $tipo,
        ];
    }

    public function token(string $url): string
    {
        $html = $this->pedir($url)['html'];
        preg_match('/name="_token"\s+value="([^"]+)"/', $html, $m);

        return $m[1] ?? '';
    }
}

$c = new Cliente();

$ok = 0;
$fallos = 0;

function paso(string $titulo, bool $bien, string $detalle = ''): void
{
    global $ok, $fallos;
    if ($bien) {
        $ok++;
        echo "  [ok]   {$titulo}" . ($detalle ? "  ({$detalle})" : '') . "\n";
    } else {
        $fallos++;
        echo "  [FALLA] {$titulo}" . ($detalle ? "  ({$detalle})" : '') . "\n";
    }
}

// 1. Login como vendedor (el que hace la venta)
$c->pedir("$base/login", [
    '_token' => $c->token("$base/login"),
    'email'  => 'vendedor@ecommerce.com',
    'password' => 'vendedor1234',
]);
$r = $c->pedir("$base/dashboard");
paso('login como vendedor', $r['codigo'] == 200, "HTTP {$r['codigo']}");

// 2. Crear el carrito (CartController::createOrGetCart pide id_customer)
$token = $c->token("$base/cart");
$r = $c->pedir("$base/cart", ['_token' => $token, 'id_customer' => 1]);
paso('crear carrito', $r['codigo'] == 302, "HTTP {$r['codigo']}");

// La redireccion apunta al carrito nuevo.
$idCarrito = null;
if (preg_match('#/cart/(\d+)#', (string) $r['destino'], $m)) {
    $idCarrito = $m[1];
}
paso('el carrito tiene id', $idCarrito !== null, "id_cart = {$idCarrito}");

if (! $idCarrito) {
    echo "\n  no se puede seguir sin carrito\n";
    exit(1);
}

// 3. Elegir un articulo CON STOCK
//
// Antes estaba fijo el id_article = 1. Cada corrida de este script registra
// una venta y descuenta stock, asi que al rato el articulo quedaba en 0 y la
// prueba fallaba por datos, no por codigo. Ahora se lee el desplegable del
// carrito y se toma el primero que tenga stock para las 2 unidades.
$r = $c->pedir("$base/cart/$idCarrito");
paso('abrir el carrito', $r['codigo'] == 200, "HTTP {$r['codigo']}");

preg_match_all(
    '/<option value="(\d+)"[^>]*>\s*(.+?)\s*—\s*\$[\d,.]+\s*\(Stock:\s*(\d+)\)/s',
    (string) $r['html'],
    $opciones,
    PREG_SET_ORDER
);

$articulo = null;

foreach ($opciones as $opcion) {
    if ((int) $opcion[3] >= 2) {
        $articulo = [
            'id'     => (int) $opcion[1],
            'nombre' => trim($opcion[2]),
            'stock'  => (int) $opcion[3],
        ];
        break;
    }
}

paso(
    'hay un articulo con stock',
    $articulo !== null,
    $articulo
        ? "{$articulo['nombre']} (stock {$articulo['stock']})"
        : 'todos los articulos estan sin stock'
);

if (! $articulo) {
    echo "\n  no hay stock para probar el flujo\n";
    exit(1);
}

// 4. Agregar el item
$r = $c->pedir("$base/cart/$idCarrito/items", [
    '_token' => $c->token("$base/cart/$idCarrito"),
    'id_article' => $articulo['id'],
    'quantity'   => 2,
]);
paso('agregar articulo al carrito', $r['codigo'] == 302, "HTTP {$r['codigo']}");

// 5. Ver el carrito con el item
//
// Se busca el formulario del renglon (/cart/{id}/items/{item}), que solo
// existe si el articulo entro. El desplegable de "agregar" tiene la ruta sin
// el id del item, asi que no da falso positivo.
$r = $c->pedir("$base/cart/$idCarrito");
paso(
    'el carrito muestra el item',
    $r['codigo'] == 200 && preg_match('#/cart/' . $idCarrito . '/items/\d+#', (string) $r['html']) === 1,
    "HTTP {$r['codigo']}"
);

// 6. Checkout
$r = $c->pedir("$base/cart/$idCarrito/checkout");
paso('pantalla de checkout', $r['codigo'] == 200, "HTTP {$r['codigo']}");
paso('el checkout pide los pagos', str_contains($r['html'], 'payments[0][amount]'),
    'campo payments[0][amount]');
paso('el checkout ofrece combinar medios', str_contains($r['html'], 'Agregar otro medio'),
    'boton Agregar otro medio');

// El total viene precargado en la primera fila de pago.
$total = null;
if (preg_match('/name="payments\[0\]\[amount\]"\s+value="([\d.]+)"/', $r['html'], $m)) {
    $total = (float) $m[1];
}
paso('el checkout muestra el total a cobrar', $total !== null && $total > 0,
    "total = {$total}");

if ($total === null) {
    echo "\n  no se puede seguir sin total\n";
    exit(1);
}

// 7. Confirmar la venta con PAGO COMBINADO: 40% efectivo + 60% con
//    tarjeta de credito en 6 cuotas.
$efectivo = round($total * 0.4, 2);
$resto    = round($total - $efectivo, 2);

$r = $c->pedir("$base/cart/$idCarrito/confirm", [
    '_token'   => $c->token("$base/cart/$idCarrito/checkout"),
    'payments' => [
        ['method' => 'Efectivo', 'amount' => number_format($efectivo, 2, '.', '')],
        [
            'method'       => 'Tarjeta de crédito',
            'amount'       => number_format($resto, 2, '.', ''),
            'installments' => 6,
        ],
    ],
]);
paso('confirmar la venta combinada', $r['codigo'] == 302, "HTTP {$r['codigo']}");

// 8. La venta queda registrada y con comprobante
$r = $c->pedir("$base/sales");
paso('la venta aparece en el listado', $r['codigo'] == 200 && str_contains($r['html'], 'Pago combinado'),
    "HTTP {$r['codigo']}");
paso('el listado muestra el numero de comprobante',
    (bool) preg_match('/\d{4}-\d{8}/', (string) $r['html']),
    'formato 0001-00000001');

// De aca sale el id para el comprobante y el PDF.
preg_match('#/sales/(\d+)/pdf#', (string) $r['html'], $m);
$idVenta = $m[1] ?? null;

// 8.1 El comprobante muestra las cuotas de la tarjeta
if ($idVenta) {
    $r = $c->pedir("$base/sales/$idVenta/receipt");
    paso('el comprobante muestra las cuotas',
        $r['codigo'] == 200 && str_contains((string) $r['html'], '6 cuotas de'),
        "HTTP {$r['codigo']}");
} else {
    paso('el comprobante muestra las cuotas', false, 'no se encontro el id de la venta');
}

// 9. PDF de la venta recien hecha
if ($idVenta) {
    $r = $c->pedir("$base/sales/$idVenta/pdf", null, true);
    paso('el comprobante en PDF se genera',
        $r['codigo'] == 200 && str_starts_with((string) $r['tipo'], 'application/pdf')
            && strlen($r['binario']) > 1000,
        "HTTP {$r['codigo']}, " . strlen($r['binario']) . " bytes");
} else {
    paso('el comprobante en PDF se genera', false, 'no se encontro el id de la venta');
}

// 9. El cliente ve su propia venta
$r = $c->pedir("$base/sales");
paso('el vendedor ve el listado de ventas', $r['codigo'] == 200, "HTTP {$r['codigo']}");

echo "\n  $ok pasos ok, $fallos fallidos\n";
