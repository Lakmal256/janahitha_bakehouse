<?php

namespace App\Documents;

use App\Models\Item;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Reads and writes the browser app's `config/catalog` document: {items: [{id, name, price}]}.
 */
class CatalogDocument
{
    /**
     * @return Collection<int, Item>
     */
    public function items(): Collection
    {
        return Item::query()->orderBy('sort')->orderBy('id')->get();
    }

    /**
     * Save the item list in the given order. Items missing from the list are kept, because
     * orders, bills and shops may still refer to them.
     *
     * @param  list<array{id: string, name: string, price?: float|int|string|null}>  $items
     */
    public function save(array $items): void
    {
        $savedKeys = [];

        foreach (array_values($items) as $index => $data) {
            $key = (string) $data['id'];
            $savedKeys[] = $key;

            Item::query()->updateOrCreate(['source_key' => $key], [
                'name' => trim($data['name']),
                'default_price' => round((float) ($data['price'] ?? 0), 2),
                'sort' => $index + 1,
            ]);
        }

        $nextSort = count($savedKeys);
        Item::query()
            ->whereNotIn('source_key', $savedKeys)
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->each(function (Item $item) use (&$nextSort): void {
                $nextSort++;
                if ($item->sort !== $nextSort) {
                    $item->update(['sort' => $nextSort]);
                }
            });
    }

    /**
     * Delete an item that is not used by any shop, order or bill.
     */
    public function delete(Item $item): void
    {
        if ($item->shops()->exists() || $item->orderLines()->exists() || $item->billLines()->exists()) {
            throw ValidationException::withMessages([
                'item' => 'This item is used by a shop, order, or saved bill and cannot be deleted.',
            ]);
        }

        $item->delete();
    }

    /**
     * Look up item ids by key, creating any item that does not exist yet.
     *
     * @param  array<string, string>  $namesByKey  The name to give each item if it has to be created.
     * @return array<string, int>
     */
    public function ensureItems(array $namesByKey): array
    {
        $ids = Item::query()->whereIn('source_key', array_map('strval', array_keys($namesByKey)))->pluck('id', 'source_key')->all();
        $nextSort = (int) Item::query()->max('sort');

        foreach ($namesByKey as $key => $name) {
            $key = (string) $key;

            if (! isset($ids[$key])) {
                $ids[$key] = Item::query()->create([
                    'source_key' => $key,
                    'name' => $name !== '' ? $name : $key,
                    'default_price' => 0,
                    'sort' => ++$nextSort,
                ])->id;
            }
        }

        return $ids;
    }

    /**
     * @param  list<string>  $keys
     * @return array<string, int>
     */
    public function idsFor(array $keys): array
    {
        return Item::query()->whereIn('source_key', array_map('strval', $keys))->pluck('id', 'source_key')->all();
    }
}
