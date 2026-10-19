<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Article;
use App\Models\Supplier;
use App\Models\ProductStock;

class Dashboard extends Component
{
    public $periodo = '30_dias';

    public function render()
    {
        $totalVentas = 348000;
        $articulosCount = Article::count();
        $proveedoresCount = Supplier::count();
        $alertasStock = ProductStock::whereRaw('remaining_quantity <= minimum_stock')->count();

        $ventasMes = [
            'labels' => ['Jun', 'Jul', 'Ago', 'Sep', 'Oct'],
            'data' => [120000, 190000, 280000, 310000, $totalVentas]
        ];

        $topProductos = [
            'labels' => ['Sillón Doble', 'Mesa Ratona', 'Sillón Indiv.', 'Cama 2 Plazas', 'Estantería'],
            'data' => [42, 35, 28, 14, 10]
        ];

        $mediosPago = [
            'labels' => ['Transferencia', 'Efectivo', 'T. Crédito', 'T. Débito'],
            'data' => [45, 30, 15, 10]
        ];

        $stockPorCategoria = [
            'labels' => ['Sillones', 'Mesas', 'Estanterías', 'Camas', 'Macetas'],
            'data'   => [10, 16, 10, 1, 15]
        ];

        $materiaPrimaStock = [
            'labels' => ['Pallets Madera', 'Tornillos (cajas)', 'Barniz (Lts)', 'Lijas (paq)'],
            'actual' => [120, 15, 8, 25],
            'minimo' => [50, 20, 10, 15]
        ];

        return view('livewire.dashboard', compact(
            'totalVentas',
            'articulosCount',
            'proveedoresCount',
            'alertasStock',
            'ventasMes',
            'stockPorCategoria'
        ));
    }
}