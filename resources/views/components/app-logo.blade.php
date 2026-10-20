{{--
    Logo de la aplicación.

    Es un <img> a un SVG en public/images/logo.svg, no un SVG inline: así el
    archivo se puede reemplazar por uno propio (o por un PNG) sin tocar el
    código, y queda cacheado por el navegador.

    Uso: <x-app-logo />            (tamaño por defecto)
         <x-app-logo class="h-10 w-10" />
--}}
@props(['class' => 'h-8 w-8'])

<img
    src="{{ asset('images/logo.svg') }}"
    alt="PalletStore"
    {{ $attributes->merge(['class' => $class]) }}
>
