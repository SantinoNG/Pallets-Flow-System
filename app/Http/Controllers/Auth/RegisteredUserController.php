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
         * Se usa forceFill() y NO create() a proposito: `id_role` esta fuera
         * del $fillable del modelo (a posta, para que nadie pueda asignarse
         * un rol arbitrario con un mass assignment), asi que hay que setearlo
         * explicitamente. Con create() Laravel lo descarta en silencio y el
         * INSERT sale sin la columna.
         *
         * El rol esta hardcodeado aca y NO viene del formulario: si llegara
         * del request, cualquiera podria registrarse eligiendo Administrador.
         */
        $rolCliente = Role::where('role_name', 'Cliente')->first();

        if ($rolCliente === null) {
            // Roles vacio: es un error de configuracion, no del usuario.
            throw ValidationException::withMessages([
                'email' => 'No hay roles cargados en el sistema. Contacta al administrador.',
            ]);
        }

        $user = new User();
        $user->forceFill([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'id_role' => $rolCliente->id_role,
        ]);
        $user->save();

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
