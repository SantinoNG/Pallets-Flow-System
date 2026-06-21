<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('article_materials', function (Blueprint $table) {
            $table->foreignId('id_article')->constrained('articles', 'id_article');
            $table->foreignId('id_raw_material')->constrained('raw_materials', 'id_raw_material');
            $table->decimal('quantity_required', 10, 2);
            $table->primary(['id_article', 'id_raw_material']);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('article_materials'); }
};
