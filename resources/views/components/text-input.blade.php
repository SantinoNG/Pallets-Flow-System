@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 dark:border-zinc-700 focus:border-hazard-500 focus:ring-hazard-500 rounded-md shadow-sm']) }}>
