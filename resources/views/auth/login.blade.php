<x-guest-layout>
    <div class="min-h-screen flex flex-col lg:flex-row">
        
        <!-- Lado Izquierdo: Branding (Visible en pantallas medianas y grandes) -->
        <div class="hidden lg:flex lg:w-1/2 bg-zinc-900 text-white flex-col justify-between p-12 relative overflow-hidden">
            <div class="z-10 flex items-center gap-3">
                <x-app-logo class="h-11 w-11 shrink-0" />
                <span class="font-bold text-2xl tracking-wide">Pallet<span class="text-hazard-500">Store</span></span>
            </div>

            <div class="z-10 space-y-4 my-auto">
                <h1 class="text-4xl font-extrabold leading-tight">Sistema de Gestión & Muebles de Pallets</h1>
                <p class="text-zinc-400 text-sm max-w-md">Control de producción, inventario de materias primas y administración centralizada.</p>
            </div>

            <p class="z-10 text-xs text-zinc-500 dark:text-zinc-300">© 2026 PalletStore System. Todos los derechos reservados.</p>
            
            <div class="absolute -bottom-20 -left-20 w-80 h-80 bg-hazard-600/10 rounded-full blur-3xl"></div>
        </div>

        <!-- Lado Derecho: Formulario de Login -->
        <div class="w-full lg:w-1/2 min-h-screen flex items-center justify-center p-6 sm:p-12 bg-zinc-50 dark:bg-zinc-950">
            <div class="w-full max-w-md space-y-6 bg-white dark:bg-zinc-900 p-8 rounded-2xl shadow-lg border border-gray-100 dark:border-zinc-800">
                
                <!-- Encabezado Móvil con Logo -->
                <div class="flex items-center gap-2.5 lg:hidden mb-4">
                    <x-app-logo class="h-9 w-9 shrink-0" />
                    <span class="font-bold text-xl text-zinc-900 dark:text-zinc-100">Pallet<span class="text-hazard-600">Store</span></span>
                </div>

                <div>
                    <h2 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">¡Bienvenido de nuevo!</h2>
                    <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">Ingresa tus credenciales para acceder al sistema.</p>
                </div>

                <!-- Estado de Sesión -->
                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf

                    <!-- Correo Electrónico -->
                    <div>
                        <label for="email" class="block text-xs font-bold text-gray-700 dark:text-zinc-200 uppercase mb-1">Correo Electrónico</label>
                        <input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" 
                               class="w-full text-sm rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-hazard-500 focus:ring-hazard-500 px-3.5 py-2.5">
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>

                    <!-- Contraseña -->
                    <div>
                        <label for="password" class="block text-xs font-bold text-gray-700 dark:text-zinc-200 uppercase mb-1">Contraseña</label>
                        <input id="password" type="password" name="password" required autocomplete="current-password"
                               class="w-full text-sm rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-hazard-500 focus:ring-hazard-500 px-3.5 py-2.5">
                        <x-input-error :messages="$errors->get('password')" class="mt-1" />
                    </div>

                    <!-- Recordarme y Olvidé Contraseña -->
                    <div class="flex items-center justify-between text-xs pt-1">
                        <label for="remember_me" class="inline-flex items-center">
                            <input id="remember_me" type="checkbox" name="remember" class="rounded border-gray-300 dark:border-zinc-700 text-hazard-600 shadow-sm focus:ring-hazard-500">
                            <span class="ms-2 text-gray-600 dark:text-zinc-300">Recordarme</span>
                        </label>
                        @if (Route::has('password.request'))
                            <a class="text-hazard-600 hover:text-hazard-700 font-semibold" href="{{ route('password.request') }}">
                                ¿Olvidaste tu contraseña?
                            </a>
                        @endif
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full bg-hazard-600 hover:bg-hazard-700 text-white font-bold py-3 rounded-lg shadow-md transition text-xs uppercase tracking-wider">
                            Iniciar Sesión
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>