<?php

namespace Tests\Feature;

use App\Livewire\Admin\Users as UserManagement;
use App\Models\Bill;
use App\Models\Route as BakeryRoute;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

class PhaseTwoAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_run_setup_creates_owner_and_then_closes(): void
    {
        $this->get('/')->assertRedirect(route('setup'));
        $this->get('/setup')->assertOk()->assertSee('Create admin account');

        $this->post('/setup', [
            'name' => 'Bakehouse Owner',
            'username' => 'owner',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ])->assertRedirect(route('dashboard'));

        $owner = User::where('username', 'owner')->firstOrFail();
        $this->assertTrue($owner->is_owner);
        $this->assertTrue($owner->canPermission('users'));
        $this->assertTrue(password_verify('StrongPass123!', $owner->password));
        $this->assertAuthenticatedAs($owner);
        $this->get('/register')->assertNotFound();

        $this->post('/logout')->assertRedirect(route('login'));
        $this->get('/setup')->assertRedirect(route('login'));

        Livewire::test('pages.auth.login')
            ->set('form.username', 'owner')
            ->set('form.password', 'StrongPass123!')
            ->call('login')
            ->assertRedirect(route('dashboard'));
    }

    public function test_salesman_route_scopes_hide_other_route_shops_and_bills(): void
    {
        $this->seed();

        $assignedRoute = BakeryRoute::where('source_key', 'sujith')->firstOrFail();
        $otherRoute = BakeryRoute::where('source_key', 'boss')->firstOrFail();
        $salesman = User::create([
            'name' => 'Sales Person',
            'username' => 'salesperson',
            'password' => 'StrongPass123!',
            'role' => 'salesman',
            'perms' => User::permissionsForRole('salesman'),
            'all_routes' => false,
            'active' => true,
        ]);
        $salesman->routes()->sync([$assignedRoute->id]);

        Route::middleware(['web', 'auth', 'active', 'route.access'])
            ->get('/test-route-access/{route}', fn () => response('', 204));

        $visibleShops = Shop::visibleTo($salesman)->get();
        $this->assertNotEmpty($visibleShops);
        $this->assertTrue($visibleShops->every(fn (Shop $shop): bool => $shop->route_id === $assignedRoute->id));
        $this->assertFalse($salesman->canAccessRoute($otherRoute));
        $this->actingAs($salesman)->get("/test-route-access/{$assignedRoute->id}")->assertNoContent();
        $this->actingAs($salesman)->get("/test-route-access/{$otherRoute->id}")->assertForbidden();

        $assignedShop = Shop::where('route_id', $assignedRoute->id)->firstOrFail();
        $otherShop = Shop::where('route_id', $otherRoute->id)->firstOrFail();
        Bill::create([
            'date' => '2026-10-12',
            'route_id' => $assignedRoute->id,
            'shop_id' => $assignedShop->id,
            'total' => '1250.00',
            'paid' => '0.00',
            'status' => 'due',
        ]);
        Bill::create([
            'date' => '2026-10-12',
            'route_id' => $otherRoute->id,
            'shop_id' => $otherShop->id,
            'total' => '750.00',
            'paid' => '0.00',
            'status' => 'due',
        ]);

        $visibleBills = Bill::visibleTo($salesman)->get();
        $this->assertCount(1, $visibleBills);
        $this->assertSame($assignedRoute->id, $visibleBills->first()->route_id);
        $this->assertTrue(Gate::forUser($salesman)->allows('orders'));
        $this->assertTrue(Gate::forUser($salesman)->allows('bills_returns'));
        $this->assertFalse(Gate::forUser($salesman)->allows('shops'));
        $this->assertFalse(Gate::forUser($salesman)->allows('bills_prices'));
        $this->assertFalse(Gate::forUser($salesman)->allows('users'));

        $viewer = User::create([
            'name' => 'Read Only',
            'username' => 'viewer',
            'password' => 'StrongPass123!',
            'role' => 'viewer',
            'all_routes' => true,
            'active' => true,
        ]);
        $this->assertFalse(Gate::forUser($viewer)->allows('orders'));
        $this->assertTrue(Gate::forUser($viewer)->allows('bills_view'));
        $this->assertTrue(Gate::forUser($viewer)->allows('reports'));

        $manager = User::create([
            'name' => 'Manager',
            'username' => 'manager',
            'password' => 'StrongPass123!',
            'role' => 'manager',
            'all_routes' => true,
            'active' => true,
        ]);
        foreach (User::PERMISSIONS as $permission) {
            $this->assertSame($permission !== 'users', Gate::forUser($manager)->allows($permission));
        }
    }

    public function test_blocking_user_deletes_sessions_and_protected_requests_end(): void
    {
        $owner = User::create([
            'name' => 'Owner',
            'username' => 'owner',
            'password' => 'StrongPass123!',
            'role' => 'admin',
            'perms' => User::permissionsForRole('admin'),
            'all_routes' => true,
            'active' => true,
            'is_owner' => true,
        ]);
        $staff = User::create([
            'name' => 'Staff Member',
            'username' => 'staff',
            'password' => 'StrongPass123!',
            'role' => 'salesman',
            'perms' => User::permissionsForRole('salesman'),
            'all_routes' => true,
            'active' => true,
        ]);
        DB::table('sessions')->insert([
            'id' => 'staff-session',
            'user_id' => $staff->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => 'payload',
            'last_activity' => time(),
        ]);

        $this->actingAs($owner);
        Livewire::test(UserManagement::class)->call('toggleBlock', $staff->id);

        $this->assertDatabaseHas('users', ['id' => $staff->id, 'active' => false]);
        $this->assertDatabaseMissing('sessions', ['id' => 'staff-session']);

        $this->actingAs($staff->fresh())
            ->get('/dashboard')
            ->assertRedirect(route('login'));

        Livewire::test('pages.auth.login')
            ->set('form.username', 'staff')
            ->set('form.password', 'StrongPass123!')
            ->call('login')
            ->assertHasErrors('form.username');
    }

    public function test_admin_can_create_user_with_custom_permissions_and_route_assignment(): void
    {
        $this->seed();

        $owner = User::create([
            'name' => 'Owner',
            'username' => 'owner',
            'password' => 'StrongPass123!',
            'role' => 'admin',
            'perms' => User::permissionsForRole('admin'),
            'all_routes' => true,
            'active' => true,
            'is_owner' => true,
        ]);
        $route = BakeryRoute::where('source_key', 'sujith')->firstOrFail();

        $this->actingAs($owner);
        Livewire::test(UserManagement::class)
            ->set('name', 'New Sales Person')
            ->set('username', 'newsales')
            ->set('password', 'StrongPass123!')
            ->set('password_confirmation', 'StrongPass123!')
            ->set('role', 'salesman')
            ->set('allRoutes', false)
            ->set('selectedRoutes', [$route->id])
            ->set('perms.bills_prices', true)
            ->call('save')
            ->assertHasNoErrors();

        $salesman = User::where('username', 'newsales')->firstOrFail();
        $this->assertSame([$route->id], $salesman->routes()->pluck('routes.id')->all());
        $this->assertTrue($salesman->canPermission('bills_prices'));
        $this->assertFalse($salesman->canPermission('users'));
    }
}
