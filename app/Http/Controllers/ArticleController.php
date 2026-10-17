<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ProductStock;
use App\Models\RawMaterial;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    // READ — Lista todos los artículos con su stock
    public function index()
    {
        $articles = Article::with('stock')->get();
        return view('articles.index', compact('articles'));
    }

    // CREATE paso 1 — Muestra formulario con materias primas disponibles
    public function create()
    {
        $rawMaterials = RawMaterial::all();
        return view('articles.create', compact('rawMaterials'));
    }

    // CREATE paso 2 — DSD-1: crea Article, ProductStock y relaciones ArticleMaterial
    public function store(Request $request)
    {
        $request->validate([
            'article_name'       => 'required|string|max:255|unique:articles,article_name',
            'description'        => 'nullable|string|max:500',
            'unit_price'         => 'required|numeric|min:0.01',
            'remaining_quantity' => 'required|integer|min:0',
            'minimum_stock'      => 'required|integer|min:0',
        ]);

        // Paso 2 del DSD-1: crear Article y persistir
        $article = new Article();
        $article->article_name = $request->article_name;
        $article->description  = $request->description;
        $article->unit_price   = $request->unit_price;
        $article->save();

        // Paso 3 del DSD-1: crear ProductStock con stock inicial y mínimo
        $stock = new ProductStock();
        $stock->id_article          = $article->id_article;
        $stock->remaining_quantity  = $request->remaining_quantity;
        $stock->minimum_stock       = $request->minimum_stock;
        $stock->save();

        // Paso 4 del DSD-1: attach materias primas con quantity_required
        if ($request->has('raw_materials')) {
            foreach ($request->raw_materials as $id => $data) {
                if (!empty($data['selected'])) {
                    $article->rawMaterials()->attach($id, [
                        'quantity_required' => $data['quantity'] ?? 0
                    ]);
                }
            }
        }

        // Paso 5 del DSD-1: redirigir
        return redirect()->route('articles.index')
                         ->with('success', 'Artículo registrado exitosamente.');
    }

    // UPDATE paso 1 — Muestra formulario con datos actuales
    public function edit(Article $article)
    {
        $rawMaterials = RawMaterial::all();
        // Materias primas ya asociadas con su quantity_required
        $selectedMaterials = $article->rawMaterials->keyBy('id_raw_material');
        return view('articles.edit', compact('article', 'rawMaterials', 'selectedMaterials'));
    }

    // UPDATE paso 2 — DSD-2: actualiza Article, ProductStock y sincroniza ArticleMaterial
    public function update(Request $request, Article $article)
    {
        $request->validate([
            'article_name'       => 'required|string|max:255|unique:articles,article_name,' . $article->id_article . ',id_article',
            'description'        => 'nullable|string|max:500',
            'unit_price'         => 'required|numeric|min:0.01',
            'remaining_quantity' => 'required|integer|min:0',
            'minimum_stock'      => 'required|integer|min:0',
        ]);

        // Paso 2 del DSD-2: actualizar Article
        $article->article_name = $request->article_name;
        $article->description  = $request->description;
        $article->unit_price   = $request->unit_price;
        $article->save();

        // Paso 3 del DSD-2: actualizar ProductStock
        $stock = $article->stock;
        $stock->remaining_quantity = $request->remaining_quantity;
        $stock->minimum_stock      = $request->minimum_stock;
        $stock->save();

        // Paso 4 del DSD-2: sync materias primas
        $syncData = [];
        if ($request->has('raw_materials')) {
            foreach ($request->raw_materials as $id => $data) {
                if (!empty($data['selected'])) {
                    $syncData[$id] = ['quantity_required' => $data['quantity'] ?? 0];
                }
            }
        }
        $article->rawMaterials()->sync($syncData);

        // Paso 5 del DSD-2: redirigir
        return redirect()->route('articles.index')
                         ->with('success', 'Artículo actualizado exitosamente.');
    }

    // DELETE — DSD-3: detach materias primas, delete stock, delete article
    public function destroy(Article $article)
    {
        // Paso 3 del DSD-3: desvincular materias primas (tabla article_materials)
        $article->rawMaterials()->detach();

        // Paso 4 del DSD-3: eliminar stock y luego el artículo
        $article->stock()->delete();
        $article->delete();

        // Paso 5 del DSD-3: redirigir
        return redirect()->route('articles.index')
                         ->with('success', 'Artículo eliminado exitosamente.');
    }
}