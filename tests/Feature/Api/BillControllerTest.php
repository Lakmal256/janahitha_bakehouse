<?php

namespace Tests\Feature\Api;

use App\Models\Bill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class BillControllerTest extends TestCase
{
    use RefreshDatabase;

    private const LINES = [
        ['id' => 'fish', 'name' => 'Fish', 'qty' => 10, 'ret' => 0, 'price' => 50],
        ['id' => 'bread', 'name' => 'Bread', 'qty' => 4, 'ret' => 0, 'price' => 120],
    ];

    private function generateBill(): TestResponse
    {
        return $this->patchJson('/api/bills/2026-10-14_sujith', [
            'date' => '2026-10-14',
            'routeId' => 'sujith',
            'shops' => ['sujith-01' => ['lines' => self::LINES, 'shopName' => 'Jawaththa', 'at' => 1792000000000]],
        ]);
    }

    public function test_generating_a_bill_stores_its_lines_total_and_due_status(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->role('manager')->create());

        $this->generateBill()
            ->assertOk()
            ->assertJsonPath('data.shops.sujith-01', [
                'lines' => self::LINES,
                'shopName' => 'Jawaththa',
                'at' => 1792000000000,
            ]);

        $bill = Bill::sole();
        $this->assertSame('980.00', $bill->total);
        $this->assertSame('due', $bill->status);
    }

    public function test_payments_record_each_new_log_entry_once_and_update_the_status(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->owner()->create());
        $this->generateBill();
        $first = ['kind' => 'partial', 'amount' => 300, 'at' => 1792000100000];
        $second = ['kind' => 'collect', 'amount' => 680, 'at' => 1792000200000];

        $this->patchJson('/api/bills/2026-10-14_sujith', ['shops' => ['sujith-01' => ['pay' => [
            'method' => 'partial', 'paid' => 300, 'at' => 1792000100000, 'log' => [$first],
        ]]]])->assertOk();
        $this->assertSame('partial', Bill::sole()->status);

        $this->patchJson('/api/bills/2026-10-14_sujith', ['shops' => ['sujith-01' => ['pay' => [
            'method' => 'cash', 'paid' => 980, 'at' => 1792000200000, 'log' => [$first, $second],
        ]]]])
            ->assertOk()
            ->assertJsonPath('data.shops.sujith-01.pay', [
                'method' => 'cash',
                'paid' => 980,
                'at' => 1792000200000,
                'log' => [$first, $second],
            ]);

        $this->assertDatabaseCount('payments', 2);
        $this->assertSame('paid', Bill::sole()->status);
    }

    public function test_a_salesman_can_enter_returns_on_a_bill(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->owner()->create());
        $this->generateBill();
        $this->actingAs(User::factory()->role('salesman')->create());
        $lines = self::LINES;
        $lines[0]['ret'] = 2;

        $this->patchJson('/api/bills/2026-10-14_sujith', ['shops' => ['sujith-01' => ['lines' => $lines, 'editedAt' => 1792000300000]]])
            ->assertOk()
            ->assertJsonPath('data.shops.sujith-01.lines.0.ret', 2)
            ->assertJsonPath('data.shops.sujith-01.editedAt', 1792000300000);

        $this->assertSame('880.00', Bill::sole()->total);
    }

    public function test_changing_a_bill_price_without_the_prices_permission_returns_403(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->owner()->create());
        $this->generateBill();
        $this->actingAs(User::factory()->role('salesman')->create());
        $lines = self::LINES;
        $lines[0]['price'] = 1;

        $this->patchJson('/api/bills/2026-10-14_sujith', ['shops' => ['sujith-01' => ['lines' => $lines, 'editedAt' => 1792000300000]]])
            ->assertForbidden();

        $this->assertSame('980.00', Bill::sole()->total);
    }

    public function test_generating_bills_without_the_generate_permission_returns_403(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->role('salesman')->create());

        $this->generateBill()->assertForbidden();

        $this->assertDatabaseCount('bills', 0);
    }

    public function test_recording_a_payment_without_the_payments_permission_returns_403(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->owner()->create());
        $this->generateBill();
        $this->actingAs(User::factory()->role('viewer')->create());

        $this->patchJson('/api/bills/2026-10-14_sujith', ['shops' => ['sujith-01' => ['pay' => [
            'method' => 'cash', 'paid' => 980, 'log' => [['kind' => 'cash', 'amount' => 980, 'at' => 1792000100000]],
        ]]]])->assertForbidden();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_returns_cannot_exceed_the_quantity(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->owner()->create());
        $lines = self::LINES;
        $lines[1]['ret'] = 5;

        $this->patchJson('/api/bills/2026-10-14_sujith', ['shops' => ['sujith-01' => ['lines' => $lines, 'at' => 1792000000000]]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['shops.sujith-01.lines.1.ret']);
    }
}
