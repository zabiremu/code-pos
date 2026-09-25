{{--
    Laravel's own pagination markup, with the active-page accent swapped
    from its default indigo to this app's primary (red) so paginated pages
    (categories, menu items, orders) stay on-brand. Laravel automatically
    picks up this file instead of the package's built-in view because it
    lives at resources/views/vendor/pagination/tailwind.blade.php.
--}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between mt-4">
        <div class="flex justify-between flex-1 sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="btn-secondary opacity-50 cursor-default">{{ __('pagination.previous') }}</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="btn-secondary">{{ __('pagination.previous') }}</a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="btn-secondary">{{ __('pagination.next') }}</a>
            @else
                <span class="btn-secondary opacity-50 cursor-default">{{ __('pagination.next') }}</span>
            @endif
        </div>

        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-600">
                    {!! __('Showing') !!}
                    @if ($paginator->firstItem())
                        <span class="font-medium">{{ $paginator->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-medium">{{ $paginator->lastItem() }}</span>
                    @else
                        {{ $paginator->count() }}
                    @endif
                    {!! __('of') !!}
                    <span class="font-medium">{{ $paginator->total() }}</span>
                    {!! __('results') !!}
                </p>
            </div>

            <div>
                <span class="inline-flex rounded-lg shadow-sm isolate gap-0.5">
                    {{-- Previous --}}
                    @if ($paginator->onFirstPage())
                        <span class="rounded-l-lg px-3 py-2 text-sm text-gray-300 bg-white border border-gray-200 cursor-default" aria-disabled="true">
                            <span aria-hidden="true">&lsaquo;</span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" class="rounded-l-lg px-3 py-2 text-sm text-gray-500 bg-white border border-gray-200 hover:bg-gray-50 hover:text-primary-600 transition-colors" rel="prev">
                            <span aria-hidden="true">&lsaquo;</span>
                        </a>
                    @endif

                    {{-- Page Numbers --}}
                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span class="px-3 py-2 text-sm text-gray-400 bg-white border border-gray-200">{{ $element }}</span>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page" class="px-3 py-2 text-sm font-semibold text-white bg-primary-600 border border-primary-600">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" class="px-3 py-2 text-sm text-gray-500 bg-white border border-gray-200 hover:bg-gray-50 hover:text-primary-600 transition-colors">{{ $page }}</a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" class="rounded-r-lg px-3 py-2 text-sm text-gray-500 bg-white border border-gray-200 hover:bg-gray-50 hover:text-primary-600 transition-colors" rel="next">
                            <span aria-hidden="true">&rsaquo;</span>
                        </a>
                    @else
                        <span class="rounded-r-lg px-3 py-2 text-sm text-gray-300 bg-white border border-gray-200 cursor-default" aria-disabled="true">
                            <span aria-hidden="true">&rsaquo;</span>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
