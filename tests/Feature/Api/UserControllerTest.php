<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_every_account_for_user_managers_without_password_data(): void
    {
        $owner = User::factory()->owner()->create(['name' => 'Ashan']);
        User::factory()->role('salesman')->create(['name' => 'Kasun']);
        $this->actingAs($owner);

        $response = $this->getJson('/api/users')->assertOk()->assertJsonCount(2, 'data');

        $this->assertSame(['Ashan', 'Kasun'], array_column($response->json('data'), 'name'));
        $response->assertJsonMissingPath('data.0.password')->assertJsonMissingPath('data.0.passHash');
    }

    public function test_index_shows_other_accounts_only_their_own_account(): void
    {
        User::factory()->owner()->create();
        $salesman = User::factory()->role('salesman')->create();
        $this->actingAs($salesman);

        $this->getJson('/api/users')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $salesman->source_key);
    }

    public function test_put_creates_an_account_with_a_hashed_password_permissions_and_routes(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->owner()->create());

        $this->putJson('/api/users/u-kasun', [
            'name' => 'Kasun',
            'username' => 'kasun',
            'password' => 'Bakery-2026',
            'perms' => ['orders' => true, 'bills_view' => true, 'reports' => false],
            'routes' => ['boss', 'mama'],
            'active' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('data.id', 'u-kasun')
            ->assertJsonPath('data.routes', ['boss', 'mama'])
            ->assertJsonPath('data.perms.orders', true)
            ->assertJsonPath('data.perms.users', false);

        $user = User::where('source_key', 'u-kasun')->sole();
        $this->assertTrue(Hash::check('Bakery-2026', $user->password));
        $this->assertFalse($user->all_routes);
        $this->assertFalse($user->canPermission('reports'));
    }

    public function test_put_requires_a_password_of_at_least_8_characters_for_a_new_account(): void
    {
        $this->actingAs(User::factory()->owner()->create());

        $this->putJson('/api/users/u-kasun', ['name' => 'Kasun', 'username' => 'kasun', 'password' => 'short'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password' => 'The password field must be at least 8 characters.']);
    }

    public function test_put_rejects_a_username_that_is_taken(): void
    {
        User::factory()->create(['username' => 'kasun']);
        $this->actingAs(User::factory()->owner()->create());

        $this->putJson('/api/users/u-other', ['name' => 'Other', 'username' => 'kasun', 'password' => 'Bakery-2026'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['username' => 'The username has already been taken.']);
    }

    public function test_blocking_an_account_keeps_its_password_and_ends_its_sessions(): void
    {
        $user = User::factory()->role('salesman')->create(['password' => 'Bakery-2026']);
        DB::table('sessions')->insert(['id' => 'session-1', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        $this->actingAs(User::factory()->owner()->create());

        $this->putJson("/api/users/{$user->source_key}", [
            'name' => $user->name,
            'username' => $user->username,
            'perms' => User::permissionsForRole('salesman'),
            'routes' => 'all',
            'active' => false,
        ])->assertOk()->assertJsonPath('data.active', false);

        $this->assertTrue(Hash::check('Bakery-2026', $user->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
    }

    public function test_an_admin_who_is_not_the_owner_cannot_edit_the_owner(): void
    {
        $owner = User::factory()->owner()->create();
        $this->actingAs(User::factory()->role('admin')->create());

        $this->putJson("/api/users/{$owner->source_key}", [
            'name' => 'Taken over',
            'username' => $owner->username,
            'password' => 'Takeover-2026',
        ])->assertForbidden();

        $this->assertNotSame('Taken over', $owner->fresh()->name);
    }

    public function test_the_owner_keeps_full_access_whatever_the_form_sends(): void
    {
        $owner = User::factory()->owner()->create();
        $this->actingAs($owner);

        $this->putJson("/api/users/{$owner->source_key}", [
            'name' => 'Owner',
            'username' => $owner->username,
            'perms' => ['users' => false],
            'routes' => 'all',
        ])->assertOk()->assertJsonPath('data.perms.users', true)->assertJsonPath('data.active', true);
    }

    public function test_a_user_manager_cannot_block_their_own_account(): void
    {
        $admin = User::factory()->role('admin')->create();
        $this->actingAs($admin);

        $this->putJson("/api/users/{$admin->source_key}", [
            'name' => $admin->name,
            'username' => $admin->username,
            'perms' => User::permissionsForRole('admin'),
            'routes' => 'all',
            'active' => false,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['active' => 'You cannot block your own account.']);
    }

    public function test_saving_accounts_without_the_users_permission_returns_403(): void
    {
        $this->actingAs(User::factory()->role('manager')->create());

        $this->putJson('/api/users/u-new', ['name' => 'New', 'username' => 'newuser', 'password' => 'Bakery-2026'])->assertForbidden();
    }

    public function test_delete_removes_another_account_but_not_your_own(): void
    {
        $owner = User::factory()->owner()->create();
        $salesman = User::factory()->role('salesman')->create();
        $this->actingAs($owner);

        $this->deleteJson("/api/users/{$owner->source_key}")->assertForbidden();
        $this->deleteJson("/api/users/{$salesman->source_key}")->assertNoContent();

        $this->assertModelMissing($salesman);
        $this->assertModelExists($owner);
    }
}
