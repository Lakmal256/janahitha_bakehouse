<?php

namespace App\Http\Controllers\Api;

use App\Documents\ShopDocuments;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SaveShopRequest;
use App\Http\Resources\ShopResource;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ShopController extends Controller
{
    private const RELATIONS = ['route:id,source_key', 'items:items.id,items.source_key'];

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $shops = Shop::query()
            ->where('active', true)
            ->unless($user->seesAllRoutes(), fn (Builder $query) => $query->visibleTo($user))
            ->with(self::RELATIONS)
            ->orderBy('sort')
            ->orderBy('name')
            ->get();

        return ShopResource::collection($shops);
    }

    /**
     * Save a shop: PUT creates or replaces it, PATCH changes the given fields of an existing one.
     */
    public function update(SaveShopRequest $request, ShopDocuments $shops, string $key): JsonResponse
    {
        abort_if($request->isMethod('patch') && ! Shop::query()->where('source_key', $key)->exists(), 404);

        $shop = $shops->save($key, $request->validated(), replace: $request->isMethod('put'));

        return (new ShopResource($shop->load(self::RELATIONS)))->response()->setStatusCode($shop->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Delete a shop, or archive it when it has orders or bills.
     */
    public function destroy(ShopDocuments $shops, string $key): JsonResponse
    {
        Gate::authorize('shops');

        $archived = $shops->delete(Shop::query()->where('source_key', $key)->firstOrFail());

        return response()->json(['data' => ['archived' => $archived]]);
    }
}
