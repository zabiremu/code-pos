<x-layouts.admin :title="'Modifier Groups'">
    <div class="card divide-y mb-6">
        @foreach ($groups as $group)
            <div class="px-5 py-3">
                <div class="flex items-center justify-between">
                    <p class="font-medium">{{ $group->name }} <span class="text-xs text-zinc-400">(max {{ $group->max_selectable }}{{ $group->is_required ? ', required' : '' }})</span></p>
                    <form method="POST" action="{{ route('admin.modifier-groups.destroy', $group) }}" onsubmit="return confirm('Delete this group?')">
                        @csrf @method('DELETE')
                        <button class="btn-ghost">Delete</button>
                    </form>
                </div>
                <p class="text-xs text-zinc-500 mt-1">
                    {{ $group->modifiers->map(fn ($m) => $m->name.' (+'.number_format($m->price_delta, 2).')')->join(', ') }}
                </p>
            </div>
        @endforeach
    </div>

    <form method="POST" action="{{ route('admin.modifier-groups.store') }}" class="card p-5 space-y-3" x-data="{ modifiers: [{ name: '', price_delta: 0 }] }">
        @csrf
        <div class="flex gap-3">
            <input type="text" name="name" placeholder="Group name (e.g. Add-ons)" required class="input flex-1">
            <input type="number" name="max_selectable" value="1" min="1" class="input w-24">
            <label class="text-sm flex items-center gap-1"><input type="checkbox" name="is_required" value="1"> Required</label>
        </div>

        <template x-for="(mod, i) in modifiers" :key="i">
            <div class="flex gap-3">
                <input type="text" :name="'modifiers['+i+'][name]'" x-model="mod.name" placeholder="Modifier name" class="input flex-1">
                <input type="number" step="0.01" :name="'modifiers['+i+'][price_delta]'" x-model="mod.price_delta" class="input w-28">
            </div>
        </template>
        <button type="button" @click="modifiers.push({ name: '', price_delta: 0 })" class="text-sm text-zinc-500 hover:text-primary-600 transition-colors">+ Add another modifier</button>

        <button class="block btn-primary">Save group</button>
    </form>
</x-layouts.admin>
