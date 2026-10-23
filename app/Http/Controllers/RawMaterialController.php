<?php

namespace App\Http\Controllers;

use App\Models\RawMaterial;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Materias primas.
 *
 * Hasta ahora los insumos solo se podian crear desde el alta de un
 * proveedor, y no habia un lugar donde verlos todos juntos: cuanto hay,
 * cuanto cuesta, cual esta bajo minimo y que productos lo usan.
 *
 * Este modulo es ese apartado, separado de Proveedores. Cada materia prima
 * declara su PRESENTACION (caja, bolsa, unidad, metro, litro...) y el
 * proveedor que la brinda. El stock se guarda en `raw_material_stock`.
 */
class RawMaterialController extends Controller
{
    // READ — Lista con filtros por descripcion, proveedor, presentacion y stock bajo
    public function index(Request $request)
    {
        $materiales = $this->consultaFiltrada($request)
            ->with(['supplier', 'stock'])
            ->withCount('articles')
            ->orderBy('description')
            ->paginate(20)
            ->withQueryString();

        /*
         * Resumen de arriba: cuantas materias primas hay, cuantas
         * presentaciones distintas, cuantas estan bajo minimo y cuanto vale
         * el stock. Es la lectura rapida del deposito.
         */
        $resumen = [
            'total'      => RawMaterial::count(),
            'unidades'   => RawMaterial::distinct()->count('unit'),
            'bajoMinimo' => RawMaterial::whereHas('stock', function ($q) {
                $q->whereColumn('remaining_quantity', '<=', 'minimum_stock');
            })->count(),
            'valor'      => RawMaterial::with('stock')->get()->sum(
                fn ($material) => (float) ($material->stock->remaining_quantity ?? 0) * (float) $material->unit_cost
            ),
        ];

        $proveedores = Supplier::orderBy('company_name')->get();

        /*
         * El Deposito consulta el stock pero no da de alta ni edita insumos:
         * la vista esconde los botones y las rutas de escritura lo vuelven a
         * controlar.
         */
        $puedeGestionar = $request->user()->hasAnyRole(['Jefe', 'Administrador', 'Encargado']);

        return view('raw-materials.index', compact('materiales', 'resumen', 'proveedores', 'puedeGestionar'));
    }

    // EXPORTAR PDF — mismos filtros que la pantalla
    public function exportPdf(Request $request)
    {
        $materiales = $this->consultaFiltrada($request)
            ->with(['supplier', 'stock'])
            ->withCount('articles')
            ->orderBy('description')
            ->get();

        $proveedorFiltro = $request->filled('supplier')
            ? Supplier::where('id_supplier', $request->supplier)->value('company_name')
            : null;

        $pdf = Pdf::loadView('raw-materials.pdf', compact('materiales', 'proveedorFiltro'));

        return $pdf->download('reporte_materias_primas.pdf');
    }

    // CREATE paso 1 — Formulario vacio
    public function create()
    {
        $proveedores = Supplier::orderBy('company_name')->get();

        return view('raw-materials.create', compact('proveedores'));
    }

    // CREATE paso 2 — Crea el insumo y su fila de stock
    public function store(Request $request)
    {
        $datos = $request->validate([
            'description'        => 'required|string|max:255|unique:raw_materials,description',
            'unit'               => ['required', Rule::in(RawMaterial::unidadesValidas())],
            'unit_cost'          => 'required|numeric|min:0',
            'id_supplier'        => 'required|integer|exists:suppliers,id_supplier',
            'remaining_quantity' => 'required|integer|min:0',
            'minimum_stock'      => 'required|integer|min:0',
        ]);

        $material = RawMaterial::create([
            'description' => trim($datos['description']),
            'unit'        => $datos['unit'],
            'unit_cost'   => $datos['unit_cost'],
            'id_supplier' => $datos['id_supplier'],
        ]);

        $material->stock()->create([
            'remaining_quantity' => $datos['remaining_quantity'],
            'minimum_stock'      => $datos['minimum_stock'],
        ]);

        return redirect()->route('raw-materials.index')
            ->with('success', 'Materia prima registrada exitosamente.');
    }

    // UPDATE paso 1 — Formulario con los datos actuales
    public function edit(RawMaterial $material)
    {
        $material->load('stock');
        $proveedores = Supplier::orderBy('company_name')->get();

        return view('raw-materials.edit', compact('material', 'proveedores'));
    }

    // UPDATE paso 2 — Actualiza el insumo y su stock
    public function update(Request $request, RawMaterial $material)
    {
        $datos = $request->validate([
            'description' => [
                'required', 'string', 'max:255',
                Rule::unique('raw_materials', 'description')->ignore($material->id_raw_material, 'id_raw_material'),
            ],
            'unit'               => ['required', Rule::in(RawMaterial::unidadesValidas())],
            'unit_cost'          => 'required|numeric|min:0',
            'id_supplier'        => 'required|integer|exists:suppliers,id_supplier',
            'remaining_quantity' => 'required|integer|min:0',
            'minimum_stock'      => 'required|integer|min:0',
        ]);

        $material->update([
            'description' => trim($datos['description']),
            'unit'        => $datos['unit'],
            'unit_cost'   => $datos['unit_cost'],
            'id_supplier' => $datos['id_supplier'],
        ]);

        $material->stock()->updateOrCreate(
            ['id_raw_material' => $material->id_raw_material],
            [
                'remaining_quantity' => $datos['remaining_quantity'],
                'minimum_stock'      => $datos['minimum_stock'],
            ]
        );

        return redirect()->route('raw-materials.index')
            ->with('success', 'Materia prima actualizada exitosamente.');
    }

    // DELETE — Solo si ningun producto la usa en sus planos
    public function destroy(RawMaterial $material)
    {
        /*
         * Si el insumo esta en los planos de un articulo, borrarlo dejaria al
         * producto sin un material y romperia la clave foranea de
         * article_materials. Se avisa en vez de reventar.
         */
        $articulos = $material->articles()->pluck('article_name');

        if ($articulos->isNotEmpty()) {
            return redirect()->route('raw-materials.index')
                ->with('error', 'No se puede eliminar «' . $material->description . '»: esta en los planos de '
                    . $articulos->implode(', ') . '.');
        }

        $material->stock()->delete();
        $material->delete();

        return redirect()->route('raw-materials.index')
            ->with('success', 'Materia prima eliminada exitosamente.');
    }

    /**
     * Consulta comun a la pantalla y al PDF.
     */
    private function consultaFiltrada(Request $request)
    {
        $query = RawMaterial::query();

        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('supplier')) {
            $query->where('id_supplier', $request->supplier);
        }

        if ($request->filled('unit')) {
            $query->where('unit', $request->unit);
        }

        if ($request->filled('low_stock') && $request->low_stock == '1') {
            $query->whereHas('stock', function ($q) {
                $q->whereColumn('remaining_quantity', '<=', 'minimum_stock');
            });
        }

        return $query;
    }
}
