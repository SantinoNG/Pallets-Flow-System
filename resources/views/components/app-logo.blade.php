{{--
    Logo de la aplicación.

    Es un <img> a un SVG en public/images/logo.svg, no un SVG inline: así el
    archivo se puede reemplazar por el logo propio (o por un PNG) sin tocar
    el código, y queda cacheado por el navegador.

    La versión (?v=...) es la fecha de modificación del archivo: si se
    cambia el logo, el navegador lo vuelve a pedir en vez de mostrar el
    viejo.

    Uso: <x-app-logo />            (tamaño por defecto)
         <x-app-logo class="h-10 w-10" />
--}}
@props(['class' => 'h-8 w-8'])

@php
    $archivo = public_path('images/logo.svg');
    $version = is_file($archivo) ? filemtime($archivo) : '1';
@endphp

<img
    src="{{ asset('images/logo.svg') }}?v={{ $version }}"
    alt="PalletStore"
    {{ $attributes->merge(['class' => $class]) }}
>
