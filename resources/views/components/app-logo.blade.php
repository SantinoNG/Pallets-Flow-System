{{--
    Logo de la aplicación.

    Es un <img> a un SVG en public/images/, no un SVG inline: así el archivo
    se puede reemplazar por el logo propio (o por un PNG) sin tocar el
    código, y queda cacheado por el navegador.

    Hay DOS archivos de marca, y cada uno va en su lugar:

    - `icono-pallet.svg`: el glifo de un pallet. Es el que va acá, en el
      sidebar y en el login, donde el espacio es de 32 o 44 pixels. El logo
      completo no se lee a ese tamaño: el texto "PALLETS FLOW" se vuelve
      una mancha.
    - `logo-marca.svg`: el logo de marca entero (monograma "PF" con el
      pallet y la hoja). Va donde hay espacio: los comprobantes en PDF y la
      marca de agua.

    La versión (?v=...) es la fecha de modificación del archivo: si se
    cambia el logo, el navegador lo vuelve a pedir en vez de mostrar el
    viejo.

    Uso: <x-app-logo />            (tamaño por defecto)
         <x-app-logo class="h-10 w-10" />
--}}
@props(['class' => 'h-8 w-8'])

@php
    $archivo = public_path('images/icono-pallet.svg');
    $version = is_file($archivo) ? filemtime($archivo) : '1';
@endphp

<img
    src="{{ asset('images/icono-pallet.svg') }}?v={{ $version }}"
    alt="Pallets Flow - Diseño Natural"
    {{ $attributes->merge(['class' => "rounded-md {$class}"]) }}>
