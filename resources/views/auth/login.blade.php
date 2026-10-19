<x-guest-layout>
    <div class="min-h-screen flex flex-col lg:flex-row">
        
        <!-- Lado Izquierdo: Branding (Visible en pantallas medianas y grandes) -->
        <div class="hidden lg:flex lg:w-1/2 bg-slate-900 text-white flex-col justify-between p-12 relative overflow-hidden">
            <div class="z-10 flex items-center gap-3">
                <div class="bg-amber-600 text-white p-2.5 rounded-xl font-bold">
                    <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zM7 7h10v2H7zm0 4h10v2H7zm0 4h7v2H7z"/>
                    </svg>
                </div>
                <span class="font-bold text-2xl tracking-wide">Pallet<span class="text-amber-500">Store</span></span>
            </div>

            <div class="z-10 space-y-4 my-auto">
                <h1 class="text-4xl font-extrabold leading-tight">Sistema de Gestión & Muebles de Pallets</h1>
                <p class="text-slate-400 text-sm max-w-md">Control de producción, inventario de materias primas y administración centralizada.</p>
            </div>

            <p class="z-10 text-xs text-slate-500">© 2026 PalletStore System. Todos los derechos reservados.</p>
            
            <div class="absolute -bottom-20 -left-20 w-80 h-80 bg-amber-600/10 rounded-full blur-3xl"></div>
        </div>

        <!-- Lado Derecho: Formulario de Login -->
        <div class="w-full lg:w-1/2 min-h-screen flex items-center justify-center p-6 sm:p-12 bg-slate-50">
            <div class="w-full max-w-md space-y-6 bg-white p-8 rounded-2xl shadow-lg border border-gray-100">
                
                <!-- Encabezado Móvil con Logo -->
                <div class="flex items-center gap-2.5 lg:hidden mb-4">
                    <div class="bg-amber-600 text-white p-2 rounded-lg font-bold">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zM7 7h10v2H7zm0 4h10v2H7zm0 4h7v2H7z"/>
                        </svg>
                    </div>
                    <span class="font-bold text-xl text-slate-900">Pallet<span class="text-amber-600">Store</span></span>
                </div>

                <div>
                    <h2 class="text-2xl font-bold text-slate-900">¡Bienvenido de nuevo!</h2>
                    <p class="text-xs text-gray-500 mt-1">Ingresa tus credenciales para acceder al sistema.</p>
                </div>

                <!-- Estado de Sesión -->
                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf

                    <!-- Correo Electrónico -->
                    <div>
                        <label for="email" class="block text-xs font-bold text-gray-700 uppercase mb-1">Correo Electrónico</label>
                        <input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" 
                               class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 px-3.5 py-2.5">
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>

                    <!-- Contraseña -->
                    <div>
                        <label for="password" class="block text-xs font-bold text-gray-700 uppercase mb-1">Contraseña</label>
                        <input id="password" type="password" name="password" required autocomplete="current-password"
                               class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 px-3.5 py-2.5">
                        <x-input-error :messages="$errors->get('password')" class="mt-1" />
                    </div>

                    <!-- Recordarme y Olvidé Contraseña -->
                    <div class="flex items-center justify-between text-xs pt-1">
                        <label for="remember_me" class="inline-flex items-center">
                            <input id="remember_me" type="checkbox" name="remember" class="rounded border-gray-300 text-amber-600 shadow-sm focus:ring-amber-500">
                            <span class="ms-2 text-gray-600">Recordarme</span>
                        </label>
                        @if (Route::has('password.request'))
                            <a class="text-amber-600 hover:text-amber-700 font-semibold" href="{{ route('password.request') }}">
                                ¿Olvidaste tu contraseña?
                            </a>
                        @endif
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full bg-amber-600 hover:bg-amber-700 text-white font-bold py-3 rounded-lg shadow-md transition text-xs uppercase tracking-wider">
                            Iniciar Sesión
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>