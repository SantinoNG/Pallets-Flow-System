<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unidad de medida y costo de las materias primas.
 *
 * Antes una materia prima era solo un texto ("Tela algodon 100%") sin
 * unidad ni precio, asi que no se podia calcular cuanto material lleva un
 * producto ni cuanto cuesta armarlo. Ahora cada insumo dice en que se mide
 * (metro, bolsa, caja, litro, plancha, unidad...) y cuanto sale la unidad.
 *
 * La unidad se guarda en singular (clave de RawMaterial::UNIDADES) y la
 * vista la pluraliza: 1 bolsa / 3 bolsas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raw_materials', function (Blueprint $table) {
            $table->string('unit', 20)->default('unidad')->after('description');
            $table->decimal('unit_cost', 12, 2)->default(0)->after('unit');
        });
    }

    public function down(): void
    {
        Schema::table('raw_materials', function (Blueprint $table) {
            $table->dropColumn(['unit', 'unit_cost']);
        });
    }
};
