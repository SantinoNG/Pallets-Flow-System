{{--
    Imagen de un artículo.

    Si el artículo tiene foto, la muestra. Si no, dibuja un placeholder
    industrial: fondo con trama diagonal (como el papel de dibujo técnico),
    un glifo de pallet y la inicial del producto.

    El placeholder es un SVG inline justamente para no depender de ningún
    archivo: los artículos del catálogo ya existen y ninguno tiene foto.

    Uso:
        <x-article-image :article="$articulo" size="sm" />
--}}
@props(['article', 'size' => 'sm', 'class' => ''])

@php
    $tamanos = [
        'sm' => ['caja' => 'h-12 w-12 min-w-12', 'fuente' => 'text-lg', 'glifo' => 'h-6 w-6'],
        'md' => ['caja' => 'h-20 w-20 min-w-20', 'fuente' => 'text-2xl', 'glifo' => 'h-10 w-10'],
        'lg' => ['caja' => 'h-40 w-full min-w-40', 'fuente' => 'text-5xl', 'glifo' => 'h-20 w-20'],
    ];

    $t = $tamanos[$size] ?? $tamanos['sm'];

    // El artículo puede no existir: en las ventas, si se borró del catálogo
    // después de venderse, el item queda sin relación.
    $nombre  = $article->article_name ?? 'Artículo eliminado';
    $inicial = mb_strtoupper(mb_substr($nombre, 0, 1));
    $url     = $article?->imagen_url ?? null;
@endphp

@if ($url)
    <img
        src="{{ $url }}"
        alt="{{ $nombre }}"
        loading="lazy"
        {{ $attributes->merge(['class' => "object-cover rounded-md border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 {$t['caja']} {$class}"]) }}
    >
@else
    <div
        {{ $attributes->merge(['class' => "relative flex items-center justify-center overflow-hidden rounded-md border border-zinc-200 dark:border-zinc-800 bg-zinc-100 dark:bg-zinc-800 {$t['caja']} {$class}"]) }}
        title="{{ $nombre }}"
    >
        {{-- Trama diagonal: fondo de plano técnico --}}
        <span class="absolute inset-0 hatch-industrial" aria-hidden="true"></span>

        {{-- Glifo de pallet --}}
        <svg class="absolute text-hazard-500 {{ $t['glifo'] }}" viewBox="0 0 48 48" fill="currentColor" aria-hidden="true">
            <rect x="4" y="9" width="40" height="5.5" rx="1"/>
            <rect x="4" y="17.5" width="40" height="5.5" rx="1"/>
            <rect x="7" y="26" width="8" height="12" rx="1" opacity=".45"/>
            <rect x="20" y="26" width="8" height="12" rx="1" opacity=".45"/>
            <rect x="33" y="26" width="8" height="12" rx="1" opacity=".45"/>
        </svg>

        {{-- Inicial del producto --}}
        <span class="relative font-mono font-bold text-zinc-400 dark:text-zinc-500 {{ $t['fuente'] }}">{{ $inicial }}</span>
    </div>
@endif
