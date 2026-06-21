<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('cart', function (Blueprint $table) {
            $table->id('id_cart');
            $table->foreignId('id_customer')->constrained('customers', 'id_customer');
            $table->enum('status', ['active', 'completed', 'abandoned'])->default('active');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('cart'); }
};
