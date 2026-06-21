<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id('id_supplier');
            $table->string('company_name');
            $table->string('supplier_email')->nullable();
            $table->string('supplier_phone')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('suppliers'); }
};
