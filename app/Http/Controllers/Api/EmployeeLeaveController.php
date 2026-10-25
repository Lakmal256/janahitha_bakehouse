<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SaveEmployeeLeaveRequest;
use App\Http\Resources\EmployeeLeaveResource;
use App\Models\Employee;
use App\Models\EmployeeLeave;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class EmployeeLeaveController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('employees');

        $query = EmployeeLeave::query()
            ->with('employee:id,source_key,name')
            ->orderByDesc('start_date')
            ->orderByDesc('id');

        if ($request->filled('employeeId')) {
            $query->whereHas('employee', fn ($q) => $q->where('source_key', $request->string('employeeId')));
        }

        return EmployeeLeaveResource::collection($query->get());
    }

    public function update(SaveEmployeeLeaveRequest $request, string $key): JsonResponse
    {
        abort_if($request->isMethod('patch') && ! EmployeeLeave::query()->where('source_key', $key)->exists(), 404);

        $data = $request->validated();
        $replace = $request->isMethod('put');
        $leave = EmployeeLeave::query()->firstOrNew(['source_key' => $key]);

        if ($replace || array_key_exists('employeeId', $data)) {
            $employee = Employee::query()->where('source_key', $data['employeeId'])->firstOrFail();
            $leave->employee_id = $employee->id;
        }

        if ($replace || array_key_exists('leaveType', $data)) {
            $leave->leave_type = trim((string) $data['leaveType']);
        }

        if ($replace || array_key_exists('startDate', $data)) {
            $leave->start_date = $data['startDate'];
        }

        if ($replace || array_key_exists('endDate', $data)) {
            $leave->end_date = $data['endDate'];
        }

        if ($replace || array_key_exists('reason', $data)) {
            $leave->reason = isset($data['reason']) ? trim((string) $data['reason']) : null;
        }

        if ($replace || array_key_exists('status', $data)) {
            $leave->status = $data['status'] ?? 'pending';
        } elseif (! $leave->exists) {
            $leave->status = 'pending';
        }

        if (! $leave->exists) {
            $leave->created_by = $request->user()?->id;
        }

        $leave->save();
        $leave->load('employee:id,source_key,name');

        return (new EmployeeLeaveResource($leave))
            ->response()
            ->setStatusCode($leave->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(string $key): Response
    {
        Gate::authorize('employees');

        $leave = EmployeeLeave::query()->where('source_key', $key)->firstOrFail();
        $leave->delete();

        return response()->noContent();
    }
}
