<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        /*
         * users.id_role es NOT NULL con Foreign Key a roles.id_role, asi que
         * todo usuario nuevo necesita un rol. Antes este controller no lo
         * seteaba y el registro fallaba con:
         *
         *     SQLSTATE[HY000]: Field 'id_role' doesn't have a default value
         *
         * Se usa asignarRol(), que escribe users.id_role y el espejo en
         * Spatie a la vez. Con create() / forceFill() el campo se descartaba
         * en silencio (esta fuera del $fillable) y el INSERT salia sin la
         * columna.
         *
         * El rol esta hardcodeado aca y NO viene del formulario: si llegara
         * del request, cualquiera podria registrarse eligiendo Administrador.
         */
        $user = new User();
        $user->forceFill([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        /*
         * OJO: NO se llama save() aca. users.id_role es NOT NULL, asi que un
         * INSERT sin esa columna revienta con "NOT NULL constraint failed:
         * users.id_role". asignarRol() es la que hace el save, ya con
         * id_role puesto.
         *
         * Rol minimo: Cliente. asignarRol() valida que exista en el catalogo.
         */
        $user->asignarRol('Cliente');

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
