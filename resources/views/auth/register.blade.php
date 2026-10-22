<x-guest-layout>
    <div class="min-h-screen flex flex-col lg:flex-row">

        <!-- Lado Izquierdo: Branding (mismo bloque que el login) -->
        <div class="hidden lg:flex lg:w-1/2 bg-zinc-900 text-white flex-col justify-between p-12 relative overflow-hidden">
            <div class="z-10 flex items-center gap-3">
                <x-app-logo class="h-11 w-11 shrink-0" />
                <span class="font-bold text-2xl tracking-wide">Pallets<span class="text-hazard-500">Flow</span></span>
            </div>

            <div class="z-10 space-y-4 my-auto">
                <h1 class="text-4xl font-extrabold leading-tight">Creá tu cuenta de cliente</h1>
                <p class="text-zinc-400 text-sm max-w-md">Con tu cuenta podés armar tu carrito, hacer tus compras y revisar tu historial de comprobantes.</p>
            </div>

            <p class="z-10 text-xs text-zinc-500 dark:text-zinc-300">© 2026 Pallets Flow System. Todos los derechos reservados.</p>

            <div class="absolute -bottom-20 -left-20 w-80 h-80 bg-hazard-600/10 rounded-full blur-3xl"></div>
        </div>

        <!-- Lado Derecho: Formulario -->
        <div class="w-full lg:w-1/2 min-h-screen flex items-center justify-center p-6 sm:p-12 bg-zinc-50 dark:bg-zinc-950">
            <div class="w-full max-w-md space-y-6 bg-white dark:bg-zinc-900 p-8 rounded-2xl shadow-lg border border-gray-100 dark:border-zinc-800">

                <!-- Encabezado Móvil con Logo -->
                <div class="flex items-center gap-2.5 lg:hidden mb-4">
                    <x-app-logo class="h-9 w-9 shrink-0" />
                    <span class="font-bold text-xl text-zinc-900 dark:text-zinc-100">Pallets<span class="text-hazard-600">Flow</span></span>
                </div>

                <div>
                    <h2 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">Crear cuenta</h2>
                    <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">Registrate como cliente para comprar en Pallets Flow.</p>
                </div>

                <form method="POST" action="{{ route('register') }}" class="space-y-4">
                    @csrf

                    <!-- Nombre y Apellido -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="first_name" class="block text-xs font-bold text-gray-700 dark:text-zinc-200 uppercase mb-1">Nombre</label>
                            <input id="first_name" type="text" name="first_name" value="{{ old('first_name') }}" required autofocus autocomplete="given-name"
                                   class="w-full text-sm rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-hazard-500 focus:ring-hazard-500 px-3.5 py-2.5">
                            <x-input-error :messages="$errors->get('first_name')" class="mt-1" />
                        </div>

                        <div>
                            <label for="last_name" class="block text-xs font-bold text-gray-700 dark:text-zinc-200 uppercase mb-1">Apellido</label>
                            <input id="last_name" type="text" name="last_name" value="{{ old('last_name') }}" required autocomplete="family-name"
                                   class="w-full text-sm rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-hazard-500 focus:ring-hazard-500 px-3.5 py-2.5">
                            <x-input-error :messages="$errors->get('last_name')" class="mt-1" />
                        </div>
                    </div>

                    <!-- Correo Electrónico -->
                    <div>
                        <label for="email" class="block text-xs font-bold text-gray-700 dark:text-zinc-200 uppercase mb-1">Correo Electrónico</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                               class="w-full text-sm rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-hazard-500 focus:ring-hazard-500 px-3.5 py-2.5">
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>

                    <!-- Contraseña -->
                    <div>
                        <label for="password" class="block text-xs font-bold text-gray-700 dark:text-zinc-200 uppercase mb-1">Contraseña</label>
                        <input id="password" type="password" name="password" required autocomplete="new-password"
                               class="w-full text-sm rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-hazard-500 focus:ring-hazard-500 px-3.5 py-2.5">
                        <p class="mt-1 text-[11px] text-gray-500 dark:text-zinc-400">Mínimo 8 caracteres.</p>
                        <x-input-error :messages="$errors->get('password')" class="mt-1" />
                    </div>

                    <!-- Repetir Contraseña -->
                    <div>
                        <label for="password_confirmation" class="block text-xs font-bold text-gray-700 dark:text-zinc-200 uppercase mb-1">Repetir Contraseña</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                               class="w-full text-sm rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-hazard-500 focus:ring-hazard-500 px-3.5 py-2.5">
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full bg-hazard-600 hover:bg-hazard-700 text-white font-bold py-3 rounded-lg shadow-md transition text-xs uppercase tracking-wider">
                            Crear Cuenta
                        </button>
                    </div>
                </form>

                <!-- Volver al login -->
                <div class="pt-4 border-t border-gray-100 dark:border-zinc-800 text-center">
                    <p class="text-xs text-gray-500 dark:text-zinc-400">
                        ¿Ya tenés una cuenta?
                        <a href="{{ route('login') }}" class="text-hazard-600 hover:text-hazard-700 font-bold">Iniciá sesión</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
