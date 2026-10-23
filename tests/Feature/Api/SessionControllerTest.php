<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SessionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_reports_that_setup_is_needed_before_any_account_exists(): void
    {
        $this->getJson('/api/session')
            ->assertOk()
            ->assertExactJson(['data' => ['user' => null, 'needsSetup' => true]]);
    }

    public function test_login_signs_in_and_returns_the_account_without_password_data(): void
    {
        $user = User::factory()->role('salesman')->create(['username' => 'kasun', 'password' => 'Bakery-2026']);

        $this->postJson('/api/session', ['username' => ' Kasun ', 'password' => 'Bakery-2026'])
            ->assertOk()
            ->assertJsonPath('data.id', $user->source_key)
            ->assertJsonPath('data.username', 'kasun')
            ->assertJsonPath('data.perms.orders', true)
            ->assertJsonPath('data.perms.users', false)
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.legacy_password_hash');

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_rejects_a_wrong_password_with_422(): void
    {
        User::factory()->create(['username' => 'kasun', 'password' => 'Bakery-2026']);

        $this->postJson('/api/session', ['username' => 'kasun', 'password' => 'wrong-password'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['username' => 'These credentials do not match our records.']);

        $this->assertGuest();
    }

    public function test_login_rejects_a_blocked_account_with_422(): void
    {
        User::factory()->blocked()->create(['username' => 'kasun', 'password' => 'Bakery-2026']);

        $this->postJson('/api/session', ['username' => 'kasun', 'password' => 'Bakery-2026'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['username' => 'This account is turned off. Ask the admin.']);

        $this->assertGuest();
    }

    public function test_login_returns_429_after_five_failed_attempts(): void
    {
        User::factory()->create(['username' => 'kasun', 'password' => 'Bakery-2026']);

        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/session', ['username' => 'kasun', 'password' => 'wrong-password'])->assertUnprocessable();
        }

        $this->postJson('/api/session', ['username' => 'kasun', 'password' => 'Bakery-2026'])->assertTooManyRequests();
        $this->assertGuest();
    }

    public function test_imported_browser_password_works_once_and_is_upgraded_to_a_standard_hash(): void
    {
        $user = User::factory()->create(['username' => 'nimal']);
        $user->forceFill([
            'legacy_password_hash' => hash('sha256', 'salt123:old-browser-pass'),
            'legacy_password_salt' => 'salt123',
        ])->save();

        $this->postJson('/api/session', ['username' => 'nimal', 'password' => 'old-browser-pass'])->assertOk();

        $user->refresh();
        $this->assertNull($user->legacy_password_hash);
        $this->assertNull($user->legacy_password_salt);
        $this->assertTrue(Hash::check('old-browser-pass', $user->password));
    }

    public function test_logout_ends_the_session(): void
    {
        $this->actingAs(User::factory()->create());

        $this->deleteJson('/api/session')->assertNoContent();

        $this->assertGuest();
    }

    public function test_data_endpoints_return_401_to_guests(): void
    {
        $this->getJson('/api/routes')->assertUnauthorized();
    }

    public function test_data_endpoints_sign_out_a_blocked_account_with_401(): void
    {
        $this->actingAs(User::factory()->blocked()->create());

        $this->getJson('/api/routes')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'This account is blocked. Contact the administrator.');

        $this->assertGuest();
    }

    public function test_first_run_setup_from_the_app_creates_the_owner_and_returns_201(): void
    {
        $this->postJson('/setup', [
            'name' => 'Bakehouse Owner',
            'username' => 'owner',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ])
            ->assertCreated()
            ->assertJsonPath('data.username', 'owner')
            ->assertJsonPath('data.owner', true);

        $this->assertAuthenticatedAs(User::where('username', 'owner')->sole());
        $this->getJson('/api/session')->assertJsonPath('data.needsSetup', false);
    }
}
