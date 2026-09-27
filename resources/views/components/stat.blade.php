@props(['label', 'value', 'hint' => null, 'tone' => null])
<div {{ $attributes->merge(['class' => 'card p-5']) }}>
    <p class="text-sm text-zinc-500">{{ $label }}</p>
    <p class="font-display text-3xl mt-1 tabular-nums {{ $tone === 'bad' ? 'text-primary-700' : ($tone === 'good' ? 'text-emerald-700' : '') }}">{{ $value }}</p>
    @if ($hint)<p class="text-xs text-zinc-500 mt-1">{{ $hint }}</p>@endif
</div>
