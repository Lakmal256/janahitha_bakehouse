<?php

namespace Tests\Feature\Api;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_put_saves_the_items_in_list_order_and_keeps_items_left_out(): void
    {
        Item::create(['source_key' => 'old', 'name' => 'Old item', 'default_price' => 10, 'sort' => 1]);
        Item::create(['source_key' => 'fish', 'name' => 'Fish', 'default_price' => 50, 'sort' => 2]);
        $this->actingAs(User::factory()->role('manager')->create());

        $this->putJson('/api/config/catalog', ['items' => [
            ['id' => 'bun', 'name' => 'Bun', 'price' => 40],
            ['id' => 'fish', 'name' => 'Fish', 'price' => 65.5],
        ]])->assertOk();

        $this->getJson('/api/config')
            ->assertOk()
            ->assertJsonPath('data.0.id', 'catalog')
            ->assertJsonPath('data.0.items', [
                ['id' => 'bun', 'name' => 'Bun', 'price' => 40],
                ['id' => 'fish', 'name' => 'Fish', 'price' => 65.5],
                ['id' => 'old', 'name' => 'Old item', 'price' => 10],
            ]);
    }

    public function test_saving_items_without_the_items_permission_returns_403(): void
    {
        $this->actingAs(User::factory()->role('salesman')->create());

        $this->putJson('/api/config/catalog', ['items' => [['id' => 'bun', 'name' => 'Bun', 'price' => 40]]])->assertForbidden();

        $this->assertDatabaseMissing('items', ['source_key' => 'bun']);
    }

    public function test_put_rejects_a_negative_price(): void
    {
        $this->actingAs(User::factory()->owner()->create());

        $this->putJson('/api/config/catalog', ['items' => [['id' => 'bun', 'name' => 'Bun', 'price' => -1]]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.price' => 'The items.0.price field must be at least 0.']);
    }

    public function test_put_to_another_config_document_returns_404(): void
    {
        $this->actingAs(User::factory()->owner()->create());

        $this->putJson('/api/config/settings', ['items' => [['id' => 'bun', 'name' => 'Bun']]])->assertNotFound();
    }

    public function test_delete_removes_an_unused_item_and_refuses_an_item_used_by_a_shop(): void
    {
        $this->seed();
        Item::create(['source_key' => 'unused-item', 'name' => 'Unused', 'default_price' => 25, 'sort' => 99]);
        $this->actingAs(User::factory()->owner()->create());

        $this->deleteJson('/api/items/unused-item')->assertNoContent();
        $this->assertDatabaseMissing('items', ['source_key' => 'unused-item']);

        $this->deleteJson('/api/items/fish')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['item']);
        $this->assertDatabaseHas('items', ['source_key' => 'fish']);
    }
}
