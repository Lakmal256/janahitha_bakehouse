<?php

namespace Tests\Feature\Api;

use App\Models\Employee;
use App\Models\EmployeeLeave;
use App\Models\SalaryAdvance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_view_update_and_delete_employees_independently_of_users(): void
    {
        $admin = User::factory()->role('admin')->create(['source_key' => 'admin_1']);
        $initialUserCount = User::query()->count();

        $this->actingAs($admin)
            ->putJson('/api/employees/emp_kasun', [
                'name' => 'Kasun Perera',
                'phone' => '077 123 4567',
                'location' => 'Maharagama',
            ])
            ->assertCreated()
            ->assertExactJson([
                'data' => [
                    'id' => 'emp_kasun',
                    'name' => 'Kasun Perera',
                    'phone' => '077 123 4567',
                    'location' => 'Maharagama',
                    'createdAt' => Employee::where('source_key', 'emp_kasun')->firstOrFail()->created_at?->getTimestampMs(),
                ],
            ]);

        $this->assertSame($initialUserCount, User::query()->count());
        $this->assertDatabaseHas('employees', [
            'source_key' => 'emp_kasun',
            'name' => 'Kasun Perera',
            'phone' => '077 123 4567',
            'location' => 'Maharagama',
        ]);

        $this->actingAs($admin)
            ->patchJson('/api/employees/emp_kasun', [
                'phone' => '071 987 6543',
                'location' => 'Nugegoda',
            ])
            ->assertOk()
            ->assertJsonPath('data.phone', '071 987 6543')
            ->assertJsonPath('data.location', 'Nugegoda');

        $listResponse = $this->actingAs($admin)
            ->getJson('/api/employees')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Kasun Perera');

        $this->assertArrayNotHasKey('salary', $listResponse->json('data.0'));

        $this->actingAs($admin)
            ->deleteJson('/api/employees/emp_kasun')
            ->assertNoContent();

        $this->assertDatabaseMissing('employees', ['source_key' => 'emp_kasun']);
        $this->assertSame($initialUserCount, User::query()->count());
    }

    public function test_admin_can_record_salary_advances_and_track_approved_monthly_totals(): void
    {
        $owner = User::factory()->owner()->create();
        $kasun = Employee::create([
            'source_key' => 'emp_kasun',
            'name' => 'Kasun Perera',
            'phone' => '077 123 4567',
            'location' => 'Maharagama',
        ]);
        $nimal = Employee::create([
            'source_key' => 'emp_nimal',
            'name' => 'Nimal Silva',
            'phone' => '071 555 4444',
            'location' => 'Kottawa',
        ]);

        $this->actingAs($owner)
            ->putJson('/api/salary-advances/adv_1', [
                'employeeId' => 'emp_kasun',
                'amount' => 5000,
                'requestDate' => '2026-10-10',
                'salaryMonth' => '2026-10',
                'status' => 'approved',
            ])
            ->assertCreated()
            ->assertJsonPath('data.amount', 5000)
            ->assertJsonPath('data.status', 'approved');

        $this->actingAs($owner)
            ->putJson('/api/salary-advances/adv_2', [
                'employeeId' => 'emp_kasun',
                'amount' => 3500.50,
                'requestDate' => '2026-10-18',
                'salaryMonth' => '2026-10',
                'status' => 'pending',
            ])
            ->assertCreated();

        $this->actingAs($owner)
            ->putJson('/api/salary-advances/adv_3', [
                'employeeId' => 'emp_kasun',
                'amount' => 2000,
                'requestDate' => '2026-10-20',
                'salaryMonth' => '2026-10',
                'status' => 'rejected',
            ])
            ->assertCreated();

        $this->actingAs($owner)
            ->putJson('/api/salary-advances/adv_4', [
                'employeeId' => 'emp_nimal',
                'amount' => 4000,
                'requestDate' => '2026-10-12',
                'salaryMonth' => '2026-10',
                'status' => 'approved',
            ])
            ->assertCreated();

        // Approve adv_2 and verify total approved for Kasun in 2026-10 becomes 8500.50
        $this->actingAs($owner)
            ->patchJson('/api/salary-advances/adv_2', [
                'status' => 'approved',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $response = $this->actingAs($owner)
            ->getJson('/api/salary-advances?salaryMonth=2026-10')
            ->assertOk()
            ->assertJsonCount(4, 'data');

        $totals = collect($response->json('meta.approvedTotals'))->keyBy('employeeId');
        $this->assertSame(8500.5, (float) $totals['emp_kasun']['totalApproved']);
        $this->assertSame(2, $totals['emp_kasun']['count']);
        $this->assertSame(4000.0, (float) $totals['emp_nimal']['totalApproved']);
        $this->assertSame(1, $totals['emp_nimal']['count']);

        $this->actingAs($owner)
            ->deleteJson('/api/salary-advances/adv_3')
            ->assertNoContent();

        $this->assertDatabaseMissing('salary_advances', ['source_key' => 'adv_3']);
    }

    public function test_admin_can_record_update_approve_reject_and_view_employee_leave_history(): void
    {
        $admin = User::factory()->role('admin')->create();
        $employee = Employee::create([
            'source_key' => 'emp_1',
            'name' => 'Ruwan Kumara',
            'phone' => '076 222 3333',
            'location' => 'Homagama',
        ]);

        $this->actingAs($admin)
            ->putJson('/api/employee-leaves/lv_1', [
                'employeeId' => $employee->source_key,
                'leaveType' => 'Sick',
                'startDate' => '2026-10-15',
                'endDate' => '2026-10-16',
                'reason' => 'Medical appointment',
                'status' => 'pending',
            ])
            ->assertCreated()
            ->assertJsonPath('data.leaveType', 'Sick')
            ->assertJsonPath('data.status', 'pending');

        // Reject invalid date range where endDate < startDate
        $this->actingAs($admin)
            ->putJson('/api/employee-leaves/lv_invalid', [
                'employeeId' => $employee->source_key,
                'leaveType' => 'Casual',
                'startDate' => '2026-10-20',
                'endDate' => '2026-10-18',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('endDate');

        // Approve leave request
        $this->actingAs($admin)
            ->patchJson('/api/employee-leaves/lv_1', [
                'status' => 'approved',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->actingAs($admin)
            ->getJson('/api/employee-leaves?employeeId=emp_1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.employeeName', 'Ruwan Kumara')
            ->assertJsonPath('data.0.status', 'approved');

        $this->actingAs($admin)
            ->deleteJson('/api/employee-leaves/lv_1')
            ->assertNoContent();

        $this->assertDatabaseMissing('employee_leaves', ['source_key' => 'lv_1']);
    }

    public function test_non_admin_and_guest_users_cannot_access_employees_advances_or_leaves(): void
    {
        $employee = Employee::create([
            'source_key' => 'emp_1',
            'name' => 'Kasun Perera',
            'phone' => '077 123 4567',
            'location' => 'Maharagama',
        ]);
        SalaryAdvance::create([
            'source_key' => 'adv_1',
            'employee_id' => $employee->id,
            'amount' => 2500,
            'request_date' => '2026-10-10',
            'salary_month' => '2026-10',
            'status' => 'approved',
        ]);
        EmployeeLeave::create([
            'source_key' => 'lv_1',
            'employee_id' => $employee->id,
            'leave_type' => 'Casual',
            'start_date' => '2026-10-12',
            'end_date' => '2026-10-12',
            'status' => 'pending',
        ]);

        // Guests are rejected with 401 on API and redirected on web route
        $this->getJson('/api/employees')->assertUnauthorized();
        $this->getJson('/api/salary-advances')->assertUnauthorized();
        $this->getJson('/api/employee-leaves')->assertUnauthorized();
        $this->get('/admin/employees')->assertRedirect(route('login'));

        $salesman = User::factory()->role('salesman')->create();
        $viewer = User::factory()->role('viewer')->create();
        $manager = User::factory()->role('manager')->create();

        foreach ([$salesman, $viewer, $manager] as $ordinaryUser) {
            $this->actingAs($ordinaryUser)->get('/admin/employees')->assertForbidden();

            $this->actingAs($ordinaryUser)->getJson('/api/employees')->assertForbidden();
            $this->actingAs($ordinaryUser)->putJson('/api/employees/emp_new', [
                'name' => 'Blocked',
                'phone' => '0770000000',
                'location' => 'Colombo',
            ])->assertForbidden();
            $this->actingAs($ordinaryUser)->deleteJson('/api/employees/emp_1')->assertForbidden();

            $this->actingAs($ordinaryUser)->getJson('/api/salary-advances')->assertForbidden();
            $this->actingAs($ordinaryUser)->putJson('/api/salary-advances/adv_new', [
                'employeeId' => 'emp_1',
                'amount' => 1000,
                'requestDate' => '2026-10-11',
                'salaryMonth' => '2026-10',
            ])->assertForbidden();
            $this->actingAs($ordinaryUser)->deleteJson('/api/salary-advances/adv_1')->assertForbidden();

            $this->actingAs($ordinaryUser)->getJson('/api/employee-leaves')->assertForbidden();
            $this->actingAs($ordinaryUser)->putJson('/api/employee-leaves/lv_new', [
                'employeeId' => 'emp_1',
                'leaveType' => 'Casual',
                'startDate' => '2026-10-14',
                'endDate' => '2026-10-14',
            ])->assertForbidden();
            $this->actingAs($ordinaryUser)->deleteJson('/api/employee-leaves/lv_1')->assertForbidden();
        }
    }
}
