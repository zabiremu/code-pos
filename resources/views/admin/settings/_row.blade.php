{{-- One label/field row, same form-table shape as the staff profile page. --}}
<div class="grid grid-cols-1 sm:grid-cols-[9rem_1fr] gap-2 sm:gap-4 py-5 border-t border-zinc-100">
    <div>
        <label for="{{ $for }}" class="text-sm font-medium text-zinc-700">{{ $label }}</label>
        @isset($hint)
            <p class="text-xs text-zinc-400 mt-1">{{ $hint }}</p>
        @endisset
    </div>
    <div>
        {!! $field !!}
        @error($for)
            <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
        @enderror
    </div>
</div>
