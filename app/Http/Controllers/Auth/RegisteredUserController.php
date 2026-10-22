<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Registro publico: cualquiera puede crearse una cuenta de CLIENTE.
 *
 * El rol esta fijo en el codigo y no viene del formulario: si llegara del
 * request, cualquiera podria registrarse eligiendo Administrador.
 *
 * El registro crea DOS filas, siempre juntas:
 *
 * - `users`: la cuenta con la que se inicia sesion.
 * - `customers`: la ficha del cliente.
 *
 * La ficha no es opcional ni decorativa: es la que enlaza el carrito y el
 * historial de compras (`cart.id_customer`, `sales.id_customer`). Antes el
 * registro solo creaba `users`, y el cliente nuevo entraba a un sistema
 * donde no podia comprar nada.
 */
class RegisteredUserController extends Controller
{
    /**
     * Muestra el formulario de registro.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Crea la cuenta y la ficha de cliente.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        /*
         * Nombre y apellido van separados porque la ficha del cliente
         * (`customers.first_name` / `customers.last_name`) los necesita
         * separados: en el listado de clientes y en los comprobantes se
         * usan por separado. En `users.name` se guarda el nombre completo.
         */
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],
            'email'      => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password'   => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'first_name.required' => 'El nombre es obligatorio.',
            'last_name.required'  => 'El apellido es obligatorio.',
            'email.required'      => 'El correo electronico es obligatorio.',
            'email.email'         => 'El correo electronico no es valido.',
            'email.unique'        => 'Ya existe una cuenta con ese correo electronico.',
            'password.required'   => 'La contrasena es obligatoria.',
            'password.min'        => 'La contrasena debe tener al menos 8 caracteres.',
            'password.confirmed'  => 'Las dos contrasenas no coinciden.',
        ]);

        /*
         * El email se guarda en minusculas: si alguien se registra como
         * "Pepe@Mail.com" y despues entra como "pepe@mail.com", tiene que
         * encontrar la misma cuenta. La regla `lowercase` de Laravel no
         * sirve aca: rechaza el alta en vez de normalizar.
         */
        $email = mb_strtolower(trim($validated['email']));

        /*
         * `users.id_role` es NOT NULL con Foreign Key a roles.id_role, asi
         * que un INSERT sin esa columna revienta. `id_role` esta fuera del
         * $fillable a proposito (para que nadie se lo asigne por mass
         * assignment), por eso el rol se pone con forceFill y `asignarRol()`
         * es la que hace el save, ya con la columna puesta.
         */
        $user = new User();
        $user->forceFill([
            'name'     => trim($validated['first_name'].' '.$validated['last_name']),
            'email'    => $email,
            'password' => Hash::make($validated['password']),
        ]);

        // Rol minimo: Cliente. asignarRol() valida que exista en el catalogo.
        $user->asignarRol('Cliente');

        // La ficha del cliente, con el nombre ya separado del formulario.
        $user->asegurarFichaCliente([
            'first_name' => $validated['first_name'],
            'last_name'  => $validated['last_name'],
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
