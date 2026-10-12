<div class="mx-auto max-w-7xl space-y-7 px-4 py-6 text-[#f3f0e7] sm:px-6 sm:py-8 lg:px-8">
    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#aab0b3]">Administration</p>
            <h1 class="mt-2 text-3xl font-bold">Shop setup</h1>
            <p class="mt-2 max-w-2xl text-sm text-[#c3c7c9]">Set each shop’s regular items, bill order, and optional fixed prices. Leave a price blank to use the item default.</p>
        </div>
        <div class="rounded-lg border border-[#41474b] bg-[#222629] px-4 py-3 text-sm">
            <span class="font-semibold">{{ $shops->count() }}</span><span class="text-[#aab0b3]"> accessible shops</span>
        </div>
    </header>

    @if (session('status')) <p role="status" class="rounded-lg border border-[#41474b] bg-[#222629] px-4 py-3 text-sm">{{ session('status') }}</p> @endif

    <section class="rounded-xl border border-[#41474b] bg-[#222629] p-4 sm:p-5">
        <h2 class="text-lg font-semibold">Add a shop</h2>
        <form wire:submit="createShop" class="mt-3 grid gap-3 sm:grid-cols-[minmax(0,1fr)_minmax(220px,0.7fr)_auto] sm:items-end">
            <label class="block text-sm font-medium">Shop name
                <input wire:model="newShopName" required class="mt-1 min-h-11 w-full rounded-lg border border-[#51595d] bg-[#141618] px-3 text-[#f3f0e7]">
                @error('newShopName') <span class="mt-1 block text-sm text-red-300">{{ $message }}</span> @enderror
            </label>
            <label class="block text-sm font-medium">Delivery route
                <select wire:model="newShopRouteId" required class="mt-1 min-h-11 w-full rounded-lg border border-[#51595d] bg-[#141618] px-3 text-[#f3f0e7]">
                    <option value="">Select route</option>
                    @foreach ($routes as $route)
                        <option value="{{ $route->id }}">{{ $route->name }}{{ $route->driver ? ' · '.$route->driver : '' }}</option>
                    @endforeach
                </select>
                @error('newShopRouteId') <span class="mt-1 block text-sm text-red-300">{{ $message }}</span> @enderror
            </label>
            <button class="min-h-11 rounded-lg bg-[#f3f0e7] px-4 font-semibold text-[#191b1d]">Create shop</button>
        </form>
    </section>

    <div class="grid gap-6 lg:grid-cols-[300px_minmax(0,1fr)]">
        <aside class="space-y-4">
            <div class="rounded-xl border border-[#41474b] bg-[#222629] p-4">
                <label for="shop-picker" class="text-sm font-semibold">Step 1 · Select shop</label>
                <select id="shop-picker" wire:model.live="shopId" class="mt-2 min-h-12 w-full rounded-lg border border-[#51595d] bg-[#141618] px-3 text-[#f3f0e7]">
                    @foreach ($shops as $shop)
                        @php($usesDefaults = $shop->items->isEmpty() || $shop->items->contains(fn ($item) => $item->pivot->price === null))
                        <option value="{{ $shop->id }}">{{ $shop->route->name }} · {{ $shop->name }}{{ $usesDefaults ? ' · Default prices' : '' }}</option>
                    @endforeach
                </select>
                @error('shopId') <span class="mt-1 block text-sm text-red-300">{{ $message }}</span> @enderror
                <button type="button" wire:click="startFromFirstShop" class="mt-3 min-h-11 w-full rounded-lg border border-[#51595d] px-4 text-sm font-semibold hover:bg-[#2b3033]">Start from first shop</button>
            </div>

            <div class="overflow-hidden rounded-xl border border-[#41474b] bg-[#222629]">
                <div class="border-b border-[#41474b] px-4 py-3 text-xs font-semibold uppercase tracking-wide text-[#aab0b3]">Shop setup status</div>
                <div class="max-h-[50vh] overflow-y-auto">
                    @foreach ($shops as $shop)
                        <button type="button" wire:key="shop-select-{{ $shop->id }}" wire:click="$set('shopId', {{ $shop->id }})" class="flex min-h-14 w-full items-center justify-between gap-2 border-b border-[#41474b] px-4 text-left last:border-b-0 {{ $shop->id === $shopId ? 'bg-[#343a3d]' : 'hover:bg-[#2b3033]' }}">
                            <span class="min-w-0"><span class="block truncate text-sm font-medium">{{ $shop->name }}</span><span class="block truncate text-xs text-[#aab0b3]">{{ $shop->route->name }}</span></span>
                            <span class="shrink-0 rounded-full border border-[#51595d] px-2 py-1 text-[10px]">{{ $shop->items->isEmpty() || $shop->items->contains(fn ($item) => $item->pivot->price === null) ? 'Default' : 'Fixed' }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </aside>

        <section class="min-w-0 space-y-5">
            @if ($shopId)
                <div class="rounded-xl border border-[#41474b] bg-[#222629] p-4 sm:p-6">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block text-sm font-medium">Shop name
                            <input wire:model="shopName" required class="mt-1 min-h-11 w-full rounded-lg border border-[#51595d] bg-[#141618] px-3 text-[#f3f0e7]">
                            @error('shopName') <span class="mt-1 block text-sm text-red-300">{{ $message }}</span> @enderror
                        </label>
                        <label class="block text-sm font-medium">Delivery route
                            <select wire:model="routeId" class="mt-1 min-h-11 w-full rounded-lg border border-[#51595d] bg-[#141618] px-3 text-[#f3f0e7]">
                                @foreach ($routes as $route)
                                    <option value="{{ $route->id }}">{{ $route->name }}{{ $route->driver ? ' · '.$route->driver : '' }}</option>
                                @endforeach
                            </select>
                            @error('routeId') <span class="mt-1 block text-sm text-red-300">{{ $message }}</span> @enderror
                        </label>
                    </div>
                </div>

                <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(300px,0.8fr)]">
                    <section class="rounded-xl border border-[#41474b] bg-[#222629] p-4 sm:p-6">
                        <div class="flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#aab0b3]">Step 2</p>
                                <h2 class="mt-1 text-xl font-semibold">Usual items</h2>
                            </div>
                            <span class="text-sm text-[#aab0b3]">{{ count($selectedItemIds) }} selected</span>
                        </div>
                        @error('selectedItemIds.*') <p class="mt-3 text-sm text-red-300">{{ $message }}</p> @enderror
                        <div class="mt-4 grid gap-2 sm:grid-cols-2">
                            @foreach ($items as $item)
                                @php($checked = in_array($item->id, array_map('intval', $selectedItemIds), true))
                                <label wire:key="catalog-item-{{ $item->id }}" class="flex min-h-12 items-center gap-3 rounded-lg border border-[#41474b] px-3 text-sm hover:bg-[#2b3033]">
                                    <input type="checkbox" @checked($checked) wire:change="toggleItem({{ $item->id }}, $event.target.checked)" class="h-5 w-5 accent-[#f3f0e7]">
                                    <span class="flex-1">{{ $item->name }}</span>
                                    <span class="text-xs text-[#aab0b3]">Default Rs. {{ number_format((float) $item->default_price, 2) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </section>

                    <section class="rounded-xl border border-[#41474b] bg-[#222629] p-4 sm:p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#aab0b3]">Step 3</p>
                        <h2 class="mt-1 text-xl font-semibold">Bill order & fixed prices</h2>
                        <p class="mt-2 text-sm text-[#aab0b3]">The order here is the order shown when entering this shop’s quantities.</p>

                        @if (count($itemOrder))
                            <ol class="mt-4 space-y-2">
                                @foreach ($itemOrder as $index => $itemId)
                                    @if (isset($orderedItems[$itemId]))
                                        <li wire:key="ordered-{{ $shopId }}-{{ $itemId }}" class="grid grid-cols-[28px_minmax(0,1fr)_110px_auto] items-center gap-2 rounded-lg border border-[#41474b] bg-[#191b1d] p-2">
                                            <span class="text-center text-xs text-[#aab0b3]">{{ $index + 1 }}</span>
                                            <span class="min-w-0 truncate text-sm font-medium">{{ $orderedItems[$itemId]->name }}</span>
                                            <label class="text-[10px] text-[#aab0b3]">Fixed Rs.
                                                <input wire:model="itemPrices.{{ $itemId }}" type="number" min="0" step="0.01" placeholder="Default" class="mt-1 min-h-10 w-full rounded-md border border-[#51595d] bg-[#141618] px-2 text-right text-sm text-[#f3f0e7]">
                                                @error("itemPrices.$itemId") <span class="block text-xs text-red-300">{{ $message }}</span> @enderror
                                            </label>
                                            <div class="flex gap-1">
                                                <button type="button" wire:click="moveItem({{ $itemId }}, -1)" aria-label="Move {{ $orderedItems[$itemId]->name }} up" @disabled($index === 0) class="grid h-10 w-10 place-items-center rounded-md border border-[#51595d] text-lg disabled:opacity-30">↑</button>
                                                <button type="button" wire:click="moveItem({{ $itemId }}, 1)" aria-label="Move {{ $orderedItems[$itemId]->name }} down" @disabled($index === count($itemOrder) - 1) class="grid h-10 w-10 place-items-center rounded-md border border-[#51595d] text-lg disabled:opacity-30">↓</button>
                                            </div>
                                        </li>
                                    @endif
                                @endforeach
                            </ol>
                        @else
                            <p class="mt-4 rounded-lg border border-dashed border-[#51595d] p-5 text-center text-sm text-[#aab0b3]">Select the usual items for this shop from the list.</p>
                        @endif
                    </section>
                </div>

                <section class="rounded-xl border border-[#41474b] bg-[#222629] p-4 sm:p-5">
                    <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
                        <label class="block text-sm font-medium">Copy items, bill order, and prices from another shop
                            <select wire:model="copyShopId" class="mt-1 min-h-11 w-full rounded-lg border border-[#51595d] bg-[#141618] px-3 text-[#f3f0e7]">
                                <option value="">Choose a shop</option>
                                @foreach ($shops as $shop)
                                    @if ($shop->id !== $shopId)
                                        <option value="{{ $shop->id }}">{{ $shop->route->name }} · {{ $shop->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                            @error('copyShopId') <span class="mt-1 block text-sm text-red-300">{{ $message }}</span> @enderror
                        </label>
                        <button type="button" wire:click="copyFrom" class="min-h-11 rounded-lg border border-[#51595d] px-4 font-medium hover:bg-[#2b3033]">Copy from shop</button>
                    </div>
                </section>

                <div class="sticky bottom-2 z-10 flex flex-wrap gap-2 rounded-xl border border-[#41474b] bg-[#141618]/95 p-3 shadow-xl backdrop-blur">
                    <button type="button" wire:click="save" class="min-h-12 flex-1 rounded-lg border border-[#51595d] px-4 font-semibold hover:bg-[#2b3033]">Save shop</button>
                    <button type="button" wire:click="saveAndNext" class="min-h-12 flex-1 rounded-lg bg-[#f3f0e7] px-4 font-semibold text-[#191b1d] hover:bg-white">Save & next</button>
                    <button type="button" wire:click="applyPricesToAllShops" wire:confirm="Apply these fixed prices to the other accessible shops that sell these selected items?" class="min-h-12 basis-full rounded-lg border border-[#51595d] px-4 text-sm font-medium hover:bg-[#2b3033] sm:basis-auto">Apply these prices to all shops</button>
                </div>
            @else
                <div class="rounded-xl border border-dashed border-[#51595d] bg-[#222629] p-10 text-center text-[#c3c7c9]">No shops are assigned to your accessible routes.</div>
            @endif
        </section>
    </div>
</div>
