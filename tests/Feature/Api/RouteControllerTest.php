<?php

namespace Tests\Feature\Api;

use App\Models\Route as BakeryRoute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_routes_as_app_documents_in_route_order(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->owner()->create());

        $this->getJson('/api/routes')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('data.0', ['id' => 'sujith', 'name' => 'Sujith', 'driver' => 'Sujith', 'order' => 1]);
    }

    public function test_index_shows_a_route_limited_salesman_only_their_routes(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->role('salesman')->onRoutes(['boss'])->create());

        $this->getJson('/api/routes')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 'boss');
    }

    public function test_put_creates_a_route_and_patch_changes_only_the_given_fields(): void
    {
        $this->actingAs(User::factory()->role('manager')->create());

        $this->putJson('/api/routes/r-evening', ['name' => ' Evening ', 'driver' => 'Kasun', 'order' => 6])
            ->assertCreated()
            ->assertJsonPath('data', ['id' => 'r-evening', 'name' => 'Evening', 'driver' => 'Kasun', 'order' => 6]);

        $this->patchJson('/api/routes/r-evening', ['name' => 'Night', 'driver' => ''])
            ->assertOk()
            ->assertJsonPath('data', ['id' => 'r-evening', 'name' => 'Night', 'driver' => '', 'order' => 6]);

        $this->assertDatabaseHas('routes', ['source_key' => 'r-evening', 'name' => 'Night', 'driver' => null, 'sort' => 6]);
    }

    public function test_patch_returns_404_for_an_unknown_route(): void
    {
        $this->actingAs(User::factory()->owner()->create());

        $this->patchJson('/api/routes/missing', ['name' => 'Night'])->assertNotFound();
    }

    public function test_saving_a_route_without_the_routes_permission_returns_403(): void
    {
        $this->actingAs(User::factory()->role('salesman')->create());

        $this->putJson('/api/routes/r-evening', ['name' => 'Evening'])->assertForbidden();

        $this->assertDatabaseMissing('routes', ['source_key' => 'r-evening']);
    }

    public function test_put_requires_a_name(): void
    {
        $this->actingAs(User::factory()->owner()->create());

        $this->putJson('/api/routes/r-evening', ['driver' => 'Kasun'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name' => 'The name field is required.']);
    }

    public function test_delete_refuses_a_route_that_still_has_shops(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->owner()->create());

        $this->deleteJson('/api/routes/sujith')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['route' => 'Move or delete this route\'s shops before deleting the route.']);

        $this->assertDatabaseHas('routes', ['source_key' => 'sujith']);
    }

    public function test_delete_removes_an_empty_route(): void
    {
        BakeryRoute::create(['source_key' => 'r-empty', 'name' => 'Empty', 'sort' => 1]);
        $this->actingAs(User::factory()->owner()->create());

        $this->deleteJson('/api/routes/r-empty')->assertNoContent();

        $this->assertDatabaseMissing('routes', ['source_key' => 'r-empty']);
    }
}
