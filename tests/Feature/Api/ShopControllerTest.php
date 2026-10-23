<?php

namespace Tests\Feature\Api;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_put_creates_a_shop_with_its_items_in_order_and_its_own_prices(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->role('manager')->create());

        $this->putJson('/api/shops/s-new', [
            'name' => 'Corner Shop',
            'routeId' => 'boss',
            'items' => ['jam', 'fish', 'bread'],
            'prices' => ['fish' => 120, 'jam' => null],
            'order' => 13,
        ])->assertCreated();

        $this->getJson('/api/shops')
            ->assertOk()
            ->assertJsonFragment([
                'id' => 's-new',
                'name' => 'Corner Shop',
                'routeId' => 'boss',
                'items' => ['jam', 'fish', 'bread'],
                'prices' => ['fish' => 120],
                'order' => 13,
            ]);
    }

    public function test_patch_merges_prices_only_for_items_the_shop_sells(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->owner()->create());
        $this->putJson('/api/shops/s-new', ['name' => 'Corner', 'routeId' => 'boss', 'items' => ['fish', 'bread'], 'prices' => ['fish' => 100]]);

        $this->patchJson('/api/shops/s-new', ['prices' => ['bread' => 55.5, 'jam' => 99]])
            ->assertOk()
            ->assertJsonPath('data.prices', ['fish' => 100, 'bread' => 55.5])
            ->assertJsonPath('data.items', ['fish', 'bread']);
    }

    public function test_put_rejects_an_unknown_item(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->owner()->create());

        $this->putJson('/api/shops/s-new', ['name' => 'Corner', 'routeId' => 'boss', 'items' => ['no-such-item']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0' => 'The selected items.0 is invalid.']);
    }

    public function test_saving_a_shop_without_the_shops_permission_returns_403(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->role('salesman')->create());

        $this->patchJson('/api/shops/sujith-01', ['prices' => ['fish' => 1]])->assertForbidden();
    }

    public function test_delete_archives_a_shop_with_orders_and_hides_it(): void
    {
        $this->seed();
        $owner = User::factory()->owner()->create();
        $this->actingAs($owner);
        $this->patchJson('/api/days/2026-10-14_sujith', ['shops' => ['sujith-01' => ['q' => ['fish' => 2]]]])->assertOk();

        $this->deleteJson('/api/shops/sujith-01')
            ->assertOk()
            ->assertJsonPath('data.archived', true);

        $this->assertFalse(Shop::where('source_key', 'sujith-01')->sole()->active);
        $this->assertNotContains('sujith-01', collect($this->getJson('/api/shops')->json('data'))->pluck('id'));
        $this->getJson('/api/days?date=2026-10-14')->assertJsonPath('data.0.shops.sujith-01.q.fish', 2);
    }

    public function test_delete_removes_a_shop_without_history(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->owner()->create());

        $this->deleteJson('/api/shops/sujith-02')
            ->assertOk()
            ->assertJsonPath('data.archived', false);

        $this->assertDatabaseMissing('shops', ['source_key' => 'sujith-02']);
    }
}
