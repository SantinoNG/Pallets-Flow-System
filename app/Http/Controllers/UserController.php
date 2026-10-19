<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Solo los administradores pueden ver o tocar la gestión de roles.
     *
     * Antes estas rutas tenian únicamente middleware('auth'), así que
     * cualquier usuario logueado podía llamar /usuarios/{cualquiera}/roles
     * y ascenderse a sí mismo.
     */
    private function authorizeAdmin(Request $request): void
    {
        abort_unless(
            $request->user()?->isAdmin(),
            403,
            'Solo un administrador puede gestionar los roles de los usuarios.'
        );
    }

    /**
     * Listado de usuarios.
     */
    public function index(Request $request)
    {
        $this->authorizeAdmin($request);

        $users = User::latest()->paginate(10);

        return view('users.index', compact('users'));
    }

    /**
     * Muestra el formulario para asignar un rol a un usuario.
     *
     * Usa App\Models\Role (tabla `roles`, PK `id_role`, columna `role_name`),
     * que es el sistema de roles real del proyecto.
     *
     * Antes importaba Spatie\Permission\Models\Role, que lee `spatie_roles`
     * (tabla vacía), por lo que la página se renderizaba con 0 checkboxes.
     */
    public function editRoles(Request $request, User $user)
    {
        $this->authorizeAdmin($request);

        $roles = Role::orderBy('role_name')->get();

        return view('users.roles', compact('user', 'roles'));
    }

    /**
     * Guarda el rol seleccionado.
     *
     * users.id_role es una FK simple: cada usuario tiene UN solo rol.
     * Por eso el formulario es un <select>, no checkboxes.
     *
     * Antes usaba syncRoles() de Spatie sin validar nada, lo que sincronizaba
     * contra spatie_roles (vacía) y contra model_has_roles.
     */
    public function updateRoles(Request $request, User $user)
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'id_role' => ['required', 'integer', 'exists:roles,id_role'],
        ]);

        // id_role no está en $fillable a propósito (es un campo que solo debe
        // cambiar desde aquí), por eso se usa forceFill.
        $user->forceFill(['id_role' => $validated['id_role']])->save();

        return redirect()
            ->route('users.roles.edit', $user->id_user)
            ->with('success', 'Roles actualizados correctamente.');
    }

    /*
     * Los métodos siguientes quedan vacíos a propósito: no tienen rutas ni
     * vistas asociadas. Si se implementan, hay que agregarle validación
     * y autorización como en los de arriba.
     */

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }
}
