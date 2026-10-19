<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role as SpatieRole;

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

        $rol = Role::create($request->only('role_name', 'description'));

        // Espejo en `spatie_roles` para que el rol exista en la capa de
        // autorizacion (@hasrole, middleware role:). Si no, un rol creado
        // desde la UI no se podria usar para autorizar nada.
        SpatieRole::findOrCreate($rol->role_name, 'web');

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

        $nombreAnterior = $role->role_name;

        $role->update($request->only('role_name', 'description'));

        // Si cambio el nombre, hay que mover tambien el espejo de Spatie.
        if ($nombreAnterior !== $role->role_name) {
            $spatie = SpatieRole::where('name', $nombreAnterior)
                ->where('guard_name', 'web')
                ->first();

            if ($spatie !== null) {
                $usuarios = $spatie->users()->get();

                $spatie->update(['name' => $role->role_name]);

                // Los usuarios que tenian ese rol siguen teniéndolo, ahora
                // con el nombre nuevo.
                foreach ($usuarios as $usuario) {
                    $usuario->asignarRol($role->role_name);
                }
            }
        }

        return redirect()->route('roles.index')
                         ->with('success', 'Rol actualizado exitosamente.');
    }

    // DELETE — Elimina el rol
    public function destroy(Role $role)
    {
        /*
         * users.id_role es NOT NULL con Foreign Key a roles.id_role, asi que
         * un rol que alguien tenga asignado no se puede borrar (la base lo
         * rechazaria con un error de FK). En vez de dejar que reviente con
         * un 500, se avisa y se pide reasignar primero.
         */
        $usuarios = User::where('id_role', $role->id_role)->get();

        if ($usuarios->isNotEmpty()) {
            $nombres = $usuarios->pluck('email')->implode(', ');

            return redirect()->route('roles.index')
                ->with('error', "No se puede eliminar el rol '{$role->role_name}': "
                    . "{$usuarios->count()} usuario(s) lo tienen asignados ({$nombres}). "
                    . 'Reasignales otro rol primero.');
        }

        $nombre = $role->role_name;

        $role->delete();

        // Y se borra el espejo de Spatie.
        $spatie = SpatieRole::where('name', $nombre)->where('guard_name', 'web')->first();

        if ($spatie !== null) {
            $spatie->delete();
        }

        return redirect()->route('roles.index')
                         ->with('success', 'Rol eliminado exitosamente.');
    }
}
