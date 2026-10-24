<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SaveSalaryAdvanceRequest;
use App\Http\Resources\SalaryAdvanceResource;
use App\Models\Employee;
use App\Models\SalaryAdvance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class SalaryAdvanceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('employees');

        $query = SalaryAdvance::query()
            ->with('employee:id,source_key,name')
            ->orderByDesc('request_date')
            ->orderByDesc('id');

        if ($request->filled('salaryMonth')) {
            $query->where('salary_month', $request->string('salaryMonth'));
        }

        if ($request->filled('employeeId')) {
            $query->whereHas('employee', fn ($q) => $q->where('source_key', $request->string('employeeId')));
        }

        $advances = $query->get();

        $approvedTotals = SalaryAdvance::query()
            ->with('employee:id,source_key,name')
            ->where('status', 'approved')
            ->get()
            ->groupBy(fn (SalaryAdvance $advance): string => ($advance->employee?->source_key ?? '').'|'.$advance->salary_month)
            ->map(function ($group): array {
                /** @var SalaryAdvance $first */
                $first = $group->first();

                return [
                    'employeeId' => $first->employee?->source_key,
                    'employeeName' => $first->employee?->name ?? '',
                    'salaryMonth' => $first->salary_month,
                    'totalApproved' => round((float) $group->sum('amount'), 2),
                    'count' => $group->count(),
                ];
            })
            ->values()
            ->all();

        return SalaryAdvanceResource::collection($advances)->additional([
            'meta' => [
                'approvedTotals' => $approvedTotals,
            ],
        ]);
    }

    public function update(SaveSalaryAdvanceRequest $request, string $key): JsonResponse
    {
        abort_if($request->isMethod('patch') && ! SalaryAdvance::query()->where('source_key', $key)->exists(), 404);

        $data = $request->validated();
        $replace = $request->isMethod('put');
        $advance = SalaryAdvance::query()->firstOrNew(['source_key' => $key]);

        if ($replace || array_key_exists('employeeId', $data)) {
            $employee = Employee::query()->where('source_key', $data['employeeId'])->firstOrFail();
            $advance->employee_id = $employee->id;
        }

        if ($replace || array_key_exists('amount', $data)) {
            $advance->amount = round((float) $data['amount'], 2);
        }

        if ($replace || array_key_exists('requestDate', $data)) {
            $advance->request_date = $data['requestDate'];
        }

        if ($replace || array_key_exists('salaryMonth', $data)) {
            $advance->salary_month = $data['salaryMonth'];
        }

        if ($replace || array_key_exists('status', $data)) {
            $advance->status = $data['status'] ?? 'pending';
        } elseif (! $advance->exists) {
            $advance->status = 'pending';
        }

        if (! $advance->exists) {
            $advance->created_by = $request->user()?->id;
        }

        $advance->save();
        $advance->load('employee:id,source_key,name');

        return (new SalaryAdvanceResource($advance))
            ->response()
            ->setStatusCode($advance->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(string $key): Response
    {
        Gate::authorize('employees');

        $advance = SalaryAdvance::query()->where('source_key', $key)->firstOrFail();
        $advance->delete();

        return response()->noContent();
    }
}
