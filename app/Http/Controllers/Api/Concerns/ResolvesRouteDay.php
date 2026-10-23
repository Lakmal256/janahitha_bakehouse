<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Documents\DocumentKey;
use App\Models\Route as BakeryRoute;
use Illuminate\Http\Request;

trait ResolvesRouteDay
{
    /**
     * Find the day and route of a "YYYY-MM-DD_routeKey" document the user may work on.
     *
     * @return array{0: string, 1: BakeryRoute}
     */
    protected function resolveRouteDay(Request $request, string $key): array
    {
        [$date, $routeKey] = DocumentKey::parse($key) ?? abort(404);
        $route = BakeryRoute::query()->where('source_key', $routeKey)->firstOrFail();

        abort_unless($request->user()->canAccessRoute($route), 403);
        abort_if($request->has('date') && $request->input('date') !== $date, 422, 'The date does not match the record.');
        abort_if($request->has('routeId') && $request->input('routeId') !== $routeKey, 422, 'The route does not match the record.');

        return [$date, $route];
    }

    /**
     * @return array{date?: string, from?: string, to?: string, routeId?: string}
     */
    protected function routeDayFilters(Request $request): array
    {
        return $request->validate([
            'date' => ['sometimes', 'date_format:Y-m-d'],
            'from' => ['sometimes', 'date_format:Y-m-d'],
            'to' => ['sometimes', 'date_format:Y-m-d'],
            'routeId' => ['sometimes', 'string', 'max:191'],
        ]);
    }
}
