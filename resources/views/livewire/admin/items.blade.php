<div class="mx-auto max-w-7xl space-y-8 px-4 py-8 text-[#f3f0e7] sm:px-6 lg:px-8">
    <header>
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#aab0b3]">Administration</p>
        <h1 class="mt-2 text-3xl font-bold">Items & default prices</h1>
        <p class="mt-2 text-sm text-[#c3c7c9]">A default price is used only when a shop has no fixed price for that item.</p>
    </header>

    @if (session('status')) <p role="status" class="rounded-lg border border-[#41474b] bg-[#222629] px-4 py-3 text-sm">{{ session('status') }}</p> @endif
    @error('delete') <p role="alert" class="rounded-lg border border-red-900 bg-red-950/50 px-4 py-3 text-sm text-red-200">{{ $message }}</p> @enderror

    <section class="rounded-xl border border-[#41474b] bg-[#222629] p-5 sm:p-6">
        <h2 class="text-xl font-semibold">Add item</h2>
        <form wire:submit="add" class="mt-4 grid gap-4 sm:grid-cols-[1fr_180px_auto] sm:items-end">
            <label class="block text-sm font-medium">Item name
                <input wire:model="name" required class="mt-1 min-h-11 w-full rounded-lg border border-[#51595d] bg-[#141618] px-3 text-[#f3f0e7]">
                @error('name') <span class="mt-1 block text-sm text-red-300">{{ $message }}</span> @enderror
            </label>
            <label class="block text-sm font-medium">Default price (Rs.)
                <input wire:model="defaultPrice" type="number" min="0" step="0.01" required class="mt-1 min-h-11 w-full rounded-lg border border-[#51595d] bg-[#141618] px-3 text-right text-[#f3f0e7]">
                @error('defaultPrice') <span class="mt-1 block text-sm text-red-300">{{ $message }}</span> @enderror
            </label>
            <button class="min-h-11 rounded-lg bg-[#f3f0e7] px-4 font-semibold text-[#191b1d]">Add item</button>
        </form>
    </section>

    <section class="overflow-hidden rounded-xl border border-[#41474b] bg-[#222629]">
        <div class="hidden grid-cols-[1fr_180px_auto] gap-4 border-b border-[#41474b] bg-[#2b3033] px-4 py-3 text-xs font-semibold uppercase tracking-wide text-[#c3c7c9] sm:grid"><span>Item</span><span class="text-right">Default price</span><span>Actions</span></div>
        @foreach ($items as $item)
            <article wire:key="item-{{ $item->id }}" class="grid gap-3 border-b border-[#41474b] p-4 last:border-b-0 sm:grid-cols-[1fr_180px_auto] sm:items-start sm:gap-4">
                <label class="block text-sm font-medium">Item name
                    <input wire:model="edits.{{ $item->id }}.name" class="mt-1 min-h-11 w-full rounded-lg border border-[#51595d] bg-[#141618] px-3 text-[#f3f0e7]">
                    @error("edits.$item->id.name") <span class="mt-1 block text-sm text-red-300">{{ $message }}</span> @enderror
                </label>
                <label class="block text-sm font-medium">Default price (Rs.)
                    <input wire:model="edits.{{ $item->id }}.default_price" type="number" min="0" step="0.01" class="mt-1 min-h-11 w-full rounded-lg border border-[#51595d] bg-[#141618] px-3 text-right text-[#f3f0e7]">
                    @error("edits.$item->id.default_price") <span class="mt-1 block text-sm text-red-300">{{ $message }}</span> @enderror
                </label>
                <div class="flex gap-2 pt-0 sm:pt-6">
                    <button type="button" wire:click="save({{ $item->id }})" class="min-h-11 rounded-lg border border-[#51595d] px-4 text-sm">Save</button>
                    <button type="button" wire:click="delete({{ $item->id }})" wire:confirm="Delete this unused item?" class="min-h-11 rounded-lg border border-[#51595d] px-4 text-sm">Delete</button>
                </div>
            </article>
        @endforeach
    </section>
</div>
