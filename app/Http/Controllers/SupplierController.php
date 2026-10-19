<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\RawMaterial;
use App\Models\RawMaterialStock;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class SupplierController extends Controller
{
    // READ — Lista todos los proveedores aplicando filtros de búsqueda
    public function index(Request $request)
    {
        $query = Supplier::with('rawMaterials');

        // Filtro por nombre de empresa
        if ($request->filled('company_name')) {
            $query->where('company_name', 'like', '%' . $request->company_name . '%');
        }

        // Filtro por materia prima (busca en la tabla relacionada)
        if ($request->filled('raw_material')) {
            $query->whereHas('rawMaterials', function ($q) use ($request) {
                $q->where('description', 'like', '%' . $request->raw_material . '%');
            });
        }

        $suppliers = $query->paginate(10)->withQueryString();

        return view('suppliers.index', compact('suppliers'));
    }

    // EXPORTAR PDF — Reutiliza la lógica de filtrado para el documento
    public function exportPdf(Request $request)
    {
        $query = Supplier::with('rawMaterials');

        if ($request->filled('company_name')) {
            $query->where('company_name', 'like', '%' . $request->company_name . '%');
        }

        if ($request->filled('raw_material')) {
            $query->whereHas('rawMaterials', function ($q) use ($request) {
                $q->where('description', 'like', '%' . $request->raw_material . '%');
            });
        }

        $suppliers = $query->get(); // Obtenemos todos los resultados filtrados sin paginar

        $pdf = Pdf::loadView('suppliers.pdf', compact('suppliers'));
        
        return $pdf->download('reporte_proveedores.pdf');
    }

    // CREATE paso 1 — Muestra formulario vacío
    public function create()
    {
        return view('suppliers.create');
    }

    // CREATE paso 2 — DSD-6: crea Supplier + RawMaterials + RawMaterialStock
    public function store(Request $request)
    {
        $request->validate([
            'company_name'   => 'required|string|max:255|unique:suppliers,company_name',
            'supplier_email' => 'required|email|max:255|unique:suppliers,supplier_email',
            'supplier_phone' => 'required|string|max:20',
            'materials'      => 'required|array|min:1',
            'materials.*'    => 'required|string|max:255',
        ]);

        $supplier = new Supplier();
        $supplier->company_name   = $request->company_name;
        $supplier->supplier_email = $request->supplier_email;
        $supplier->supplier_phone = $request->supplier_phone;
        $supplier->save();

        foreach ($request->materials as $description) {
            if (empty(trim($description))) continue;

            $rawMaterial = RawMaterial::where('description', trim($description))->first();

            if ($rawMaterial) {
                $rawMaterial->id_supplier = $supplier->id_supplier;
                $rawMaterial->save();
            } else {
                $rawMaterial = new RawMaterial();
                $rawMaterial->description  = trim($description);
                $rawMaterial->id_supplier  = $supplier->id_supplier;
                $rawMaterial->save();

                $stock = new RawMaterialStock();
                $stock->id_raw_material    = $rawMaterial->id_raw_material;
                $stock->remaining_quantity = 0;
                $stock->minimum_stock      = 0;
                $stock->save();
            }
        }

        return redirect()->route('suppliers.index')
                         ->with('success', 'Proveedor registrado exitosamente.');
    }

    // UPDATE paso 1 — Muestra formulario con datos actuales
    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    // UPDATE paso 2 — Actualiza datos del proveedor
    public function update(Request $request, Supplier $supplier)
    {
        $request->validate([
            'company_name'   => 'required|string|max:255|unique:suppliers,company_name,' . $supplier->id_supplier . ',id_supplier',
            'supplier_email' => 'required|email|max:255|unique:suppliers,supplier_email,' . $supplier->id_supplier . ',id_supplier',
            'supplier_phone' => 'required|string|max:20',
        ]);

        $supplier->company_name   = $request->company_name;
        $supplier->supplier_email = $request->supplier_email;
        $supplier->supplier_phone = $request->supplier_phone;
        $supplier->save();

        return redirect()->route('suppliers.index')
                         ->with('success', 'Proveedor actualizado exitosamente.');
    }

    // DELETE — Elimina el proveedor
    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return redirect()->route('suppliers.index')
                         ->with('success', 'Proveedor eliminado exitosamente.');
    }
}