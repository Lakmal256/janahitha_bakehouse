<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Route as BakeryRoute;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BakerySeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_bakery_seed_imports_routes_shops_and_catalog(): void
    {
        $this->seed();

        $this->assertDatabaseCount('routes', 5);
        $this->assertDatabaseCount('shops', 55);
        $this->assertDatabaseCount('items', 33);
        $this->assertDatabaseCount('shop_items', 537);

        $routeFour = BakeryRoute::where('source_key', 'route4')->firstOrFail();
        $this->assertSame('Route 4', $routeFour->name);
        $this->assertNull($routeFour->driver);

        $officeShop = Shop::where('source_key', 'route4-09')->firstOrFail();
        $this->assertSame('RO (Office / Ground)', $officeShop->name);
        $this->assertCount(0, $officeShop->items);

        $this->assertSame('Sini Sambal', Item::where('source_key', 'sinisam')->value('name'));
        $this->assertSame('Multi', Item::where('source_key', 'multi')->value('name'));
        $this->assertSame('Keels', Item::where('source_key', 'keels')->value('name'));
        $this->assertSame('* Keels', Item::where('source_key', 'keels_s')->value('name'));
        $this->assertSame('0.00', Item::where('source_key', 'fish')->firstOrFail()->default_price);
    }

    public function test_shop_usual_items_keep_paper_order(): void
    {
        $this->seed();

        $itemNames = Shop::where('source_key', 'sujith-03')
            ->firstOrFail()
            ->items
            ->pluck('name')
            ->all();

        $this->assertSame([
            'Fish', 'Kimbula', 'Jam', 'Sini', 'Multi', 'Dagara',
            'Sini Sambal', 'Keels', 'Pastry', 'Bread', 'Saman', 'Kala',
        ], $itemNames);
    }
}
