<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    // READ — Lista todos los roles
    public function index()
    {
        $roles = Role::all();
        return view('roles.index', compact('roles'));
    }

    // CREATE paso 1 — Muestra el formulario vacío
    public function create()
    {
        return view('roles.create');
    }

    // CREATE paso 2 — Valida y guarda
    public function store(Request $request)
    {
        $request->validate([
            'role_name'   => 'required|string|max:255|unique:roles,role_name',
            'description' => 'nullable|string|max:255',
        ]);

        Role::create($request->only('role_name', 'description'));

        return redirect()->route('roles.index')
                         ->with('success', 'Rol creado exitosamente.');
    }

    // UPDATE paso 1 — Muestra el formulario con datos actuales
    public function edit(Role $role)
    {
        return view('roles.edit', compact('role'));
    }

    // UPDATE paso 2 — Valida y actualiza
    public function update(Request $request, Role $role)
    {
        $request->validate([
            'role_name'   => 'required|string|max:255|unique:roles,role_name,' . $role->id_role . ',id_role',
            'description' => 'nullable|string|max:255',
        ]);

        $role->update($request->only('role_name', 'description'));

        return redirect()->route('roles.index')
                         ->with('success', 'Rol actualizado exitosamente.');
    }

    // DELETE — Elimina el rol
    public function destroy(Role $role)
    {
        $role->delete();

        return redirect()->route('roles.index')
                         ->with('success', 'Rol eliminado exitosamente.');
    }
}