<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'PalletStore') }}</title>

        {{-- Tema: se aplica antes de pintar para que no haya destello --}}
        <script>
            (function () {
                try {
                    var tema = localStorage.getItem('tema');
                    var oscuro = tema
                        ? tema === 'dark'
                        : window.matchMedia('(prefers-color-scheme: dark)').matches;
                    if (oscuro) document.documentElement.classList.add('dark');
                } catch (e) {}
            })();
        </script>

        <!-- Fonts & Scripts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <!-- Chart.js CDN -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    </head>
    <body class="font-sans antialiased bg-zinc-100 dark:bg-zinc-950 text-zinc-800 dark:text-zinc-100 transition-colors">
        <div class="min-h-screen flex">
            
            <!-- SIDEBAR LATERAL (Izquierda) -->
            <aside class="w-64 bg-zinc-900 text-zinc-300 flex flex-col justify-between hidden md:flex min-h-screen sticky top-0 shadow-lg border-r border-zinc-800">
                <div>
                    <!-- Logo / Identidad -->
                    <div class="h-16 flex items-center px-6 bg-zinc-950 border-b border-zinc-800 gap-3">
                        <div class="bg-hazard-600 text-white p-2 rounded-md font-bold">
                            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zM7 7h10v2H7zm0 4h10v2H7zm0 4h7v2H7z"/></svg>
                        </div>
                        <span class="font-bold text-lg text-white tracking-wide">Pallet<span class="text-hazard-500">Store</span></span>
                    </div>

                    <!-- Navegación -->
                    <nav class="p-4 space-y-1.5 text-xs font-semibold">
                        <p class="px-3 text-[10px] uppercase text-zinc-500 font-bold tracking-wider mb-2">Panel de Control</p>
                        
                        <!-- Dashboard -->
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('dashboard') ? 'bg-hazard-600 text-white shadow-md' : 'hover:bg-zinc-800 hover:text-white' }}">
                            <svg class="w-4 h-4 fill-none stroke-current stroke-2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
                            <span>Dashboard</span>
                        </a>

                        <p class="px-3 text-[10px] uppercase text-zinc-500 font-bold tracking-wider mt-6 mb-2">Catálogo e Inventario</p>

                        <!-- Artículos -->
                        @role('Jefe|Administrador|Encargado|Deposito')
                        <a href="{{ route('articles.index') ?? '#' }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('articles.*') ? 'bg-hazard-600 text-white shadow-md' : 'hover:bg-zinc-800 hover:text-white' }}">
                            <svg class="w-4 h-4 fill-none stroke-current stroke-2" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            <span>Artículos</span>
                        </a>
                        @endrole

                        <!-- Proveedores -->
                        @role('Jefe|Administrador|Encargado')
                        <a href="{{ route('suppliers.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('suppliers.*') ? 'bg-hazard-600 text-white shadow-md' : 'hover:bg-zinc-800 hover:text-white' }}">
                            <svg class="w-4 h-4 fill-none stroke-current stroke-2" viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <span>Proveedores</span>
                        </a>
                        @endrole

                        <p class="px-3 text-[10px] uppercase text-zinc-500 font-bold tracking-wider mt-6 mb-2">Administración</p>

                        <!-- Empleados -->
                        @role('Jefe|Administrador|Encargado')
                        <a href="{{ route('employees.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('employees.*') ? 'bg-hazard-600 text-white shadow-md' : 'hover:bg-zinc-800 hover:text-white' }}">
                            <svg class="w-4 h-4 fill-none stroke-current stroke-2" viewBox="0 0 24 24"><path d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <span>Empleados</span>
                        </a>
                        @endrole

                        <!-- Usuarios & Roles -->
                          @role('Jefe|Administrador')
                        <a href="{{ route('users.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('users.*') ? 'bg-hazard-600 text-white shadow-md' : 'hover:bg-zinc-800 hover:text-white' }}">
                            <svg class="w-4 h-4 fill-none stroke-current stroke-2" viewBox="0 0 24 24"><path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <span>Usuarios y Roles</span>
                        </a>
                          @endrole

                        <!-- Carrito -->
                        <a href="{{ route('cart.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('cart.*') ? 'bg-hazard-600 text-white shadow-md' : 'hover:bg-zinc-800 hover:text-white' }}">
                            <svg class="w-4 h-4 fill-none stroke-current stroke-2" viewBox="0 0 24 24"><path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <span>Carrito</span>
                        </a>
                        <!-- Ventas -->
                        @role('Jefe|Administrador|Encargado|Vendedor')
                        <a href="{{ route('sales.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('sales.*') ? 'bg-hazard-600 text-white shadow-md' : 'hover:bg-zinc-800 hover:text-white' }}">
                            <svg class="w-4 h-4 fill-none stroke-current stroke-2" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Ventas</span>
                        </a>
                        @endrole
                    </nav>
                </div>

                <!-- Usuario y Logout -->
                <div class="p-4 border-t border-zinc-800 flex items-center justify-between">
                    <div class="flex items-center gap-3 overflow-hidden">
                        <div class="w-8 h-8 rounded-full bg-hazard-600 flex items-center justify-center font-bold text-white text-xs">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                        <div class="truncate text-xs">
                            <p class="font-semibold text-white truncate">{{ Auth::user()->name }}</p>
                            <p class="text-zinc-500 text-[10px] truncate">{{ Auth::user()->email }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-zinc-400 hover:text-red-400 transition p-1" title="Cerrar sesión">
                            <svg class="w-5 h-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        </button>
                    </form>
                </div>
            </aside>

            <!-- ÁREA PRINCIPAL -->
            <div class="flex-1 flex flex-col min-w-0 overflow-hidden bg-zinc-100 dark:bg-zinc-950">
                <!-- Topbar Móvil / Contenedor Superior -->
                @if (isset($header))
                    <header class="bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-800 py-4 px-6 sm:px-8 shadow-sm flex items-center justify-between gap-4">
                        <div class="min-w-0">{{ $header }}</div>
                        <x-theme-toggle />
                    </header>
                @else
                    <div class="flex justify-end px-6 sm:px-8 pt-4">
                        <x-theme-toggle />
                    </div>
                @endif

                <!-- Contenido de las Vistas -->
                <main class="flex-1 p-6 sm:p-8 overflow-y-auto">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
