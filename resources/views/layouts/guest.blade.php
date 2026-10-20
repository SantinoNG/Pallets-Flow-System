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
    </head>
    <body class="font-sans text-zinc-900 dark:text-zinc-100 antialiased bg-zinc-100 dark:bg-zinc-950 transition-colors">
        <div class="min-h-screen">
            <div class="absolute top-4 right-4 z-20">
                <x-theme-toggle />
            </div>
            {{ $slot }}
        </div>
    </body>
</html>
