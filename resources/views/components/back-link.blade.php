@props(['href'])
<div class="mb-5">
    <a href="{{ $href }}" class="text-sm text-zinc-500 hover:text-primary-600">&larr; {{ $slot }}</a>
</div>
