<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Los artículos no tenían forma de guardar una foto.
     *
     * La columna es nullable a propósito: los 8 artículos del catálogo ya
     * existen y no tienen imagen. La vista cae en un placeholder generado
     * (componente x-article-image) cuando la columna está vacía.
     *
     * Se guarda la ruta relativa dentro de storage/app/public (por ejemplo
     * "articles/sillon-individual.jpg"), no la URL completa: si el dominio
     * cambia, la ruta sigue sirviendo.
     */
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('image')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }
};
