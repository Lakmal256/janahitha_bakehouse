<?php

namespace App\Http\Controllers\Api;

use App\Documents\RouteDocuments;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SaveRouteRequest;
use App\Http\Resources\RouteResource;
use App\Models\Route as BakeryRoute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class RouteController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $routes = BakeryRoute::query()
            ->unless($user->seesAllRoutes(), fn (Builder $query) => $query->visibleTo($user))
            ->orderBy('sort')
            ->orderBy('name')
            ->get();

        return RouteResource::collection($routes);
    }

    /**
     * Save a route: PUT creates or replaces it, PATCH changes the given fields of an existing one.
     */
    public function update(SaveRouteRequest $request, RouteDocuments $routes, string $key): JsonResponse
    {
        abort_if($request->isMethod('patch') && ! BakeryRoute::query()->where('source_key', $key)->exists(), 404);

        $route = $routes->save($key, $request->validated(), replace: $request->isMethod('put'));

        return (new RouteResource($route))->response()->setStatusCode($route->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(RouteDocuments $routes, string $key): Response
    {
        Gate::authorize('routes');

        $routes->delete(BakeryRoute::query()->where('source_key', $key)->firstOrFail());

        return response()->noContent();
    }
}
