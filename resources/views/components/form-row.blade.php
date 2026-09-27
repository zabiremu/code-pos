{{-- Label/field row in the app's form-table shape (Staff, Settings, Suppliers). --}}
@props(['label', 'for', 'hint' => null, 'required' => false, 'error' => null])
@php $errorKey = $error ?? $for; @endphp
<div {{ $attributes->merge(['class' => 'grid grid-cols-1 sm:grid-cols-[10rem_1fr] gap-2 sm:gap-4 py-5 border-t border-zinc-100 first:border-t-0']) }}>
    <div>
        <label for="{{ $for }}" class="text-sm font-medium text-zinc-700">
            {{ $label }}@if ($required)<span class="text-primary-600" aria-hidden="true"> *</span>@endif
        </label>
        @if ($hint)
            <p class="text-xs text-zinc-400 mt-1">{{ $hint }}</p>
        @endif
    </div>
    <div>
        {{ $slot }}
        @error($errorKey)
            <p class="text-xs text-primary-600 mt-1.5">{{ $message }}</p>
        @enderror
    </div>
</div>
