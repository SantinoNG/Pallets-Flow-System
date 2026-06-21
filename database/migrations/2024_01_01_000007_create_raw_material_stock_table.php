<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('raw_material_stock', function (Blueprint $table) {
            $table->foreignId('id_raw_material')->primary()->constrained('raw_materials', 'id_raw_material');
            $table->integer('remaining_quantity')->default(0);
            $table->integer('minimum_stock')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('raw_material_stock'); }
};
