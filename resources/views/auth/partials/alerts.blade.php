@if (session('status'))
    <p class="status" role="status">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" style="flex:none;margin-top:1px"><circle cx="12" cy="12" r="9"/><path d="m8 12.5 2.5 2.5L16 9.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span>{{ session('status') }}</span>
    </p>
@endif

@if ($errors->any())
    <p class="error" role="alert">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" style="flex:none;margin-top:1px"><circle cx="12" cy="12" r="9"/><path d="M12 7.5v5.5M12 16.5h.01" stroke-linecap="round"/></svg>
        <span>{{ $errors->first() }}</span>
    </p>
@endif
