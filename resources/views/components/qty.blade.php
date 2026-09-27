{{-- Quantity without trailing zeros: 12.500 -> 12.5, 3.000 -> 3. --}}
@props(['value', 'unit' => null])
<span class="tabular-nums">{{ rtrim(rtrim(number_format((float) $value, 3, '.', ','), '0'), '.') }}</span>@if ($unit)<span class="text-zinc-400"> {{ $unit }}</span>@endif
