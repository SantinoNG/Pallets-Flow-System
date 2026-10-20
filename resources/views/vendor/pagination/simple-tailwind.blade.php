@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex gap-2 items-center justify-between">

        @if ($paginator->onFirstPage())
            <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-600 dark:text-zinc-300 dark:text-zinc-500 bg-white dark:bg-zinc-900 border border-gray-300 dark:border-zinc-700 cursor-not-allowed leading-5 rounded-md">
                {!! __('pagination.previous') !!}
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-800 dark:text-zinc-100 dark:text-zinc-200 bg-white dark:bg-zinc-900 border border-gray-300 dark:border-zinc-700 leading-5 rounded-md hover:text-gray-700 dark:hover:text-zinc-100 focus:outline-none focus:ring ring-gray-300 dark:ring-zinc-700 focus:border-hazard-500 active:bg-gray-100 dark:active:bg-zinc-800 active:text-gray-800 dark:active:text-zinc-100 transition ease-in-out duration-150 dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-200 dark:focus:border-hazard-600 dark:active:bg-zinc-700 dark:active:text-zinc-100 hover:bg-gray-100 dark:hover:bg-zinc-800 dark:hover:bg-zinc-900 dark:hover:text-zinc-200">
                {!! __('pagination.previous') !!}
            </a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-800 dark:text-zinc-100 dark:text-zinc-200 bg-white dark:bg-zinc-900 border border-gray-300 dark:border-zinc-700 leading-5 rounded-md hover:text-gray-700 dark:hover:text-zinc-100 focus:outline-none focus:ring ring-gray-300 dark:ring-zinc-700 focus:border-hazard-500 active:bg-gray-100 dark:active:bg-zinc-800 active:text-gray-800 dark:active:text-zinc-100 transition ease-in-out duration-150 dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-200 dark:focus:border-hazard-600 dark:active:bg-zinc-700 dark:active:text-zinc-100 hover:bg-gray-100 dark:hover:bg-zinc-800 dark:hover:bg-zinc-900 dark:hover:text-zinc-200">
                {!! __('pagination.next') !!}
            </a>
        @else
            <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-600 dark:text-zinc-300 dark:text-zinc-500 bg-white dark:bg-zinc-900 border border-gray-300 dark:border-zinc-700 cursor-not-allowed leading-5 rounded-md">
                {!! __('pagination.next') !!}
            </span>
        @endif

    </nav>
@endif
