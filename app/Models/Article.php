<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    /**
     * @use HasFactory<\Database\Factories\ArticleFactory>
     *
     * HasFactory habilita Article::factory(). Sin este trait el metodo no
     * existe y el CartTest falla con:
     *
     *     Call to undefined method App\Models\Article::factory()
     */
    use HasFactory;

    protected $primaryKey = 'id_article';

    /**
     * La ruta debe usar la PK real de la tabla.
     * Sin esto, Route::modelBinding() buscaria por 'id',
     * columna que no existe en estas tablas.
     */
    public function getRouteKeyName(): string
    {
        return 'id_article';
    }

    protected $fillable = ['article_name', 'description', 'unit_price', 'image'];

    /**
     * URL publica de la foto del artículo, o null si no tiene.
     *
     * La columna guarda la ruta relativa dentro de storage/app/public
     * (por ejemplo "articles/sillon.jpg"), así que se le antepone el
     * prefijo de disco publico. Si la columna está vacía, devuelve null y
     * la vista dibuja el placeholder.
     */
    public function getImagenUrlAttribute(): ?string
    {
        if (empty($this->image)) {
            return null;
        }

        return asset('storage/' . $this->image);
    }

    /**
     * True si el artículo tiene foto propia.
     */
    public function tieneImagen(): bool
    {
        return ! empty($this->image);
    }

    public function stock() {
        return $this->hasOne(ProductStock::class, 'id_article');
    }
    public function rawMaterials() {
        return $this->belongsToMany(RawMaterial::class, 'article_materials', 'id_article', 'id_raw_material')
                    ->withPivot('quantity_required');
    }
    public function cartItems() {
        return $this->hasMany(CartItem::class, 'id_article');
    }
}