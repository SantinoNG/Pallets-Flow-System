<?php

// Verifica el movimiento de compras: cuantos clientes hay, cuantas ventas
// tiene cada uno y como se reparten en los ultimos meses.

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "  Clientes: " . DB::table('customers')->count() . "\n";
echo "  Ventas  : " . DB::table('sales')->count() . "\n";
echo "  Items   : " . DB::table('cart_items')->count() . "\n\n";

echo "  Ventas por cliente (y antiguedad de la mas reciente):\n";
foreach (DB::table('sales')
    ->join('customers', 'customers.id_customer', '=', 'sales.id_customer')
    ->join('users', 'users.id_user', '=', 'customers.id_user')
    ->selectRaw('users.email, customers.first_name, customers.last_name, count(sales.id_sale) as ventas, max(sales.created_at) as ultima, min(sales.created_at) as primera')
    ->groupBy('users.email', 'customers.first_name', 'customers.last_name')
    ->orderBy('ventas', 'desc')
    ->get() as $f) {

    $dias = (int) now()->diffInDays(\Illuminate\Support\Carbon::parse($f->ultima));

    printf("    %-34s %-22s %2d ventas   ultima: %s (hace %d dias)\n",
        $f->email,
        $f->first_name . ' ' . $f->last_name,
        $f->ventas,
        \Illuminate\Support\Carbon::parse($f->ultima)->format('d/m/Y'),
        $dias);
}

echo "\n  Ventas por mes (ultimos 6):\n";
$porMes = DB::table('sales')
    ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as mes, count(*) as n, sum(payment_method = 'Efectivo') as efectivo")
    ->where('created_at', '>=', now()->subMonths(6))
    ->groupBy('mes')
    ->orderBy('mes')
    ->get();

foreach ($porMes as $m) {
    $barra = str_repeat('#', $m->n);
    printf("    %s  %2d ventas  %s\n", $m->mes, $m->n, $barra);
}

echo "\n  Ultimos 4 meses: " . DB::table('sales')->where('created_at', '>=', now()->subMonths(4))->count() . " ventas\n";
echo "  Ultimos 90 dias : " . DB::table('sales')->where('created_at', '>=', now()->subDays(90))->count() . " ventas\n";
echo "  Ultimos 30 dias : " . DB::table('sales')->where('created_at', '>=', now()->subDays(30))->count() . " ventas\n";

echo "\n  Medios de pago:\n";
foreach (DB::table('sales')->selectRaw('payment_method, count(*) as n')->groupBy('payment_method')->get() as $p) {
    printf("    %-22s %2d\n", $p->payment_method, $p->n);
}
