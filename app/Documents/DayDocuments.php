<?php

namespace App\Documents;

use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Route as BakeryRoute;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reads and writes the browser app's `days` documents, one per route and day:
 * {date, routeId, shops: {shopKey: {q: {itemKey: quantity}, at}}}.
 */
class DayDocuments
{
    public function __construct(private CatalogDocument $catalog) {}

    /**
     * @param  array{date?: ?string, from?: ?string, to?: ?string, routeId?: ?string}  $filters
     * @return list<array<string, mixed>>
     */
    public function query(User $viewer, array $filters): array
    {
        $orders = Order::query()
            ->visibleTo($viewer)
            ->when($filters['date'] ?? null, fn (Builder $query, string $date) => $query->whereDate('date', $date))
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->whereDate('date', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->whereDate('date', '<=', $to))
            ->when($filters['routeId'] ?? null, fn (Builder $query, string $key) => $query->whereRelation('route', 'source_key', $key))
            ->with(['route:id,source_key', 'shop:id,source_key', 'lines' => fn ($query) => $query->orderBy('id'), 'lines.item:id,source_key'])
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        return $orders
            ->groupBy(fn (Order $order): string => DocumentKey::make($order->date, $order->route->source_key))
            ->map(fn (Collection $orders, string $id): array => [
                'id' => $id,
                'date' => $orders->first()->date->format('Y-m-d'),
                'routeId' => $orders->first()->route->source_key,
                'shops' => (object) $orders->mapWithKeys(fn (Order $order): array => [
                    $order->shop->source_key => [
                        'q' => (object) $order->lines->mapWithKeys(fn (OrderLine $line): array => [$line->item->source_key => (int) $line->qty])->all(),
                        'at' => DocumentKey::milliseconds($order->updated_at),
                    ],
                ])->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * Merge shop orders into a route's day. Quantities above zero are saved, zero or empty ones removed,
     * and items left out are not changed.
     *
     * @param  array<string, array{q?: array<string, int|string|null>, at?: int|float|null}>  $shops
     * @param  bool  $requireShopOnRoute  Whether each shop must currently belong to the route.
     */
    public function merge(string $date, BakeryRoute $route, array $shops, ?User $actor, bool $requireShopOnRoute = true): void
    {
        DB::transaction(function () use ($date, $route, $shops, $actor, $requireShopOnRoute): void {
            $shopModels = Shop::query()->whereIn('source_key', array_map('strval', array_keys($shops)))->get()->keyBy('source_key');
            $itemKeys = collect($shops)->flatMap(fn (array $entry): array => array_map('strval', array_keys($entry['q'] ?? [])))->unique()->values()->all();
            $itemIds = $this->catalog->idsFor($itemKeys);

            foreach ($shops as $shopKey => $entry) {
                $shop = $shopModels->get((string) $shopKey) ?? throw ValidationException::withMessages([
                    'shops' => "The shop [{$shopKey}] does not exist.",
                ]);

                if ($requireShopOnRoute && $shop->route_id !== $route->id) {
                    throw ValidationException::withMessages(['shops' => "The shop [{$shopKey}] is not on this route."]);
                }

                $order = Order::query()->whereDate('date', $date)->where('shop_id', $shop->id)->first()
                    ?? new Order(['date' => $date, 'shop_id' => $shop->id, 'created_by' => $actor?->id]);
                $order->route_id = $route->id;
                $order->updated_at = DocumentKey::timestamp($entry['at'] ?? null) ?? now();
                $order->save();

                $this->saveQuantities($order, $entry['q'] ?? [], $itemIds);
            }
        });
    }

    /**
     * Delete all orders for a route and day.
     */
    public function delete(string $date, BakeryRoute $route): void
    {
        Order::query()->whereDate('date', $date)->where('route_id', $route->id)->delete();
    }

    /**
     * @param  array<string, int|string|null>  $quantities
     * @param  array<string, int>  $itemIds
     */
    private function saveQuantities(Order $order, array $quantities, array $itemIds): void
    {
        $upserts = [];
        $removals = [];

        foreach ($quantities as $itemKey => $quantity) {
            $itemId = $itemIds[(string) $itemKey] ?? throw ValidationException::withMessages([
                'q' => "The item [{$itemKey}] does not exist.",
            ]);

            if ((int) $quantity > 0) {
                $upserts[] = ['order_id' => $order->id, 'item_id' => $itemId, 'qty' => (int) $quantity];
            } else {
                $removals[] = $itemId;
            }
        }

        if ($upserts !== []) {
            OrderLine::query()->upsert($upserts, ['order_id', 'item_id'], ['qty']);
        }

        if ($removals !== []) {
            $order->lines()->whereIn('item_id', $removals)->delete();
        }
    }
}
