<?php

namespace App\Http\Controllers\Api;

use App\Documents\BillDocuments;
use App\Http\Controllers\Api\Concerns\ResolvesRouteDay;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SaveBillRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class BillController extends Controller
{
    use ResolvesRouteDay;

    /**
     * List the bills of the routes the user works on, one document per route and day.
     */
    public function index(Request $request, BillDocuments $bills): JsonResponse
    {
        return response()->json(['data' => $bills->query($request->user(), $this->routeDayFilters($request))]);
    }

    /**
     * Merge shop bills into a route's day, after checking the permissions the change needs.
     */
    public function update(SaveBillRequest $request, BillDocuments $bills, string $key): JsonResponse
    {
        [$date, $route] = $this->resolveRouteDay($request, $key);
        $shops = $request->validated('shops');

        foreach ($bills->requiredPermissions($date, $shops) ?: ['bills_generate'] as $permission) {
            Gate::authorize($permission);
        }

        $bills->merge($date, $route, $shops, $request->user());

        return response()->json([
            'data' => $bills->query($request->user(), ['date' => $date, 'routeId' => $route->source_key])[0] ?? null,
        ]);
    }

    /**
     * Delete all bills for a route and day.
     */
    public function destroy(Request $request, BillDocuments $bills, string $key): Response
    {
        Gate::authorize('bills_generate');
        [$date, $route] = $this->resolveRouteDay($request, $key);

        $bills->delete($date, $route);

        return response()->noContent();
    }
}
