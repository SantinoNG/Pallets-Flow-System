<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('raw_materials', function (Blueprint $table) {
            $table->id('id_raw_material');
            $table->string('description');
            $table->foreignId('id_supplier')->constrained('suppliers', 'id_supplier');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('raw_materials'); }
};
