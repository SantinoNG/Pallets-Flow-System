<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('articles', function (Blueprint $table) {
            $table->id('id_article');
            $table->string('article_name');
            $table->string('description')->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('articles'); }
};
