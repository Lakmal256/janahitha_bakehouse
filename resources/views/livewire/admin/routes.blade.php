<div class="mx-auto max-w-7xl space-y-8 px-4 py-8 text-[#f3f0e7] sm:px-6 lg:px-8">
    <header>
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#aab0b3]">Administration</p>
        <h1 class="mt-2 text-3xl font-bold">Routes & drivers</h1>
        <p class="mt-2 text-sm text-[#c3c7c9]">The five paper sheets are separate delivery routes. A route with assigned shops cannot be deleted.</p>
    </header>

    @if (session('status')) <p role="status" class="rounded-lg border border-[#41474b] bg-[#222629] px-4 py-3 text-sm">{{ session('status') }}</p> @endif

    <section class="rounded-xl border border-[#41474b] bg-[#222629] p-5 sm:p-6">
        <h2 class="text-xl font-semibold">{{ $editingId ? 'Rename route or change driver' : 'Add route' }}</h2>
        <form wire:submit="save" class="mt-4 grid gap-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
            <label class="block text-sm font-medium">Route name
                <input wire:model="name" required class="mt-1 min-h-11 w-full rounded-lg border border-[#51595d] bg-[#141618] px-3 text-[#f3f0e7]">
                @error('name') <span class="mt-1 block text-sm text-red-300">{{ $message }}</span> @enderror
            </label>
            <label class="block text-sm font-medium">Driver
                <input wire:model="driver" class="mt-1 min-h-11 w-full rounded-lg border border-[#51595d] bg-[#141618] px-3 text-[#f3f0e7]">
                @error('driver') <span class="mt-1 block text-sm text-red-300">{{ $message }}</span> @enderror
            </label>
            <div class="flex gap-2">
                <button class="min-h-11 rounded-lg bg-[#f3f0e7] px-4 font-semibold text-[#191b1d]">{{ $editingId ? 'Save changes' : 'Add route' }}</button>
                @if ($editingId)
                    <button type="button" wire:click="cancelEdit" class="min-h-11 rounded-lg border border-[#51595d] px-4">Cancel</button>
                @endif
            </div>
        </form>
    </section>

    @error('delete') <p role="alert" class="rounded-lg border border-red-900 bg-red-950/50 px-4 py-3 text-sm text-red-200">{{ $message }}</p> @enderror
    <section class="overflow-hidden rounded-xl border border-[#41474b] bg-[#222629]">
        @foreach ($routes as $route)
            <article wire:key="route-{{ $route->id }}" class="flex flex-wrap items-center justify-between gap-4 border-b border-[#41474b] px-4 py-4 last:border-b-0">
                <div>
                    <h2 class="font-semibold">{{ $route->name }}</h2>
                    <p class="text-sm text-[#aab0b3]">{{ $route->driver ?: 'No driver assigned' }} · {{ $route->shops_count }} shops</p>
                </div>
                <div class="flex gap-2">
                    <button type="button" wire:click="edit({{ $route->id }})" class="min-h-10 rounded-lg border border-[#51595d] px-4 text-sm">Edit</button>
                    <button type="button" wire:click="delete({{ $route->id }})" wire:confirm="Delete this empty route?" @disabled($route->shops_count > 0) class="min-h-10 rounded-lg border border-[#51595d] px-4 text-sm disabled:cursor-not-allowed disabled:opacity-40">Delete</button>
                </div>
            </article>
        @endforeach
    </section>
</div>
