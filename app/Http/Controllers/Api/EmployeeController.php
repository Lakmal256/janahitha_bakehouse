<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SaveEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class EmployeeController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('employees');

        $employees = Employee::query()
            ->orderBy('name')
            ->get();

        return EmployeeResource::collection($employees);
    }

    public function update(SaveEmployeeRequest $request, string $key): JsonResponse
    {
        abort_if($request->isMethod('patch') && ! Employee::query()->where('source_key', $key)->exists(), 404);

        $data = $request->validated();
        $replace = $request->isMethod('put');
        $employee = Employee::query()->firstOrNew(['source_key' => $key]);

        if ($replace || array_key_exists('name', $data)) {
            $employee->name = trim((string) $data['name']);
        }

        if ($replace || array_key_exists('phone', $data)) {
            $employee->phone = trim((string) $data['phone']);
        }

        if ($replace || array_key_exists('location', $data)) {
            $employee->location = trim((string) $data['location']);
        }

        $employee->save();

        return (new EmployeeResource($employee))
            ->response()
            ->setStatusCode($employee->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(string $key): Response
    {
        Gate::authorize('employees');

        $employee = Employee::query()->where('source_key', $key)->firstOrFail();
        $employee->delete();

        return response()->noContent();
    }
}
