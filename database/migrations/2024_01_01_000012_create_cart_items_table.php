<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id('id_cart_item');
            $table->foreignId('id_cart')->constrained('cart', 'id_cart');
            $table->foreignId('id_article')->constrained('articles', 'id_article');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('cart_items'); }
};
