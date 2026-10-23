<?php

namespace App\Documents;

use App\Models\Bill;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Route as BakeryRoute;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Copies the data the browser app kept in localStorage into the database.
 * Records are matched by their browser ids, so importing the same backup again adds nothing new.
 */
class BrowserDataImporter
{
    private const PAY_METHODS = ['cash', 'credit', 'partial'];

    private const PAYMENT_KINDS = ['cash', 'credit', 'partial', 'collect'];

    /** @var list<string> */
    private array $warnings = [];

    public function __construct(
        private CatalogDocument $catalog,
        private RouteDocuments $routes,
        private ShopDocuments $shops,
        private UserDocuments $users,
        private DayDocuments $days,
        private BillDocuments $bills,
    ) {}

    /**
     * @param  array<string, mixed>  $data  The browser app's saved data.
     * @return array{summary: array<string, array<string, int>>, verification: array{ok: bool, checks: array<string, array{expected: int, found: int}>}, warnings: list<string>}
     */
    public function import(array $data, User $actor): array
    {
        $this->warnings = [];
        $before = $this->tableCounts();

        $summary = DB::transaction(function () use ($data, $actor): array {
            $items = $this->importCatalog($data);
            $items['missing_created'] = $this->createReferencedItems($data);

            return [
                'items' => $items,
                'routes' => $this->importRoutes($data),
                'shops' => $this->importShops($data),
                'users' => $this->importUsers($data),
                'orders' => ['documents' => $this->importDays($data, $actor)],
                'bills' => ['documents' => $this->importBills($data, $actor)],
            ];
        });

        $after = $this->tableCounts();
        $summary['orders'] += ['added' => $after['orders'] - $before['orders'], 'lines_added' => $after['order_lines'] - $before['order_lines']];
        $summary['bills'] += ['added' => $after['bills'] - $before['bills'], 'payments_added' => $after['payments'] - $before['payments']];

        return [
            'summary' => $summary,
            'verification' => $this->verify($data),
            'warnings' => $this->warnings,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{created: int, updated: int}
     */
    private function importCatalog(array $data): array
    {
        $items = collect($data['config']['catalog']['items'] ?? [])
            ->filter(fn (mixed $item): bool => is_array($item) && filled($item['id'] ?? null) && filled($item['name'] ?? null))
            ->map(fn (array $item): array => [
                'id' => (string) $item['id'],
                'name' => Str::limit((string) $item['name'], 250, ''),
                'price' => is_numeric($item['price'] ?? null) ? max(0, (float) $item['price']) : 0,
            ])
            ->unique('id')
            ->values()
            ->all();

        $existingPrices = Item::query()
            ->whereIn('source_key', array_column($items, 'id'))
            ->pluck('default_price', 'source_key')
            ->all();

        $items = array_map(function (array $item) use ($existingPrices): array {
            if ($item['price'] == 0 && isset($existingPrices[$item['id']]) && (float) $existingPrices[$item['id']] > 0) {
                $item['price'] = (float) $existingPrices[$item['id']];
            }

            return $item;
        }, $items);

        $existing = count($existingPrices);
        $this->catalog->save($items);

        return ['created' => count($items) - $existing, 'updated' => $existing];
    }

    /**
     * Shops, orders and bills can mention items that are no longer in the catalog; keep them by
     * creating those items, named after the bill line where one exists.
     *
     * @param  array<string, mixed>  $data
     */
    private function createReferencedItems(array $data): int
    {
        $names = [];

        foreach ($this->documents($data, 'shops') as $shop) {
            foreach ((array) ($shop['items'] ?? []) as $itemKey) {
                if (is_scalar($itemKey) && $itemKey !== '') {
                    $names[(string) $itemKey] ??= (string) $itemKey;
                }
            }
        }

        foreach ($this->documents($data, 'days') as $day) {
            foreach ((array) ($day['shops'] ?? []) as $entry) {
                foreach (array_keys((array) ($entry['q'] ?? [])) as $itemKey) {
                    $names[(string) $itemKey] ??= (string) $itemKey;
                }
            }
        }

        foreach ($this->documents($data, 'bills') as $bill) {
            foreach ((array) ($bill['shops'] ?? []) as $entry) {
                foreach ($this->billLines($entry['lines'] ?? []) as $line) {
                    $names[$line['id']] = $line['name'];
                }
            }
        }

        $before = Item::query()->count();
        $this->catalog->ensureItems($names);

        return Item::query()->count() - $before;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{created: int, updated: int, skipped: int}
     */
    private function importRoutes(array $data): array
    {
        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        foreach ($this->documents($data, 'routes') as $key => $route) {
            if (! filled($route['name'] ?? null) || ! is_scalar($route['name'])) {
                $result['skipped']++;
                $this->warnings[] = "Route [{$key}] has no name and was skipped.";

                continue;
            }

            $exists = BakeryRoute::query()->where('source_key', $key)->exists();
            $this->routes->save($key, [
                'name' => Str::limit((string) $route['name'], 250, ''),
                'driver' => is_scalar($route['driver'] ?? null) ? Str::limit((string) $route['driver'], 250, '') : null,
                'order' => is_numeric($route['order'] ?? null) ? (int) $route['order'] : 0,
            ], replace: true);
            $result[$exists ? 'updated' : 'created']++;
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{created: int, updated: int, skipped: int}
     */
    private function importShops(array $data): array
    {
        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        foreach ($this->documents($data, 'shops') as $key => $shop) {
            $routeKey = is_scalar($shop['routeId'] ?? null) ? (string) $shop['routeId'] : '';

            if (! filled($shop['name'] ?? null) || ! is_scalar($shop['name']) || ! BakeryRoute::query()->where('source_key', $routeKey)->exists()) {
                $result['skipped']++;
                $this->warnings[] = "Shop [{$key}] has no name or an unknown route and was skipped.";

                continue;
            }

            $exists = Shop::query()->where('source_key', $key)->exists();
            $this->shops->save($key, [
                'name' => Str::limit((string) $shop['name'], 250, ''),
                'routeId' => $routeKey,
                'items' => array_values(array_map('strval', array_filter((array) ($shop['items'] ?? []), fn (mixed $itemKey): bool => is_scalar($itemKey) && $itemKey !== ''))),
                'prices' => array_filter((array) ($shop['prices'] ?? []), fn (mixed $price): bool => is_numeric($price) && $price >= 0),
                'order' => is_numeric($shop['order'] ?? null) ? (int) $shop['order'] : 0,
            ], replace: true);
            $result[$exists ? 'updated' : 'created']++;
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{created: int, existing: int, username_taken: int, skipped: int}
     */
    private function importUsers(array $data): array
    {
        $result = ['created' => 0, 'existing' => 0, 'username_taken' => 0, 'skipped' => 0];

        foreach ($this->documents($data, 'users') as $key => $user) {
            $outcome = $this->users->import($key, $user);
            $result[$outcome]++;

            if ($outcome === 'username_taken') {
                $this->warnings[] = "The username [{$user['username']}] already belongs to an account on the server, so that account was kept and the browser account was not imported.";
            }
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function importDays(array $data, User $actor): int
    {
        $imported = 0;

        foreach ($this->dayDocuments($data, 'days') as [$date, $route, $shops]) {
            $entries = [];

            foreach ($shops as $shopKey => $entry) {
                $quantities = array_filter((array) ($entry['q'] ?? []), fn (mixed $quantity): bool => $quantity === null || $quantity === '' || is_numeric($quantity));
                $entries[$shopKey] = [
                    'q' => array_map(fn (mixed $quantity): int => max(0, (int) $quantity), $quantities),
                    'at' => is_numeric($entry['at'] ?? null) ? $entry['at'] : null,
                ];
            }

            $this->days->merge($date, $route, $entries, $actor, requireShopOnRoute: false);
            $imported++;
        }

        return $imported;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function importBills(array $data, User $actor): int
    {
        $imported = 0;

        foreach ($this->dayDocuments($data, 'bills') as [$date, $route, $shops]) {
            $entries = [];

            foreach ($shops as $shopKey => $entry) {
                $clean = [];

                if (array_key_exists('lines', $entry)) {
                    $clean['lines'] = $this->billLines($entry['lines']);
                }

                foreach (['shopName' => 'is_scalar', 'at' => 'is_numeric', 'editedAt' => 'is_numeric'] as $field => $check) {
                    if ($check($entry[$field] ?? null)) {
                        $clean[$field] = $field === 'shopName' ? Str::limit((string) $entry[$field], 250, '') : $entry[$field];
                    }
                }

                if (is_array($entry['pay'] ?? null) && in_array($entry['pay']['method'] ?? null, self::PAY_METHODS, true)) {
                    $clean['pay'] = [
                        'method' => $entry['pay']['method'],
                        'paid' => is_numeric($entry['pay']['paid'] ?? null) ? max(0, (float) $entry['pay']['paid']) : 0,
                        'at' => is_numeric($entry['pay']['at'] ?? null) ? $entry['pay']['at'] : null,
                        'log' => $this->paymentLog($entry['pay']['log'] ?? []),
                    ];
                }

                $entries[$shopKey] = $clean;
            }

            $this->bills->merge($date, $route, $entries, $actor, requireShopOnRoute: false);
            $imported++;
        }

        return $imported;
    }

    /**
     * Yield the valid route-and-day documents of a collection with their known shops.
     *
     * @param  array<string, mixed>  $data
     * @return \Generator<int, array{0: string, 1: BakeryRoute, 2: array<string, array<string, mixed>>}>
     */
    private function dayDocuments(array $data, string $collection): \Generator
    {
        foreach ($this->documents($data, $collection) as $id => $document) {
            $parsed = DocumentKey::parse($id);
            $date = is_string($document['date'] ?? null) ? $document['date'] : $parsed[0] ?? null;
            $routeKey = is_scalar($document['routeId'] ?? null) ? (string) $document['routeId'] : $parsed[1] ?? null;
            $route = $routeKey !== null ? BakeryRoute::query()->where('source_key', $routeKey)->first() : null;

            if ($date === null || DocumentKey::parse($date.'_x') === null || $route === null) {
                $this->warnings[] = "The {$collection} record [{$id}] has an invalid date or an unknown route and was skipped.";

                continue;
            }

            $shops = array_filter((array) ($document['shops'] ?? []), 'is_array');
            $known = Shop::query()->whereIn('source_key', array_map('strval', array_keys($shops)))->pluck('source_key')->map(fn (mixed $key): string => (string) $key)->all();

            foreach (array_diff(array_map('strval', array_keys($shops)), $known) as $missing) {
                $this->warnings[] = "The {$collection} record [{$id}] mentions the unknown shop [{$missing}], which was skipped.";
            }

            $shops = array_intersect_key($shops, array_flip($known));

            if ($shops !== []) {
                yield [$date, $route, $shops];
            }
        }
    }

    /**
     * @return list<array{id: string, name: string, qty: int, ret: int, price: float}>
     */
    private function billLines(mixed $lines): array
    {
        return collect(is_array($lines) ? $lines : [])
            ->filter(fn (mixed $line): bool => is_array($line) && is_scalar($line['id'] ?? null) && (string) $line['id'] !== '')
            ->map(function (array $line): array {
                $quantity = is_numeric($line['qty'] ?? null) ? max(0, (int) $line['qty']) : 0;

                return [
                    'id' => (string) $line['id'],
                    'name' => Str::limit(is_scalar($line['name'] ?? null) && (string) $line['name'] !== '' ? (string) $line['name'] : (string) $line['id'], 250, ''),
                    'qty' => $quantity,
                    'ret' => is_numeric($line['ret'] ?? null) ? min($quantity, max(0, (int) $line['ret'])) : 0,
                    'price' => is_numeric($line['price'] ?? null) ? max(0, (float) $line['price']) : 0,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{kind: string, amount: float, at: int|float}>
     */
    private function paymentLog(mixed $log): array
    {
        return collect(is_array($log) ? $log : [])
            ->filter(fn (mixed $entry): bool => is_array($entry) && in_array($entry['kind'] ?? null, self::PAYMENT_KINDS, true) && is_numeric($entry['at'] ?? null))
            ->map(fn (array $entry): array => [
                'kind' => $entry['kind'],
                'amount' => is_numeric($entry['amount'] ?? null) ? max(0, (float) $entry['amount']) : 0,
                'at' => $entry['at'],
            ])
            ->values()
            ->all();
    }

    /**
     * Check that every imported record can be read back from the database.
     *
     * @param  array<string, mixed>  $data
     * @return array{ok: bool, checks: array<string, array{expected: int, found: int}>}
     */
    private function verify(array $data): array
    {
        $routeKeys = array_keys(array_filter($this->documents($data, 'routes'), fn (array $route): bool => filled($route['name'] ?? null)));
        $knownRouteKeys = BakeryRoute::query()->pluck('source_key')->map(fn (mixed $key): string => (string) $key)->all();
        $shopKeys = array_keys(array_filter($this->documents($data, 'shops'), fn (array $shop): bool => filled($shop['name'] ?? null) && in_array((string) ($shop['routeId'] ?? ''), $knownRouteKeys, true)));
        $itemKeys = array_values(array_unique(array_column(array_filter($data['config']['catalog']['items'] ?? [], fn (mixed $item): bool => is_array($item) && filled($item['id'] ?? null) && filled($item['name'] ?? null)), 'id')));
        $users = array_filter($this->documents($data, 'users'), fn (array $user): bool => filled($user['username'] ?? null) && filled($user['passHash'] ?? null));

        $checks = [
            'routes' => ['expected' => count($routeKeys), 'found' => BakeryRoute::query()->whereIn('source_key', $routeKeys)->count()],
            'shops' => ['expected' => count($shopKeys), 'found' => Shop::query()->whereIn('source_key', $shopKeys)->count()],
            'items' => ['expected' => count($itemKeys), 'found' => Item::query()->whereIn('source_key', array_map('strval', $itemKeys))->count()],
            'users' => [
                'expected' => count($users),
                'found' => collect($users)->filter(fn (array $user, string $key): bool => User::query()
                    ->where('source_key', $key)
                    ->orWhere('username', Str::lower(trim((string) $user['username'])))
                    ->exists())->count(),
            ],
        ];

        $checks['orders'] = $this->verifyOrders($data);
        $checks['bills'] = $this->verifyBills($data);

        return [
            'ok' => collect($checks)->every(fn (array $check): bool => $check['expected'] === $check['found']),
            'checks' => $checks,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{expected: int, found: int}
     */
    private function verifyOrders(array $data): array
    {
        $expected = 0;
        $found = 0;
        $warnings = $this->warnings;

        foreach ($this->dayDocuments($data, 'days') as [$date, , $shops]) {
            $stored = Order::query()
                ->whereDate('date', $date)
                ->whereHas('shop', fn ($query) => $query->whereIn('source_key', array_map('strval', array_keys($shops))))
                ->with(['shop:id,source_key', 'lines.item:id,source_key'])
                ->get()
                ->mapWithKeys(fn (Order $order): array => [
                    $order->shop->source_key => $order->lines->mapWithKeys(fn (OrderLine $line): array => [$line->item->source_key => (int) $line->qty])->sortKeys()->all(),
                ]);

            foreach ($shops as $shopKey => $entry) {
                $wanted = collect((array) ($entry['q'] ?? []))
                    ->filter(fn (mixed $quantity): bool => is_numeric($quantity) && (int) $quantity > 0)
                    ->map(fn (mixed $quantity): int => (int) $quantity)
                    ->sortKeys()
                    ->all();

                if ($wanted === []) {
                    continue;
                }

                $expected++;
                $found += $stored->get((string) $shopKey) == $wanted ? 1 : 0;
            }
        }

        $this->warnings = $warnings;

        return ['expected' => $expected, 'found' => $found];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{expected: int, found: int}
     */
    private function verifyBills(array $data): array
    {
        $expected = 0;
        $found = 0;
        $warnings = $this->warnings;

        foreach ($this->dayDocuments($data, 'bills') as [$date, , $shops]) {
            $stored = Bill::query()
                ->whereDate('date', $date)
                ->whereHas('shop', fn ($query) => $query->whereIn('source_key', array_map('strval', array_keys($shops))))
                ->with('shop:id,source_key')
                ->withCount(['lines', 'payments'])
                ->get()
                ->keyBy(fn (Bill $bill): string => $bill->shop->source_key);

            foreach ($shops as $shopKey => $entry) {
                $expected++;
                $bill = $stored->get((string) $shopKey);
                $lines = count($this->billLines($entry['lines'] ?? []));
                $payments = count($this->paymentLog($entry['pay']['log'] ?? []));

                $found += $bill && $bill->lines_count === $lines && $bill->payments_count >= $payments ? 1 : 0;
            }
        }

        $this->warnings = $warnings;

        return ['expected' => $expected, 'found' => $found];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, array<string, mixed>>
     */
    private function documents(array $data, string $collection): array
    {
        $documents = [];

        foreach ((array) ($data[$collection] ?? []) as $key => $document) {
            if (is_array($document) && (string) $key !== '') {
                $documents[(string) $key] = $document;
            }
        }

        return $documents;
    }

    /**
     * @return array<string, int>
     */
    private function tableCounts(): array
    {
        return [
            'orders' => Order::query()->count(),
            'order_lines' => OrderLine::query()->count(),
            'bills' => Bill::query()->count(),
            'payments' => Payment::query()->count(),
        ];
    }
}
