<?php

namespace App\Documents;

use App\Models\Route as BakeryRoute;
use App\Models\Shop;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reads and writes the browser app's `shops` documents:
 * {name, routeId, items: [itemKey], prices: {itemKey: price}, order}.
 */
class ShopDocuments
{
    public function __construct(private CatalogDocument $catalog) {}

    /**
     * Create or update a shop. A full save replaces every field; a partial save only the given ones,
     * and merges `prices` into the shop's existing prices.
     *
     * @param  array{name?: string, routeId?: string, items?: list<string>, prices?: array<string, float|int|string|null>, order?: ?int}  $data
     */
    public function save(string $key, array $data, bool $replace): Shop
    {
        return DB::transaction(function () use ($key, $data, $replace): Shop {
            $shop = Shop::query()->firstOrNew(['source_key' => $key]);

            if ($replace || array_key_exists('name', $data)) {
                $shop->name = trim($data['name']);
            }

            if ($replace || array_key_exists('routeId', $data)) {
                $shop->route_id = BakeryRoute::query()->where('source_key', $data['routeId'])->value('id')
                    ?? throw ValidationException::withMessages(['routeId' => 'The selected route does not exist.']);
            }

            if ($replace || array_key_exists('order', $data)) {
                $shop->sort = (int) ($data['order'] ?? 0);
            }

            $shop->active = true;
            $shop->save();

            if ($replace || array_key_exists('items', $data)) {
                $this->syncItems($shop, $data['items'] ?? [], $data['prices'] ?? []);
            } elseif (array_key_exists('prices', $data)) {
                $this->mergePrices($shop, $data['prices']);
            }

            return $shop;
        });
    }

    /**
     * Delete a shop. A shop with orders or bills is archived instead, so its history stays in reports.
     *
     * @return bool Whether the shop was archived rather than deleted.
     */
    public function delete(Shop $shop): bool
    {
        if ($shop->orders()->exists() || $shop->bills()->exists()) {
            $shop->update(['active' => false]);

            return true;
        }

        $shop->delete();

        return false;
    }

    /**
     * @param  list<string>  $itemKeys
     * @param  array<string, float|int|string|null>  $prices
     */
    private function syncItems(Shop $shop, array $itemKeys, array $prices): void
    {
        $itemKeys = array_values(array_unique(array_map('strval', $itemKeys)));
        $ids = $this->catalog->idsFor($itemKeys);
        $pivot = [];

        foreach ($itemKeys as $index => $itemKey) {
            $itemId = $ids[$itemKey] ?? throw ValidationException::withMessages([
                'items' => "The item [{$itemKey}] does not exist.",
            ]);

            $pivot[$itemId] = ['sort' => $index + 1, 'price' => $this->price($prices[$itemKey] ?? null)];
        }

        $shop->items()->sync($pivot);
    }

    /**
     * @param  array<string, float|int|string|null>  $prices
     */
    private function mergePrices(Shop $shop, array $prices): void
    {
        $ids = $this->catalog->idsFor(array_map('strval', array_keys($prices)));
        $shopItemIds = $shop->items()->pluck('items.id')->all();

        foreach ($prices as $itemKey => $price) {
            $itemId = $ids[(string) $itemKey] ?? null;

            if ($itemId !== null && in_array($itemId, $shopItemIds, true)) {
                $shop->items()->updateExistingPivot($itemId, ['price' => $this->price($price)]);
            }
        }
    }

    private function price(float|int|string|null $price): ?float
    {
        return $price === null || $price === '' ? null : round((float) $price, 2);
    }
}
