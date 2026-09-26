{{-- Shared create/edit fields, in the same label/field row shape as Staff and Settings. --}}
@php
    $rows = [
        ['name', 'Contact name', 'text', 'The person you deal with.', true],
        ['company_name', 'Company', 'text', 'Leave blank if they trade under their own name.', false],
        ['phone', 'Phone', 'tel', null, false],
        ['email', 'Email', 'email', null, false],
        ['tax_number', 'VAT / BIN / TIN', 'text', 'Printed on their invoices.', false],
    ];
@endphp

<div class="px-5">
    @foreach ($rows as [$field, $label, $type, $hint, $required])
        <div class="grid grid-cols-1 sm:grid-cols-[10rem_1fr] gap-2 sm:gap-4 py-5 border-t border-zinc-100 first:border-t-0">
            <div>
                <label for="{{ $field }}" class="text-sm font-medium text-zinc-700">
                    {{ $label }}@if ($required)<span class="text-primary-600" aria-hidden="true"> *</span>@endif
                </label>
                @if ($hint)
                    <p class="text-xs text-zinc-400 mt-1">{{ $hint }}</p>
                @endif
            </div>
            <div>
                <input id="{{ $field }}" type="{{ $type }}" name="{{ $field }}" value="{{ old($field, $supplier->$field) }}"
                       @if ($required) required @endif maxlength="150"
                       class="w-full input @error($field) !border-primary-500 @enderror"
                       @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>
                @error($field)
                    <p id="{{ $field }}-error" class="text-xs text-primary-600 mt-1.5">{{ $message }}</p>
                @enderror
            </div>
        </div>
    @endforeach

    @foreach ([['address', 'Address', 3], ['notes', 'Notes', 3]] as [$field, $label, $rowsCount])
        <div class="grid grid-cols-1 sm:grid-cols-[10rem_1fr] gap-2 sm:gap-4 py-5 border-t border-zinc-100">
            <div>
                <label for="{{ $field }}" class="text-sm font-medium text-zinc-700">{{ $label }}</label>
                @if ($field === 'notes')
                    <p class="text-xs text-zinc-400 mt-1">Payment terms, delivery days, anything worth remembering.</p>
                @endif
            </div>
            <div>
                <textarea id="{{ $field }}" name="{{ $field }}" rows="{{ $rowsCount }}" class="w-full input">{{ old($field, $supplier->$field) }}</textarea>
                @error($field)
                    <p class="text-xs text-primary-600 mt-1.5">{{ $message }}</p>
                @enderror
            </div>
        </div>
    @endforeach

    <div class="grid grid-cols-1 sm:grid-cols-[10rem_1fr] gap-2 sm:gap-4 py-5 border-t border-zinc-100">
        <div><span class="text-sm font-medium text-zinc-700">Status</span></div>
        <div>
            <label class="inline-flex items-center gap-2 text-sm text-zinc-700">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="rounded border-zinc-300 text-primary-600 focus:ring-primary-500"
                       @checked(old('is_active', $supplier->is_active))>
                Active
            </label>
            <p class="text-xs text-zinc-400 mt-1">Inactive suppliers stay on record but drop to the bottom of the list.</p>
        </div>
    </div>
</div>
