<?php

namespace App\Livewire\Admin;

use App\Models\Item;
use App\Models\Route as BakeryRoute;
use App\Models\Shop;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Shops extends Component
{
    public ?int $shopId = null;
    public string $shopName = '';
    public ?int $routeId = null;
    public string $newShopName = '';
    public ?int $newShopRouteId = null;
    public array $selectedItemIds = [];
    public array $itemPrices = [];
    public array $itemOrder = [];
    public ?int $copyShopId = null;

    public function mount(): void
    {
        Gate::authorize('shops');
        $firstShop = $this->visibleShops()->first();
        if ($firstShop) {
            $this->selectShop($firstShop->id);
        }
    }

    public function updatedShopId($shopId): void
    {
        Gate::authorize('shops');
        if ($shopId) {
            $this->selectShop((int) $shopId);
        } else {
            $this->resetEditor();
        }
    }

    public function startFromFirstShop(): void
    {
        Gate::authorize('shops');
        $firstShop = $this->visibleShops()->first();
        if (! $firstShop) {
            $this->addError('shopId', 'There are no shops assigned to your accessible routes yet.');
            return;
        }

        $this->selectShop($firstShop->id);
    }

    public function createShop(): void
    {
        Gate::authorize('shops');
        $validated = $this->validate([
            'newShopName' => ['required', 'string', 'max:160'],
            'newShopRouteId' => ['required', 'integer', 'exists:routes,id'],
        ]);

        $route = BakeryRoute::query()->findOrFail($validated['newShopRouteId']);
        abort_unless(auth()->user()->canAccessRoute($route), 403);

        $shop = Shop::create([
            'name' => $validated['newShopName'],
            'route_id' => $route->id,
            'sort' => (int) Shop::where('route_id', $route->id)->max('sort') + 1,
            'active' => true,
        ]);

        $this->reset(['newShopName', 'newShopRouteId']);
        $this->selectShop($shop->id);
        session()->flash('status', 'Shop created. Select its usual items and save the setup.');
    }

    public function toggleItem(int $itemId, bool $selected): void
    {
        Gate::authorize('shops');
        abort_unless(Item::query()->whereKey($itemId)->exists(), 404);

        if ($selected && ! in_array($itemId, $this->selectedItemIds, true)) {
            $this->selectedItemIds[] = $itemId;
            $this->itemOrder[] = $itemId;
            $this->itemPrices[$itemId] ??= '';
        } elseif (! $selected) {
            $this->selectedItemIds = array_values(array_filter($this->selectedItemIds, fn ($id): bool => (int) $id !== $itemId));
            $this->itemOrder = array_values(array_filter($this->itemOrder, fn ($id): bool => (int) $id !== $itemId));
            unset($this->itemPrices[$itemId]);
        }
    }

    public function moveItem(int $itemId, int $direction): void
    {
        Gate::authorize('shops');
        if (! in_array($itemId, $this->selectedItemIds, true) || ! in_array($direction, [-1, 1], true)) {
            return;
        }

        $position = array_search($itemId, $this->itemOrder, true);
        $target = $position + $direction;
        if ($position === false || ! array_key_exists($target, $this->itemOrder)) {
            return;
        }

        [$this->itemOrder[$position], $this->itemOrder[$target]] = [$this->itemOrder[$target], $this->itemOrder[$position]];
    }

    public function copyFrom(): void
    {
        Gate::authorize('shops');
        $validated = $this->validate([
            'copyShopId' => ['required', 'integer', Rule::exists('shops', 'id')],
        ]);

        $source = $this->visibleShops()->whereKey($validated['copyShopId'])->with('items')->firstOrFail();
        abort_if($source->id === $this->shopId, 422, 'Choose another shop to copy from.');

        $this->selectedItemIds = $source->items->pluck('id')->all();
        $this->itemOrder = $this->selectedItemIds;
        $this->itemPrices = $source->items->mapWithKeys(fn (Item $item): array => [
            $item->id => $item->pivot->price ?? '',
        ])->all();
        session()->flash('status', 'Shop items, bill order, and fixed prices copied. Save to apply them to this shop.');
    }

    public function save(): void
    {
        Gate::authorize('shops');
        $shop = $this->currentShop();
        $this->persistShop($shop);
        session()->flash('status', 'Shop setup saved.');
    }

    public function saveAndNext(): void
    {
        Gate::authorize('shops');
        $shops = $this->visibleShops()->get();
        if ($shops->isEmpty()) {
            return;
        }

        $current = $this->currentShop();
        $this->persistShop($current);
        $currentIndex = $shops->search(fn (Shop $shop): bool => $shop->id === $current->id);
        $next = $shops->get(($currentIndex + 1) % $shops->count());
        $this->selectShop($next->id);
        session()->flash('status', 'Saved. Now editing '.$next->name.'.');
    }

    public function applyPricesToAllShops(): void
    {
        Gate::authorize('shops');
        $shop = $this->currentShop();
        $this->persistShop($shop);

        $visibleShopIds = $this->visibleShops()
            ->whereKeyNot($shop->id)
            ->pluck('id');

        DB::transaction(function () use ($visibleShopIds): void {
            foreach ($this->orderedSelectedIds() as $itemId) {
                $price = trim((string) ($this->itemPrices[$itemId] ?? ''));
                DB::table('shop_items')
                    ->whereIn('shop_id', $visibleShopIds)
                    ->where('item_id', $itemId)
                    ->update(['price' => $price === '' ? null : $price]);
            }
        });

        session()->flash('status', 'These fixed prices were applied to other accessible shops that sell the selected items.');
    }

    private function persistShop(Shop $shop): void
    {
        $route = BakeryRoute::query()->findOrFail($this->routeId);
        abort_unless(auth()->user()->canAccessRoute($route), 403);

        $validated = Validator::make([
            'shopName' => $this->shopName,
            'routeId' => $this->routeId,
            'selectedItemIds' => $this->selectedItemIds,
            'itemPrices' => $this->itemPrices,
        ], [
            'shopName' => ['required', 'string', 'max:160'],
            'routeId' => ['required', 'integer', 'exists:routes,id'],
            'selectedItemIds' => ['array'],
            'selectedItemIds.*' => ['integer', 'distinct', 'exists:items,id'],
            'itemPrices' => ['array'],
        ])->validate();

        foreach ($this->selectedItemIds as $itemId) {
            $price = trim((string) ($this->itemPrices[$itemId] ?? ''));
            if ($price !== '' && (! is_numeric($price) || (float) $price < 0 || strlen((string) $price) > 12)) {
                throw ValidationException::withMessages(["itemPrices.$itemId" => 'Enter a valid non-negative price or leave it empty to use the default.']);
            }
        }

        $pivot = [];
        foreach ($this->orderedSelectedIds() as $sort => $itemId) {
            $price = trim((string) ($this->itemPrices[$itemId] ?? ''));
            $pivot[$itemId] = ['sort' => $sort + 1, 'price' => $price === '' ? null : $price];
        }

        DB::transaction(function () use ($shop, $validated, $pivot): void {
            $shop->update([
                'name' => $validated['shopName'],
                'route_id' => $validated['routeId'],
            ]);
            $shop->items()->sync($pivot);
        });

        $this->shopName = $shop->name;
    }

    private function orderedSelectedIds(): array
    {
        $selected = array_map('intval', $this->selectedItemIds);
        $ordered = array_values(array_filter(array_map('intval', $this->itemOrder), fn (int $id): bool => in_array($id, $selected, true)));

        foreach ($selected as $itemId) {
            if (! in_array($itemId, $ordered, true)) {
                $ordered[] = $itemId;
            }
        }

        return $ordered;
    }

    private function currentShop(): Shop
    {
        $validated = $this->validate(['shopId' => ['required', 'integer', 'exists:shops,id']]);

        return $this->visibleShops()->whereKey($validated['shopId'])->firstOrFail();
    }

    private function selectShop(int $shopId): void
    {
        $shop = $this->visibleShops()->whereKey($shopId)->with('items')->firstOrFail();
        $this->shopId = $shop->id;
        $this->shopName = $shop->name;
        $this->routeId = $shop->route_id;
        $this->selectedItemIds = $shop->items->pluck('id')->all();
        $this->itemOrder = $this->selectedItemIds;
        $this->itemPrices = $shop->items->mapWithKeys(fn (Item $item): array => [
            $item->id => $item->pivot->price ?? '',
        ])->all();
        $this->copyShopId = null;
        $this->resetValidation();
    }

    private function resetEditor(): void
    {
        $this->reset(['shopName', 'routeId', 'selectedItemIds', 'itemPrices', 'itemOrder', 'copyShopId']);
    }

    private function visibleShops()
    {
        return Shop::query()
            ->visibleTo(auth()->user())
            ->with('route')
            ->orderBy(BakeryRoute::select('sort')->whereColumn('routes.id', 'shops.route_id'))
            ->orderBy('shops.sort')
            ->orderBy('shops.name');
    }

    public function render()
    {
        return view('livewire.admin.shops', [
            'shops' => $this->visibleShops()->with('items')->get(),
            'items' => Item::query()->orderBy('sort')->get(),
            'routes' => BakeryRoute::query()
                ->when(! auth()->user()->all_routes, fn ($query) => $query->whereIn('id', auth()->user()->routes()->select('routes.id')))
                ->orderBy('sort')->get(),
            'orderedItems' => Item::query()->whereIn('id', $this->orderedSelectedIds())->get()->keyBy('id'),
        ]);
    }
}
