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
            $opciones[CURLOPT_POSTFIELDS] = $post;
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

// 3. Agregar un item
$r = $c->pedir("$base/cart/$idCarrito/items", [
    '_token' => $c->token("$base/cart/$idCarrito"),
    'id_article' => 1,
    'quantity'   => 2,
]);
paso('agregar articulo al carrito', $r['codigo'] == 302, "HTTP {$r['codigo']}");

// 4. Ver el carrito con el item
$r = $c->pedir("$base/cart/$idCarrito");
paso('el carrito muestra el item', $r['codigo'] == 200 && str_contains($r['html'], 'Sillón'),
    "HTTP {$r['codigo']}");

// 5. Checkout
$r = $c->pedir("$base/cart/$idCarrito/checkout");
paso('pantalla de checkout', $r['codigo'] == 200, "HTTP {$r['codigo']}");
paso('el checkout pide el medio de pago', str_contains($r['html'], 'payment_method'),
    'campo payment_method');

// 6. Confirmar la venta
$r = $c->pedir("$base/cart/$idCarrito/confirm", [
    '_token' => $c->token("$base/cart/$idCarrito/checkout"),
    'payment_method' => 'Efectivo',
]);
paso('confirmar la venta', $r['codigo'] == 302, "HTTP {$r['codigo']}");

// 7. La venta queda registrada y con comprobante
$r = $c->pedir("$base/sales");
paso('la venta aparece en el listado', $r['codigo'] == 200 && str_contains($r['html'], 'Efectivo'),
    "HTTP {$r['codigo']}");

// 8. PDF de la venta recien hecha
preg_match('#/sales/(\d+)/pdf#', (string) $r['html'], $m);
$idVenta = $m[1] ?? null;
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
