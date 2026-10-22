<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use Barryvdh\DomPDF\Facade\Pdf;

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
     *
     * El listado mezcla personal y clientes, asi que se puede filtrar por
     * rol (y por tipo: personal, clientes o todos). El filtro se hace con
     * el id_role de `users`, que es el rol real del usuario.
     */
    public function index(Request $request)
    {
        $this->authorizeAdmin($request);

        $query = User::with('roles')->latest();

        // Filtro por un rol puntual (el select de la vista).
        if ($request->filled('id_role')) {
            $query->where('id_role', $request->id_role);
        }

        // Filtro por tipo: solo personal, solo clientes o todos.
        // El criterio es el ROL del usuario, que es la fuente de verdad de
        // quien es personal y quien compra.
        if ($request->filled('tipo')) {
            if ($request->tipo === 'personal') {
                $query->esPersonal();
            } elseif ($request->tipo === 'clientes') {
                $query->esCliente();
            }
        }

        // Busqueda por nombre o email.
        if ($request->filled('search')) {
            $busqueda = $request->search;
            $query->where(function ($q) use ($busqueda) {
                $q->where('name', 'like', "%{$busqueda}%")
                  ->orWhere('email', 'like', "%{$busqueda}%");
            });
        }

        $users = $query->paginate(10)->withQueryString();

        $roles = Role::orderBy('role_name')->get();

        return view('users.index', compact('users', 'roles'));
    }

    /**
     * Alta de usuario.
     *
     * Sin esta pantalla el alta de personal quedaba en banda muerta: el
     * formulario de empleados solo ofrece usuarios que NO sean empleados ni
     * clientes, y no habia ninguna forma de crear un usuario nuevo. Con
     * todos los usuarios ya ocupados, el desplegable quedaba vacio.
     */
    public function create(Request $request)
    {
        $this->authorizeAdmin($request);

        $roles = Role::orderBy('role_name')->get();

        return view('users.create', compact('roles'));
    }

    /**
     * Guarda el usuario nuevo.
     *
     * Si el rol es Cliente, ademas se crea la fila en `customers`: sin ella
     * el cliente no tendria carrito ni historial de compras, porque toda la
     * logica de compras se apoya en `customers.id_user`.
     */
    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

        $rol = Role::where('id_role', $validated['id_role'])->firstOrFail();

        /*
         * `users.id_role` es NOT NULL, asi que el rol tiene que estar en el
         * INSERT, no se puede crear el usuario y asignarle el rol despues.
         *
         * id_role no esta en $fillable a proposito (para que nadie se lo
         * asigne por mass assignment), asi que va con forceFill.
         */
        $user = new User();
        $user->forceFill([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => $validated['password'],
            'id_role'  => $rol->id_role,
        ])->save();

        // asignarRol() deja el espejo en Spatie (model_has_roles), que es
        // lo que leen @hasrole y el middleware role:.
        $user->asignarRol($rol->role_name);

        // Ficha de cliente, solo si el rol es Cliente.
        if ($rol->role_name === 'Cliente') {
            Customer::updateOrCreate(
                ['id_user' => $user->id_user],
                [
                    'first_name' => $validated['first_name'],
                    'last_name'  => $validated['last_name'],
                    'phone'      => $validated['phone'] ?? null,
                    'address'    => $validated['address'] ?? null,
                ]
            );
        }

        return redirect()->route('users.index')
            ->with('success', "Usuario '{$user->name}' creado con el rol {$rol->role_name}.");
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

        /*
         * Se busca el rol por id y se delega en asignarRol(), que escribe
         * users.id_role Y el espejo en Spatie (model_has_roles).
         *
         * Antes se usaba syncRoles() de Spatie contra la tabla `spatie_roles`,
         * que estaba vacia: se sincronizaba contra una tabla sin los roles
         * del sistema y `users.id_role` nunca se actualizaba.
         */
        $rol = Role::findOrFail($validated['id_role']);

        $user->asignarRol($rol->role_name);

        /*
         * Si el usuario pasa a ser Cliente y no tiene ficha, se le crea una
         * minima con su nombre. Sin la fila en `customers` el cliente
         * entra al sistema pero no puede comprar: no tiene carrito ni
         * historial, porque toda la logica de compras se apoya en
         * `customers.id_user`.
         *
         * Se delega en el modelo: la misma regla la usan el registro
         * publico y el carrito.
         */
        if ($rol->role_name === 'Cliente') {
            $user->asegurarFichaCliente();
        }

        return redirect()
            ->route('users.roles.edit', $user->id_user)
            ->with('success', 'Roles actualizados correctamente.');
    }

    /**
     * Reporte de usuarios en PDF.
     *
     * La ruta GET /usuarios/pdf estaba registrada pero este método no
     * existía, así que la ruta devolvía HTTP 500. Los otros tres módulos
     * (artículos, empleados, proveedores) sí lo tienen.
     */
    public function exportPdf(Request $request)
    {
        $this->authorizeAdmin($request);

        $query = User::with(['role', 'employee']);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('id_role')) {
            $query->where('id_role', $request->id_role);
        }

        $users = $query->latest('id_user')->get();

        $pdf = Pdf::loadView('users.pdf', compact('users'));

        return $pdf->download('reporte_usuarios.pdf');
    }

    /*
     * Los métodos siguientes quedan vacíos a propósito: no tienen rutas ni
     * vistas asociadas. Si se implementan, hay que agregarle validación
     * y autorización como en los de arriba.
     *
     * OJO: `create` y `store` ya no están vacíos: están implementados más
     * arriba, para el alta de usuario.
     */

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
