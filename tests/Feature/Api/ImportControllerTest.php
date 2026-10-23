<?php

namespace Tests\Feature\Api;

use App\Models\Bill;
use App\Models\Item;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ImportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    /**
     * @return array<string, mixed>
     */
    private function backupData(): array
    {
        return json_decode(file_get_contents(base_path('tests/Fixtures/browser-backup.json')), true)['data'];
    }

    private function import(): TestResponse
    {
        return $this->postJson('/api/imports', ['data' => $this->backupData()]);
    }

    public function test_owner_import_copies_the_browser_data_and_reports_a_passing_check(): void
    {
        $this->actingAs(User::factory()->owner()->create(['username' => 'owner']));

        $this->import()
            ->assertOk()
            ->assertJsonPath('data.verification.ok', true)
            ->assertJsonPath('data.verification.checks', [
                'routes' => ['expected' => 2, 'found' => 2],
                'shops' => ['expected' => 2, 'found' => 2],
                'items' => ['expected' => 2, 'found' => 2],
                'users' => ['expected' => 2, 'found' => 2],
                'orders' => ['expected' => 2, 'found' => 2],
                'bills' => ['expected' => 1, 'found' => 1],
            ])
            ->assertJsonPath('data.summary.items', ['created' => 2, 'updated' => 0, 'missing_created' => 1])
            ->assertJsonPath('data.summary.orders', ['documents' => 2, 'added' => 2, 'lines_added' => 3])
            ->assertJsonPath('data.summary.bills', ['documents' => 1, 'added' => 1, 'payments_added' => 1])
            ->assertJsonPath('data.warnings', [
                'The days record [2026-10-13_sujith] mentions the unknown shop [ghost-shop], which was skipped.',
            ]);

        $this->assertSame('kimbula', Item::where('source_key', 'kimbula')->sole()->name);
        $this->assertSame(['fish', 'bread'], Shop::where('source_key', 'sujith-01')->sole()->items->pluck('source_key')->all());

        $bill = Bill::sole();
        $this->assertSame('885.00', $bill->total);
        $this->assertSame('partial', $bill->status);
        $this->assertSame('Jawaththa', $bill->shop_name);

        $this->getJson('/api/days?date=2026-10-13&routeId=rmg1abcd')
            ->assertJsonPath('data.0.shops.smg1abcd.q', ['kimbula' => 6]);
        $stored = Storage::disk('local')->files('imports');
        $this->assertCount(1, $stored);
        $this->assertSame(['data' => $this->backupData()], json_decode(Storage::disk('local')->get($stored[0]), true));
    }

    public function test_imported_accounts_sign_in_with_their_browser_passwords(): void
    {
        $this->actingAs(User::factory()->owner()->create(['username' => 'owner']));
        $this->import()->assertOk();
        $this->deleteJson('/api/session');

        $this->postJson('/api/session', ['username' => 'kasun', 'password' => 'kasun-browser-pass'])
            ->assertOk()
            ->assertJsonPath('data.id', 'umg1sales')
            ->assertJsonPath('data.routes', ['rmg1abcd'])
            ->assertJsonPath('data.perms.orders', true)
            ->assertJsonPath('data.perms.reports', false);

        $this->assertTrue(Hash::check('kasun-browser-pass', User::where('username', 'kasun')->sole()->password));
    }

    public function test_importing_the_same_backup_twice_adds_no_duplicates(): void
    {
        $this->actingAs(User::factory()->owner()->create(['username' => 'owner']));
        $this->import()->assertOk();

        $this->import()
            ->assertOk()
            ->assertJsonPath('data.verification.ok', true)
            ->assertJsonPath('data.summary.users', ['created' => 0, 'existing' => 2, 'username_taken' => 0, 'skipped' => 0])
            ->assertJsonPath('data.summary.orders.added', 0)
            ->assertJsonPath('data.summary.orders.lines_added', 0)
            ->assertJsonPath('data.summary.bills.added', 0)
            ->assertJsonPath('data.summary.bills.payments_added', 0);

        $this->assertDatabaseCount('routes', 2);
        $this->assertDatabaseCount('shops', 2);
        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('order_lines', 3);
        $this->assertDatabaseCount('bill_lines', 2);
    }

    public function test_an_existing_account_with_the_same_username_is_kept(): void
    {
        $owner = User::factory()->owner()->create(['username' => 'ashan', 'password' => 'Server-pass-2026']);
        $this->actingAs($owner);

        $this->import()
            ->assertOk()
            ->assertJsonPath('data.summary.users.username_taken', 1)
            ->assertJsonFragment(['The username [ashan] already belongs to an account on the server, so that account was kept and the browser account was not imported.']);

        $this->assertTrue(Hash::check('Server-pass-2026', $owner->fresh()->password));
        $this->assertNull($owner->fresh()->legacy_password_hash);
    }

    public function test_import_by_an_account_that_is_not_the_owner_returns_403(): void
    {
        $this->actingAs(User::factory()->role('admin')->create());

        $this->import()->assertForbidden();

        $this->assertDatabaseCount('routes', 0);
    }

    public function test_import_requires_the_backup_data(): void
    {
        $this->actingAs(User::factory()->owner()->create());

        $this->postJson('/api/imports', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['data' => 'The data field is required.']);
    }

    public function test_artisan_migrate_data_command_seeds_and_imports_backup_idempotently(): void
    {
        User::factory()->owner()->create(['username' => 'owner']);

        $this->artisan('bakehouse:migrate-data', ['file' => 'tests/Fixtures/browser-backup.json'])
            ->assertSuccessful();

        $this->assertDatabaseHas('routes', ['source_key' => 'rmg1abcd', 'name' => 'Maradana']);
        $this->assertDatabaseHas('shops', ['source_key' => 'smg1abcd', 'name' => 'Corner Shop']);
        $this->assertDatabaseHas('users', ['username' => 'kasun']);
        $this->assertDatabaseCount('bills', 1);
    }
}
