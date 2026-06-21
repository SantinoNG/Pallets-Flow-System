<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sales', function (Blueprint $table) {
            $table->id('id_sale');
            $table->foreignId('id_cart')->constrained('cart', 'id_cart');
            $table->foreignId('id_customer')->constrained('customers', 'id_customer');
            $table->foreignId('id_employee')->nullable()->constrained('employees', 'id_employee');
            $table->string('payment_method');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('sales'); }
};
