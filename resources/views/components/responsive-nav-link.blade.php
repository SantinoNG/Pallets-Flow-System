@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-3 py-2 border-l-4 border-hazard-500 text-sm font-medium leading-5 text-gray-900 dark:text-zinc-100 focus:outline-none transition duration-150 ease-in-out'
            : 'inline-flex items-center px-3 py-2 border-l-4 border-transparent text-sm font-medium leading-5 text-gray-500 dark:text-zinc-400 hover:text-gray-700 dark:hover:text-zinc-200 hover:border-gray-300 dark:hover:border-zinc-700 focus:outline-none transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
