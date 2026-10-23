<?php

namespace App\Documents;

use App\Models\Bill;
use App\Models\BillLine;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Route as BakeryRoute;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reads and writes the browser app's `bills` documents, one per route and day:
 * {date, routeId, shops: {shopKey: {lines: [{id, name, qty, ret, price}], shopName, at, editedAt,
 * pay: {method, paid, at, log: [{kind, amount, at}]}}}}.
 */
class BillDocuments
{
    public function __construct(private CatalogDocument $catalog) {}

    /**
     * @param  array{date?: ?string, from?: ?string, to?: ?string, routeId?: ?string}  $filters
     * @return list<array<string, mixed>>
     */
    public function query(User $viewer, array $filters): array
    {
        $bills = Bill::query()
            ->visibleTo($viewer)
            ->when($filters['date'] ?? null, fn (Builder $query, string $date) => $query->whereDate('date', $date))
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->whereDate('date', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->whereDate('date', '<=', $to))
            ->when($filters['routeId'] ?? null, fn (Builder $query, string $key) => $query->whereRelation('route', 'source_key', $key))
            ->with([
                'route:id,source_key',
                'shop:id,source_key',
                'lines' => fn ($query) => $query->orderBy('id'),
                'lines.item:id,source_key',
                'payments' => fn ($query) => $query->orderBy('created_at')->orderBy('id'),
            ])
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        return $bills
            ->groupBy(fn (Bill $bill): string => DocumentKey::make($bill->date, $bill->route->source_key))
            ->map(fn (Collection $bills, string $id): array => [
                'id' => $id,
                'date' => $bills->first()->date->format('Y-m-d'),
                'routeId' => $bills->first()->route->source_key,
                'shops' => (object) $bills->mapWithKeys(fn (Bill $bill): array => [
                    $bill->shop->source_key => $this->entry($bill),
                ])->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * Work out which permissions a change needs. Generating a bill needs `bills_generate`;
     * editing one needs `bills_returns` for returns, `bills_prices` for prices, and
     * `bills_generate` for anything else; recording a payment needs `bills_payments`.
     *
     * @param  array<string, array<string, mixed>>  $shops
     * @return list<string>
     */
    public function requiredPermissions(string $date, array $shops): array
    {
        $existing = Bill::query()
            ->whereDate('date', $date)
            ->whereHas('shop', fn (Builder $query) => $query->whereIn('source_key', array_map('strval', array_keys($shops))))
            ->with(['shop:id,source_key', 'lines' => fn ($query) => $query->orderBy('id'), 'lines.item:id,source_key'])
            ->get()
            ->keyBy(fn (Bill $bill): string => $bill->shop->source_key);

        $permissions = [];

        foreach ($shops as $shopKey => $entry) {
            if (array_key_exists('pay', $entry)) {
                $permissions[] = 'bills_payments';
            }

            if (! array_key_exists('lines', $entry)) {
                continue;
            }

            if (array_key_exists('at', $entry)) {
                $permissions[] = 'bills_generate';

                continue;
            }

            $before = $existing->get((string) $shopKey)?->lines->map(fn (BillLine $line): array => $this->line($line))->all() ?? [];
            array_push($permissions, ...$this->editPermissions($before, $entry['lines']));
        }

        return array_values(array_unique($permissions));
    }

    /**
     * Merge shop bills into a route's day.
     *
     * @param  array<string, array<string, mixed>>  $shops
     * @param  bool  $requireShopOnRoute  Whether each shop must currently belong to the route.
     */
    public function merge(string $date, BakeryRoute $route, array $shops, ?User $actor, bool $requireShopOnRoute = true): void
    {
        DB::transaction(function () use ($date, $route, $shops, $actor, $requireShopOnRoute): void {
            $shopModels = Shop::query()->whereIn('source_key', array_map('strval', array_keys($shops)))->get()->keyBy('source_key');
            $itemKeys = collect($shops)->flatMap(fn (array $entry): array => array_map(fn (array $line): string => (string) $line['id'], $entry['lines'] ?? []))->unique()->values()->all();
            $itemIds = $this->catalog->idsFor($itemKeys);

            foreach ($shops as $shopKey => $entry) {
                $shop = $shopModels->get((string) $shopKey) ?? throw ValidationException::withMessages([
                    'shops' => "The shop [{$shopKey}] does not exist.",
                ]);

                if ($requireShopOnRoute && $shop->route_id !== $route->id) {
                    throw ValidationException::withMessages(['shops' => "The shop [{$shopKey}] is not on this route."]);
                }

                $this->mergeEntry($date, $route, $shop, $entry, $itemIds, $actor);
            }
        });
    }

    /**
     * Delete all bills for a route and day.
     */
    public function delete(string $date, BakeryRoute $route): void
    {
        Bill::query()->whereDate('date', $date)->where('route_id', $route->id)->delete();
    }

    /**
     * @param  array<string, mixed>  $entry
     * @param  array<string, int>  $itemIds
     */
    private function mergeEntry(string $date, BakeryRoute $route, Shop $shop, array $entry, array $itemIds, ?User $actor): void
    {
        $bill = Bill::query()->whereDate('date', $date)->where('shop_id', $shop->id)->first()
            ?? new Bill(['date' => $date, 'shop_id' => $shop->id, 'total' => 0, 'paid' => 0, 'status' => 'due']);

        $bill->route_id = $route->id;
        $bill->order_id = Order::query()->whereDate('date', $date)->where('shop_id', $shop->id)->value('id');

        if (array_key_exists('shopName', $entry)) {
            $bill->shop_name = $entry['shopName'];
        }

        if (array_key_exists('at', $entry)) {
            $bill->generated_at = DocumentKey::timestamp($entry['at']);
        }

        if (array_key_exists('editedAt', $entry)) {
            $bill->edited_at = DocumentKey::timestamp($entry['editedAt']);
        }

        if (is_array($entry['pay'] ?? null)) {
            $bill->pay_method = $entry['pay']['method'];
            $bill->paid = round((float) ($entry['pay']['paid'] ?? 0), 2);
            $bill->paid_at = DocumentKey::timestamp($entry['pay']['at'] ?? null) ?? now();
        }

        $bill->save();

        if (array_key_exists('lines', $entry)) {
            $bill->lines()->delete();
            $bill->lines()->createMany(array_map(fn (array $line): array => [
                'item_id' => $itemIds[(string) $line['id']] ?? null,
                'name' => $line['name'],
                'qty' => (int) $line['qty'],
                'ret' => min((int) ($line['ret'] ?? 0), (int) $line['qty']),
                'price' => round((float) $line['price'], 2),
            ], $entry['lines']));
        }

        if (is_array($entry['pay']['log'] ?? null)) {
            $this->addPayments($bill, $entry['pay']['log'], $actor);
        }

        $this->refreshTotals($bill);
    }

    /**
     * Record the payment log entries that are not stored yet. The browser sends the whole recent log
     * on every payment, so entries already saved (same kind, amount and second) are skipped.
     *
     * @param  list<array{kind: string, amount: float|int|string, at: int|float}>  $log
     */
    private function addPayments(Bill $bill, array $log, ?User $actor): void
    {
        $signature = fn (string $kind, float $amount, int $timestamp): string => $kind.'|'.number_format($amount, 2, '.', '').'|'.$timestamp;

        $stored = $bill->payments()->get()
            ->countBy(fn (Payment $payment): string => $signature($payment->kind, (float) $payment->amount, $payment->created_at->getTimestamp()))
            ->all();

        foreach ($log as $entry) {
            $at = DocumentKey::timestamp($entry['at']);
            $key = $signature($entry['kind'], round((float) $entry['amount'], 2), $at->getTimestamp());

            if (($stored[$key] ?? 0) > 0) {
                $stored[$key]--;

                continue;
            }

            $payment = new Payment([
                'kind' => $entry['kind'],
                'amount' => round((float) $entry['amount'], 2),
                'created_by' => $actor?->id,
            ]);
            $payment->created_at = $at;
            $bill->payments()->save($payment);
        }
    }

    /**
     * Keep the stored total and payment status in step with the browser app's payment rules.
     */
    private function refreshTotals(Bill $bill): void
    {
        $total = round($bill->lines()->get()->sum(fn (BillLine $line): float => max(0, $line->qty - $line->ret) * (float) $line->price), 2);
        $paid = match ($bill->pay_method) {
            null => 0.0,
            'cash' => $total,
            default => min($total, (float) $bill->paid),
        };
        $balance = max(0, round($total - $paid, 2));

        $bill->total = $total;
        $bill->status = match (true) {
            $bill->pay_method === null => 'due',
            $balance <= 0 => 'paid',
            $paid > 0 => 'partial',
            default => 'credit',
        };
        $bill->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function entry(Bill $bill): array
    {
        $entry = ['lines' => $bill->lines->map(fn (BillLine $line): array => $this->line($line))->values()->all()];

        if ($bill->shop_name !== null) {
            $entry['shopName'] = $bill->shop_name;
        }

        if ($bill->generated_at !== null) {
            $entry['at'] = DocumentKey::milliseconds($bill->generated_at);
        }

        if ($bill->edited_at !== null) {
            $entry['editedAt'] = DocumentKey::milliseconds($bill->edited_at);
        }

        if ($bill->pay_method !== null) {
            $entry['pay'] = [
                'method' => $bill->pay_method,
                'paid' => (float) $bill->paid,
                'at' => DocumentKey::milliseconds($bill->paid_at),
                'log' => $bill->payments->map(fn (Payment $payment): array => [
                    'kind' => $payment->kind,
                    'amount' => (float) $payment->amount,
                    'at' => DocumentKey::milliseconds($payment->created_at),
                ])->values()->all(),
            ];
        }

        return $entry;
    }

    /**
     * @return array{id: string, name: string, qty: int, ret: int, price: float}
     */
    private function line(BillLine $line): array
    {
        return [
            'id' => $line->item?->source_key ?? 'line-'.$line->id,
            'name' => $line->name,
            'qty' => (int) $line->qty,
            'ret' => (int) $line->ret,
            'price' => (float) $line->price,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $before
     * @param  list<array<string, mixed>>  $after
     * @return list<string>
     */
    private function editPermissions(array $before, array $after): array
    {
        if (count($before) !== count($after)) {
            return ['bills_generate'];
        }

        $permissions = [];

        foreach (array_values($after) as $index => $line) {
            $old = $before[$index];

            if ((string) $old['id'] !== (string) $line['id'] || $old['name'] !== $line['name'] || $old['qty'] !== (int) $line['qty']) {
                return ['bills_generate'];
            }

            if (round($old['price'], 2) !== round((float) $line['price'], 2)) {
                $permissions[] = 'bills_prices';
            }

            if ($old['ret'] !== (int) ($line['ret'] ?? 0)) {
                $permissions[] = 'bills_returns';
            }
        }

        return $permissions;
    }
}
