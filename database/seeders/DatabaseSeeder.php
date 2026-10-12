<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Route as BakeryRoute;
use App\Models\Shop;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $path = base_path('data/seed.json');

        if (! is_file($path)) {
            throw new RuntimeException('The bakery seed file was not found at data/seed.json.');
        }

        $seed = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($seed): void {
            $routes = [];
            foreach ($seed['routes'] ?? [] as $key => $routeData) {
                $routes[$key] = BakeryRoute::firstOrCreate(
                    ['source_key' => (string) $key],
                    [
                        'name' => $routeData['name'],
                        'driver' => $routeData['driver'] ?: null,
                        'sort' => $routeData['order'],
                    ],
                );
            }

            $items = [];
            foreach ($seed['config']['catalog']['items'] ?? [] as $sort => $itemData) {
                $items[$itemData['id']] = Item::firstOrCreate(
                    ['source_key' => (string) $itemData['id']],
                    [
                        'name' => $itemData['name'],
                        'default_price' => (string) ($itemData['price'] ?? '0.00'),
                        'sort' => $sort + 1,
                    ],
                );
            }

            foreach ($seed['shops'] ?? [] as $key => $shopData) {
                $route = $routes[$shopData['routeId']] ?? null;
                if (! $route) {
                    throw new RuntimeException("Unknown route [{$shopData['routeId']}] for shop [{$key}].");
                }

                $shop = Shop::firstOrCreate(
                    ['source_key' => (string) $key],
                    [
                        'name' => $shopData['name'],
                        'route_id' => $route->id,
                        'sort' => $shopData['order'],
                        'active' => true,
                    ],
                );

                $existingItemIds = $shop->items()->pluck('items.id')->all();
                foreach ($shopData['items'] as $sort => $itemKey) {
                    $item = $items[$itemKey] ?? null;
                    if (! $item) {
                        throw new RuntimeException("Unknown item [{$itemKey}] for shop [{$key}].");
                    }

                    if (! in_array($item->id, $existingItemIds, true)) {
                        $shop->items()->attach($item->id, ['sort' => $sort + 1, 'price' => null]);
                    }
                }
            }
        });
    }
}
