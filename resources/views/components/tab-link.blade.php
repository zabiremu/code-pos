@props(['href', 'active' => false, 'count' => null])
<a href="{{ $href }}" @if ($active) aria-current="page" @endif
   class="px-3 py-2 -mb-px border-b-2 transition-colors whitespace-nowrap {{ $active ? 'border-primary-600 text-primary-700 font-medium' : 'border-transparent text-zinc-500 hover:text-zinc-800' }}">
    {{ $slot }}@if ($count !== null) <span class="text-zinc-400">({{ $count }})</span>@endif
</a>
