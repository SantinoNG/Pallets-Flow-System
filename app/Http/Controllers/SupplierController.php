<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\RawMaterial;
use App\Models\RawMaterialStock;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    // READ — Lista todos los proveedores con sus materias primas
    public function index()
    {
        $suppliers = Supplier::with('rawMaterials')->get();
        return view('suppliers.index', compact('suppliers'));
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

        // Paso 2 del DSD-6: crear y guardar Supplier
        $supplier = new Supplier();
        $supplier->company_name   = $request->company_name;
        $supplier->supplier_email = $request->supplier_email;
        $supplier->supplier_phone = $request->supplier_phone;
        $supplier->save();

        // Paso 3-5 del DSD-6: por cada materia prima buscar o crear + stock
        foreach ($request->materials as $description) {
            if (empty(trim($description))) continue;

            // Paso 4 del DSD-6: buscar si ya existe la materia prima
            $rawMaterial = RawMaterial::where('description', trim($description))->first();

            if ($rawMaterial) {
                // Si existe: actualizar su proveedor
                $rawMaterial->id_supplier = $supplier->id_supplier;
                $rawMaterial->save();
            } else {
                // Si no existe: crear nueva materia prima
                $rawMaterial = new RawMaterial();
                $rawMaterial->description  = trim($description);
                $rawMaterial->id_supplier  = $supplier->id_supplier;
                $rawMaterial->save();

                // Paso 5 del DSD-6: crear stock inicial en 0
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