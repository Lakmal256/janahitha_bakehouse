<?php

namespace Tests\Feature;

use App\Livewire\Admin\Items as ItemManagement;
use App\Livewire\Admin\Routes as RouteManagement;
use App\Livewire\Admin\Shops as ShopManagement;
use App\Models\Item;
use App\Models\Route as BakeryRoute;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminSetupTest extends TestCase
{
    use RefreshDatabase;

    private function createOwner(): User
    {
        return User::create([
            'name' => 'Owner',
            'username' => 'owner',
            'password' => 'StrongPass123!',
            'role' => 'admin',
            'perms' => User::permissionsForRole('admin'),
            'all_routes' => true,
            'active' => true,
            'is_owner' => true,
        ]);
    }

    public function test_admin_can_create_edit_and_cannot_delete_route_with_shops(): void
    {
        $this->seed();
        $this->actingAs($this->createOwner());

        Livewire::test(RouteManagement::class)
            ->set('name', 'Evening Route')
            ->set('driver', 'Kasun')
            ->call('save')
            ->assertHasNoErrors();

        $route = BakeryRoute::where('name', 'Evening Route')->firstOrFail();
        $this->assertSame('Kasun', $route->driver);

        Livewire::test(RouteManagement::class)
            ->call('edit', $route->id)
            ->set('name', 'Night Route')
            ->set('driver', 'Nuwan')
            ->call('save')
            ->assertHasNoErrors();

        $route->refresh();
        $this->assertSame('Night Route', $route->name);
        $this->assertSame('Nuwan', $route->driver);

        $shop = Shop::where('source_key', 'sujith-01')->firstOrFail();
        Livewire::test(RouteManagement::class)
            ->call('delete', $shop->route_id)
            ->assertHasErrors('delete');
        $this->assertDatabaseHas('routes', ['id' => $shop->route_id]);
    }

    public function test_admin_can_edit_item_default_price_and_item_in_use_cannot_be_deleted(): void
    {
        $this->seed();
        $this->actingAs($this->createOwner());
        $item = Item::where('source_key', 'fish')->firstOrFail();

        Livewire::test(ItemManagement::class)
            ->set("edits.{$item->id}.name", 'Fish Bun')
            ->set("edits.{$item->id}.default_price", '125.50')
            ->call('save', $item->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'name' => 'Fish Bun',
            'default_price' => '125.50',
        ]);

        Livewire::test(ItemManagement::class)
            ->call('delete', $item->id)
            ->assertHasErrors('delete');
        $this->assertDatabaseHas('items', ['id' => $item->id]);
    }

    public function test_shop_setup_saves_usual_items_price_overrides_and_paper_order(): void
    {
        $this->seed();
        $this->actingAs($this->createOwner());
        $shop = Shop::where('source_key', 'sujith-03')->firstOrFail();
        $fish = Item::where('source_key', 'fish')->firstOrFail();
        $bread = Item::where('source_key', 'bread')->firstOrFail();
        $kala = Item::where('source_key', 'kala')->firstOrFail();

        Livewire::test(ShopManagement::class)
            ->set('shopId', $shop->id)
            ->call('toggleItem', $bread->id, false)
            ->set("itemPrices.{$fish->id}", '145.25')
            ->call('moveItem', $kala->id, -1)
            ->call('save')
            ->assertHasNoErrors();

        $saved = $shop->fresh()->items;
        $this->assertSame(
            ['Fish', 'Kimbula', 'Jam', 'Sini', 'Multi', 'Dagara', 'Sini Sambal', 'Keels', 'Pastry', 'Kala', 'Saman'],
            $saved->pluck('name')->all(),
        );
        $this->assertEquals(145.25, $saved->firstWhere('id', $fish->id)->pivot->price);
        $this->assertNull($saved->firstWhere('id', $kala->id)->pivot->price);
        $this->assertDatabaseMissing('shop_items', ['shop_id' => $shop->id, 'item_id' => $bread->id]);
    }

    public function test_shop_copy_save_next_and_bulk_price_apply_work(): void
    {
        $this->seed();
        $this->actingAs($this->createOwner());

        $source = Shop::where('source_key', 'sujith-01')->firstOrFail();
        $target = Shop::where('source_key', 'sujith-02')->firstOrFail();
        $fish = Item::where('source_key', 'fish')->firstOrFail();
        $source->items()->updateExistingPivot($fish->id, ['price' => '150.00']);

        Livewire::test(ShopManagement::class)
            ->set('shopId', $target->id)
            ->set('copyShopId', $source->id)
            ->call('copyFrom')
            ->assertHasNoErrors()
            ->assertSet('selectedItemIds', $source->items()->pluck('items.id')->all())
            ->assertSet("itemPrices.{$fish->id}", '150.00')
            ->call('saveAndNext')
            ->assertHasNoErrors()
            ->assertSet('shopId', Shop::where('source_key', 'sujith-03')->value('id'));

        $otherShop = Shop::where('source_key', 'boss-01')->firstOrFail();
        $otherShop->items()->syncWithoutDetaching([$fish->id => ['sort' => 1, 'price' => null]]);

        Livewire::test(ShopManagement::class)
            ->set('shopId', $source->id)
            ->set("itemPrices.{$fish->id}", '175.00')
            ->call('applyPricesToAllShops')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('shop_items', ['shop_id' => $target->id, 'item_id' => $fish->id, 'price' => '175.00']);
        $this->assertDatabaseHas('shop_items', ['shop_id' => $otherShop->id, 'item_id' => $fish->id, 'price' => '175.00']);
    }

    public function test_admin_can_create_shop_on_a_selected_route(): void
    {
        $this->seed();
        $this->actingAs($this->createOwner());
        $route = BakeryRoute::where('source_key', 'route4')->firstOrFail();

        Livewire::test(ShopManagement::class)
            ->set('newShopName', 'New Route 4 Shop')
            ->set('newShopRouteId', $route->id)
            ->call('createShop')
            ->assertHasNoErrors()
            ->assertSet('shopName', 'New Route 4 Shop')
            ->assertSet('routeId', $route->id);

        $this->assertDatabaseHas('shops', [
            'name' => 'New Route 4 Shop',
            'route_id' => $route->id,
            'sort' => 13,
        ]);
    }

    public function test_salesman_cannot_open_or_mutate_admin_shop_setup(): void
    {
        $this->seed();
        $route = BakeryRoute::where('source_key', 'sujith')->firstOrFail();
        $salesman = User::create([
            'name' => 'Sales Person',
            'username' => 'salesperson',
            'password' => 'StrongPass123!',
            'role' => 'salesman',
            'perms' => User::permissionsForRole('salesman'),
            'all_routes' => false,
            'active' => true,
        ]);
        $salesman->routes()->sync([$route->id]);
        $this->actingAs($salesman);

        $this->get('/admin/shops')->assertForbidden();
        Livewire::test(ShopManagement::class)->assertForbidden();
    }
}
