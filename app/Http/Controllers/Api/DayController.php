<?php

namespace App\Http\Controllers\Api;

use App\Documents\DayDocuments;
use App\Http\Controllers\Api\Concerns\ResolvesRouteDay;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SaveDayRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class DayController extends Controller
{
    use ResolvesRouteDay;

    /**
     * List the orders of the routes the user works on, one document per route and day.
     */
    public function index(Request $request, DayDocuments $days): JsonResponse
    {
        return response()->json(['data' => $days->query($request->user(), $this->routeDayFilters($request))]);
    }

    /**
     * Merge shop orders into a route's day.
     */
    public function update(SaveDayRequest $request, DayDocuments $days, string $key): JsonResponse
    {
        [$date, $route] = $this->resolveRouteDay($request, $key);

        $days->merge($date, $route, $request->validated('shops'), $request->user());

        return response()->json([
            'data' => $days->query($request->user(), ['date' => $date, 'routeId' => $route->source_key])[0] ?? null,
        ]);
    }

    /**
     * Delete all orders for a route and day.
     */
    public function destroy(Request $request, DayDocuments $days, string $key): Response
    {
        Gate::authorize('orders');
        [$date, $route] = $this->resolveRouteDay($request, $key);

        $days->delete($date, $route);

        return response()->noContent();
    }
}
