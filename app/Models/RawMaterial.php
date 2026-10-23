<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RawMaterial extends Model
{
    /**
     * Unidades de medida admitidas.
     *
     * La clave es lo que se guarda en `raw_materials.unit`; el par es
     * [singular, plural] para mostrarlo. "kg" no cambia.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const UNIDADES = [
        'unidad'  => ['unidad', 'unidades'],
        'metro'   => ['metro', 'metros'],
        'bolsa'   => ['bolsa', 'bolsas'],
        'caja'    => ['caja', 'cajas'],
        'litro'   => ['litro', 'litros'],
        'pliego'  => ['pliego', 'pliegos'],
        'rollo'   => ['rollo', 'rollos'],
        'plancha' => ['plancha', 'planchas'],
        'kg'      => ['kg', 'kg'],
    ];

    protected $primaryKey = 'id_raw_material';

    /**
     * La ruta debe usar la PK real de la tabla.
     * Sin esto, Route::modelBinding() buscaria por 'id',
     * columna que no existe en estas tablas.
     */
    public function getRouteKeyName(): string
    {
        return 'id_raw_material';
    }

    protected $fillable = ['description', 'unit', 'unit_cost', 'id_supplier'];

    protected $casts = [
        'unit_cost' => 'float',
    ];

    public function supplier() {
        return $this->belongsTo(Supplier::class, 'id_supplier');
    }
    public function stock() {
        return $this->hasOne(RawMaterialStock::class, 'id_raw_material');
    }
    public function articles() {
        return $this->belongsToMany(Article::class, 'article_materials', 'id_raw_material', 'id_article')
                    ->withPivot('quantity_required');
    }

    /**
     * Claves validas para el <select> de unidad.
     *
     * @return list<string>
     */
    public static function unidadesValidas(): array
    {
        return array_keys(self::UNIDADES);
    }

    /**
     * Nombre de la unidad para una cantidad.
     *
     * 1 -> singular ("metro"), 1.5 o 3 -> plural ("metros").
     */
    public function etiquetaUnidad(float $cantidad = 2): string
    {
        [$singular, $plural] = self::UNIDADES[$this->unit] ?? self::UNIDADES['unidad'];

        return abs($cantidad - 1) < 0.0001 ? $singular : $plural;
    }

    /**
     * Cantidad con su unidad, lista para mostrar: "3 metros", "1 bolsa",
     * "1.50 litros".
     */
    public function cantidad(float $cantidad): string
    {
        $numero = floor($cantidad) == $cantidad
            ? number_format($cantidad, 0)
            : number_format($cantidad, 2);

        return $numero . ' ' . $this->etiquetaUnidad($cantidad);
    }
}
