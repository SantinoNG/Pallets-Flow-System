<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ProductStock;
use App\Models\RawMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;

class ArticleController extends Controller
{
    // READ — Lista con filtros
    public function index(Request $request)
    {
        $query = Article::with('stock')->withCount('rawMaterials');

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

        /*
         * El Cliente ve el catálogo en modo lectura (puede comprar, pero no
         * gestionar el inventario). El flag lo usa la vista para ocultar los
         * controles de administración.
         */
        $esCliente = $request->user()?->hasRole('Cliente') ?? false;

        /*
         * El Vendedor también ve el catálogo en modo lectura, pero no
         * gestiona el inventario: los botones de alta, edición y planos
         * son solo para los roles que tienen esas rutas habilitadas.
         */
        $puedeGestionar = $request->user()?->hasAnyRole([
            'Jefe', 'Administrador', 'Encargado', 'Deposito',
        ]) ?? false;

        return view('articles.index', compact('articles', 'esCliente', 'puedeGestionar'));
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

    // READ — Detalle con la receta: cantidad y variedad de materias primas
    public function show(Article $article)
    {
        $article->load(['stock', 'rawMaterials.supplier', 'rawMaterials.stock']);

        $materiales = $article->rawMaterials;

        $proveedores = $materiales
            ->pluck('supplier.company_name')
            ->filter()
            ->unique()
            ->count();

        /*
         * Costo de materiales por unidad: cantidad de la receta x costo de
         * la unidad del insumo. Es el costo directo, sin mano de obra ni
         * gastos generales.
         */
        $costoMateriales = round($materiales->sum(
            fn ($material) => (float) $material->pivot->quantity_required * (float) $material->unit_cost
        ), 2);

        $precio    = (float) $article->unit_price;
        $margen    = round($precio - $costoMateriales, 2);
        $margenPct = $precio > 0 ? round($margen / $precio * 100, 1) : 0.0;

        /*
         * Alcance con el stock actual de materias primas: para cada insumo
         * se calcula cuantas unidades del producto se pueden armar y se
         * toma el minimo. El insumo que da ese minimo es el limitante (el
         * primero que se queda sin stock).
         */
        $alcance   = null;
        $limitante = null;

        foreach ($materiales as $material) {
            $requerido = (float) $material->pivot->quantity_required;

            if ($requerido <= 0) {
                continue;
            }

            $disponible = (float) ($material->stock->remaining_quantity ?? 0);
            $posibles   = (int) floor($disponible / $requerido);

            if ($alcance === null || $posibles < $alcance) {
                $alcance   = $posibles;
                $limitante = $material;
            }
        }

        return view('articles.show', compact(
            'article',
            'materiales',
            'proveedores',
            'costoMateriales',
            'margen',
            'margenPct',
            'alcance',
            'limitante'
        ));
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
            'raw_materials.*.quantity' => 'nullable|numeric|min:0',
            'raw_materials.*.unit'     => ['nullable', Rule::in(RawMaterial::unidadesValidas())],
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
                // La presentación se guarda aunque el insumo no esté tildado:
                // es una propiedad de la materia prima, no del plano.
                $this->guardarPresentacion($id, $data['unit'] ?? null);

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
            'raw_materials.*.quantity' => 'nullable|numeric|min:0',
            'raw_materials.*.unit'     => ['nullable', Rule::in(RawMaterial::unidadesValidas())],
        ]);

        $article->article_name = $request->article_name;
        $article->description  = $request->description;
        $article->unit_price   = $request->unit_price;
        $article->image        = $this->guardarImagen($request, $article->image);
        $article->save();

        /*
         * El stock se crea si no existe. Si el artículo se quedó sin fila de
         * stock (por ejemplo, porque se borró a mano en la base), el
         * update reventaba con un error de tipo.
         */
        $stock = $article->stock ?? new ProductStock();
        $stock->id_article         = $article->id_article;
        $stock->remaining_quantity = $request->remaining_quantity;
        $stock->minimum_stock      = $request->minimum_stock;
        $stock->save();

        $syncData = [];
        if ($request->has('raw_materials')) {
            foreach ($request->raw_materials as $id => $data) {
                // Igual que en el alta: la presentación es de la materia prima.
                $this->guardarPresentacion($id, $data['unit'] ?? null);

                if (!empty($data['selected'])) {
                    $syncData[$id] = ['quantity_required' => $data['quantity'] ?? 0];
                }
            }
        }
        $article->rawMaterials()->sync($syncData);

        return redirect()->route('articles.index')
                         ->with('success', 'Artículo actualizado exitosamente.');
    }

    /**
     * Guarda la presentación elegida dentro de los planos.
     *
     * La unidad vive en la materia prima, no en el renglón del plano: si el
     * taller pasa a comprar la espuma por rollo en vez de por plancha, el
     * cambio vale para todos los productos que la usan. La cantidad del plano
     * se interpreta en esa presentación.
     */
    private function guardarPresentacion($idMaterial, ?string $unidad): void
    {
        if (! $unidad || ! in_array($unidad, RawMaterial::unidadesValidas(), true)) {
            return;
        }

        RawMaterial::where('id_raw_material', $idMaterial)
            ->where('unit', '!=', $unidad)
            ->update(['unit' => $unidad]);
    }

    // DELETE
    public function destroy(Article $article)
    {
        /*
         * Un artículo que ya se vendió NO se puede borrar.
         *
         * El item del carrito apunta al artículo (cart_items.id_article), y
         * ese item es el historial de la venta. Si se borra el artículo, la
         * base tira el error de clave foránea y el usuario ve un 500.
         *
         * Lo que sí se puede limpiar son los items que quedaron en carritos
         * SIN venta (carritos abandonados): no son historia de nadie.
         */
        $itemsVendidos = $article->cartItems()
            ->whereHas('cart.sale')
            ->count();

        if ($itemsVendidos > 0) {
            return redirect()->route('articles.index')
                ->with('error', 'No se puede eliminar «' . $article->article_name . '»: tiene '
                    . $itemsVendidos . ' venta(s) registradas. Si ya no se ofrece, '
                    . 'dejalo con stock en cero en vez de borrarlo.');
        }

        // Items de carritos sin venta: se van con el artículo.
        $article->cartItems()->delete();

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