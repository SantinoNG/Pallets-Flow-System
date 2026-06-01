<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        
        <title>{{ config('app.name', 'Sistema de Gestión') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    
    <body class="bg-gray-50 text-gray-800 font-sans antialiased min-h-screen flex flex-col">
        
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                
                <div class="flex items-center gap-3">
                    <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    <span class="font-bold text-xl text-gray-900 tracking-tight">SG Institucional</span>
                </div>

                @if (Route::has('login'))
                    <nav class="flex items-center gap-4">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="text-sm font-semibold text-gray-700 hover:text-indigo-600 transition">
                                Ir al Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-semibold text-gray-700 hover:text-indigo-600 transition">
                                Ingresar
                            </a>

                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="text-sm font-semibold bg-indigo-600 text-white px-4 py-2 rounded-md shadow hover:bg-indigo-700 transition">
                                    Registrarse
                                </a>
                            @endif
                        @endauth
                    </nav>
                @endif
            </div>
        </header>

        <main class="flex-grow flex items-center justify-center p-6">
            <div class="max-w-4xl w-full bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
                <div class="p-8 sm:p-16 text-center">
                    
                    <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 tracking-tight mb-4">
                        Bienvenido al <span class="text-indigo-600">Sistema de Gestión</span>
                    </h1>
                    
                    <p class="text-lg text-gray-600 mb-10 max-w-2xl mx-auto">
                        Plataforma centralizada y segura para la administración integral de usuarios, roles, permisos y recursos de la institución.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-left mt-8">
                        
                        <div class="bg-gray-50 p-6 rounded-xl border border-gray-100 hover:shadow-md transition">
                            <div class="text-2xl mb-3">👥</div>
                            <h3 class="font-bold text-gray-900 text-lg">Gestión de Usuarios</h3>
                            <p class="text-sm text-gray-500 mt-2">Administración completa del personal, directivos y alumnos en un solo lugar.</p>
                        </div>
                        
                        <div class="bg-gray-50 p-6 rounded-xl border border-gray-100 hover:shadow-md transition">
                            <div class="text-2xl mb-3">🔐</div>
                            <h3 class="font-bold text-gray-900 text-lg">Control de Accesos</h3>
                            <p class="text-sm text-gray-500 mt-2">Seguridad avanzada basada en roles de Spatie y permisos específicos por área.</p>
                        </div>
                        
                        <div class="bg-gray-50 p-6 rounded-xl border border-gray-100 hover:shadow-md transition">
                            <div class="text-2xl mb-3">📊</div>
                            <h3 class="font-bold text-gray-900 text-lg">Trazabilidad</h3>
                            <p class="text-sm text-gray-500 mt-2">Registro detallado de actividades y auditoría de movimientos del sistema.</p>
                        </div>

                    </div>
                </div>
            </div>
        </main>

        <footer class="bg-white border-t border-gray-200 py-6 mt-auto">
            <div class="max-w-7xl mx-auto px-4 text-center text-sm text-gray-500">
                &copy; {{ date('Y') }} Sistema de Gestión Institucional. Todos los derechos reservados.
            </div>
        </footer>

    </body>
</html>