<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ProductStock;
use App\Models\RawMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class ArticleController extends Controller
{
    // READ — Lista con filtros
    public function index(Request $request)
    {
        $query = Article::with('stock');

        // Filtro por nombre
        if ($request->filled('search')) {
            $query->where('article_name', 'like', '%' . $request->search . '%');
        }

        // Filtro por precio mínimo
        if ($request->filled('price_min')) {
            $query->where('unit_price', '>=', $request->price_min);
        }

        // Filtro por precio máximo
        if ($request->filled('price_max')) {
            $query->where('unit_price', '<=', $request->price_max);
        }

        // Filtro por stock bajo (stock actual <= stock mínimo)
        if ($request->filled('low_stock') && $request->low_stock == '1') {
            $query->whereHas('stock', function ($q) {
                $q->whereColumn('remaining_quantity', '<=', 'minimum_stock');
            });
        }

        $articles = $query->get();

        return view('articles.index', compact('articles'));
    }

    // EXPORTAR PDF con los mismos filtros
    public function exportPdf(Request $request)
    {
        $query = Article::with('stock');

        if ($request->filled('search')) {
            $query->where('article_name', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('price_min')) {
            $query->where('unit_price', '>=', $request->price_min);
        }
        if ($request->filled('price_max')) {
            $query->where('unit_price', '<=', $request->price_max);
        }
        if ($request->filled('low_stock') && $request->low_stock == '1') {
            $query->whereHas('stock', function ($q) {
                $q->whereColumn('remaining_quantity', '<=', 'minimum_stock');
            });
        }

        $articles = $query->get();
        $filters  = $request->only('search', 'price_min', 'price_max', 'low_stock');

        $pdf = Pdf::loadView('articles.pdf', compact('articles', 'filters'))
                  ->setPaper('a4', 'portrait');

        return $pdf->download('articulos-' . now()->format('Y-m-d') . '.pdf');
    }

    // CREATE paso 1
    public function create()
    {
        $rawMaterials = RawMaterial::all();
        return view('articles.create', compact('rawMaterials'));
    }

    // CREATE paso 2
    public function store(Request $request)
    {
        $request->validate([
            'article_name'       => 'required|string|max:255|unique:articles,article_name',
            'description'        => 'nullable|string|max:500',
            'unit_price'         => 'required|numeric|min:0.01',
            'remaining_quantity' => 'required|integer|min:0',
            'minimum_stock'      => 'required|integer|min:0',
            'image'              => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $article = new Article();
        $article->article_name = $request->article_name;
        $article->description  = $request->description;
        $article->unit_price   = $request->unit_price;
        $article->image        = $this->guardarImagen($request);
        $article->save();

        $stock = new ProductStock();
        $stock->id_article         = $article->id_article;
        $stock->remaining_quantity = $request->remaining_quantity;
        $stock->minimum_stock      = $request->minimum_stock;
        $stock->save();

        if ($request->has('raw_materials')) {
            foreach ($request->raw_materials as $id => $data) {
                if (!empty($data['selected'])) {
                    $article->rawMaterials()->attach($id, [
                        'quantity_required' => $data['quantity'] ?? 0
                    ]);
                }
            }
        }

        return redirect()->route('articles.index')
                         ->with('success', 'Artículo registrado exitosamente.');
    }

    // UPDATE paso 1
    public function edit(Article $article)
    {
        $rawMaterials      = RawMaterial::all();
        $selectedMaterials = $article->rawMaterials->keyBy('id_raw_material');
        return view('articles.edit', compact('article', 'rawMaterials', 'selectedMaterials'));
    }

    // UPDATE paso 2
    public function update(Request $request, Article $article)
    {
        $request->validate([
            'article_name'       => 'required|string|max:255|unique:articles,article_name,' . $article->id_article . ',id_article',
            'description'        => 'nullable|string|max:500',
            'unit_price'         => 'required|numeric|min:0.01',
            'remaining_quantity' => 'required|integer|min:0',
            'minimum_stock'      => 'required|integer|min:0',
            'image'              => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $article->article_name = $request->article_name;
        $article->description  = $request->description;
        $article->unit_price   = $request->unit_price;
        $article->image        = $this->guardarImagen($request, $article->image);
        $article->save();

        $stock                     = $article->stock;
        $stock->remaining_quantity = $request->remaining_quantity;
        $stock->minimum_stock      = $request->minimum_stock;
        $stock->save();

        $syncData = [];
        if ($request->has('raw_materials')) {
            foreach ($request->raw_materials as $id => $data) {
                if (!empty($data['selected'])) {
                    $syncData[$id] = ['quantity_required' => $data['quantity'] ?? 0];
                }
            }
        }
        $article->rawMaterials()->sync($syncData);

        return redirect()->route('articles.index')
                         ->with('success', 'Artículo actualizado exitosamente.');
    }

    // DELETE
    public function destroy(Article $article)
    {
        $this->borrarImagen($article->image);

        $article->rawMaterials()->detach();
        $article->stock()->delete();
        $article->delete();

        return redirect()->route('articles.index')
                         ->with('success', 'Artículo eliminado exitosamente.');
    }

    /**
     * Guarda la foto subida y devuelve la ruta relativa, o null si no
     * se subió ninguna.
     *
     * La imagen va al disco 'public' (storage/app/public/articles) para que
     * sea accesible por el navegador a traves del enlace storage/ que crea
     * `php artisan storage:link`.
     *
     * @param  string|null  $rutaAnterior  la imagen que se reemplaza, para
     *                                       borrarla del disco
     */
    private function guardarImagen(Request $request, ?string $rutaAnterior = null): ?string
    {
        if (! $request->hasFile('image')) {
            return $rutaAnterior;
        }

        $archivo = $request->file('image');

        // La imagen nueva reemplaza a la vieja: la vieja no se puede
        // quedar dando vueltas en el disco.
        $this->borrarImagen($rutaAnterior);

        return $archivo->store('articles', 'public');
    }

    /**
     * Borra una imagen del disco, si esta guardada.
     */
    private function borrarImagen(?string $ruta): void
    {
        if (empty($ruta)) {
            return;
        }

        Storage::disk('public')->delete($ruta);
    }
}