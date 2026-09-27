{{-- Inline delete with a confirm prompt. $variant "link" for row actions, "danger" for a full button. --}}
@props(['action', 'confirm', 'label' => 'Delete', 'variant' => 'link'])
<form method="POST" action="{{ $action }}" onsubmit="return confirm('{{ addslashes($confirm) }}')" class="inline">
    @csrf
    @method('DELETE')
    @if ($variant === 'danger')
        <button class="btn bg-white text-primary-700 ring-1 ring-inset ring-primary-200 hover:bg-primary-50 focus:outline-none focus:ring-2 focus:ring-primary-500">{{ $label }}</button>
    @else
        <button class="text-zinc-500 hover:text-primary-600">{{ $label }}</button>
    @endif
</form>
