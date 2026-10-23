<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DayControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_patch_saves_a_shop_order_and_index_returns_the_day_document(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->role('salesman')->create());

        $this->patchJson('/api/days/2026-10-14_sujith', [
            'date' => '2026-10-14',
            'routeId' => 'sujith',
            'shops' => ['sujith-01' => ['q' => ['fish' => '12', 'bread' => 3, 'jam' => ''], 'at' => 1792000000000]],
        ])->assertOk();

        $this->getJson('/api/days?date=2026-10-14')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', '2026-10-14_sujith')
            ->assertJsonPath('data.0.routeId', 'sujith')
            ->assertJsonPath('data.0.shops.sujith-01', ['q' => ['fish' => 12, 'bread' => 3], 'at' => 1792000000000]);
    }

    public function test_patch_removes_cleared_quantities_and_keeps_items_it_does_not_mention(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->owner()->create());
        $this->patchJson('/api/days/2026-10-14_sujith', ['shops' => ['sujith-01' => ['q' => ['fish' => 12, 'bread' => 3]]]]);

        $this->patchJson('/api/days/2026-10-14_sujith', ['shops' => ['sujith-01' => ['q' => ['fish' => 0, 'jam' => 4]]]])->assertOk();

        $this->getJson('/api/days?date=2026-10-14')->assertJsonPath('data.0.shops.sujith-01.q', ['bread' => 3, 'jam' => 4]);
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_index_filters_by_date_range_and_route(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->owner()->create());
        foreach (['2026-10-01', '2026-10-05', '2026-10-09'] as $date) {
            $this->patchJson("/api/days/{$date}_sujith", ['shops' => ['sujith-01' => ['q' => ['fish' => 1]]]]);
        }
        $this->patchJson('/api/days/2026-10-05_boss', ['shops' => ['boss-01' => ['q' => ['fish' => 1]]]]);

        $this->getJson('/api/days?from=2026-10-02&to=2026-10-09&routeId=sujith')
            ->assertOk()
            ->assertJsonPath('data.*.id', ['2026-10-05_sujith', '2026-10-09_sujith']);
    }

    public function test_a_route_limited_salesman_cannot_save_or_see_other_routes(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->owner()->create());
        $this->patchJson('/api/days/2026-10-14_boss', ['shops' => ['boss-01' => ['q' => ['fish' => 5]]]]);
        $this->actingAs(User::factory()->role('salesman')->onRoutes(['sujith'])->create());

        $this->patchJson('/api/days/2026-10-14_boss', ['shops' => ['boss-01' => ['q' => ['fish' => 99]]]])->assertForbidden();
        $this->getJson('/api/days?date=2026-10-14')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_saving_orders_without_the_orders_permission_returns_403(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->role('viewer')->create());

        $this->patchJson('/api/days/2026-10-14_sujith', ['shops' => ['sujith-01' => ['q' => ['fish' => 1]]]])->assertForbidden();

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_patch_rejects_a_shop_from_another_route(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->owner()->create());

        $this->patchJson('/api/days/2026-10-14_sujith', ['shops' => ['boss-01' => ['q' => ['fish' => 1]]]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['shops' => 'The shop [boss-01] is not on this route.']);
    }

    public function test_patch_returns_404_for_an_invalid_document_key(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->owner()->create());

        $this->patchJson('/api/days/2026-02-31_sujith', ['shops' => ['sujith-01' => ['q' => ['fish' => 1]]]])->assertNotFound();
    }
}
