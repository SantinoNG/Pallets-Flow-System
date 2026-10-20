@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'py-1 bg-white dark:bg-zinc-900'])

@php
$alignmentClasses = match ($align) {
    'left' => 'l-0',
    'center' => 'l-1/2 -translate-x-1/2',
    default => 'right-0',
};
@endphp

<div class="relative" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false">
    <div x-ref="button" @click="open = ! open" class="inline-flex">
        {{ $trigger }}
    </div>

    <div x-show="open"
         style="display: none;"
         class="absolute z-50 mt-2 {{ $width }} rounded-md shadow-lg {{ $alignmentClasses }}"
         @click.outside="open = false">
        <div class="rounded-md ring-1 ring-black ring-opacity-5 dark:ring-white/10 {{ $contentClasses }}">
            {{ $slot }}
        </div>
    </div>
</div>
