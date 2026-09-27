@props(['colspan', 'search' => '', 'clearHref' => null, 'createHref' => null, 'noun', 'createLabel' => null])
<tr>
    <td colspan="{{ $colspan }}" class="px-5 py-14 text-center">
        @if ($search !== '')
            <p class="text-zinc-600">No {{ $noun }} match "{{ $search }}".</p>
            @if ($clearHref)<a href="{{ $clearHref }}" class="text-sm text-primary-600 hover:underline underline-offset-4 mt-1 inline-block">Clear search</a>@endif
        @else
            <p class="text-zinc-600">No {{ $noun }} yet.</p>
            @if ($createHref)<a href="{{ $createHref }}" class="text-sm text-primary-600 hover:underline underline-offset-4 mt-1 inline-block">{{ $createLabel }}</a>@endif
        @endif
    </td>
</tr>
