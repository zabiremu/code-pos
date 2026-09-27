{{-- A labelled on/off setting that always submits (hidden 0 + checkbox 1). --}}
@props(['label', 'name', 'checked' => false, 'text', 'hint' => null])
<x-form-row :label="$label" :for="$name" :hint="$hint">
    <label class="inline-flex items-center gap-2 text-sm text-zinc-700">
        <input type="hidden" name="{{ $name }}" value="0">
        <input id="{{ $name }}" type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $checked))
               class="rounded border-zinc-300 text-primary-600 focus:ring-primary-500">
        {{ $text }}
    </label>
</x-form-row>
