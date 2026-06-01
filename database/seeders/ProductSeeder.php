<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
    Product::create([
        'nameProduct' => 'Sillon de Pallets',
        'price' => 2000 ,
        'currentStock' => 15
    ]);
    }
}
