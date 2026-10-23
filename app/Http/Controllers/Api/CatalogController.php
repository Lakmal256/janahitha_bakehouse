<?php

namespace App\Http\Controllers\Api;

use App\Documents\CatalogDocument;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SaveCatalogRequest;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CatalogController extends Controller
{
    /**
     * List the browser app's `config` documents; the only one is the item catalog.
     */
    public function index(CatalogDocument $catalog): JsonResponse
    {
        return response()->json(['data' => [$this->document($catalog)]]);
    }

    public function update(SaveCatalogRequest $request, CatalogDocument $catalog, string $key): JsonResponse
    {
        abort_unless($key === 'catalog', 404);

        $catalog->save($request->validated('items'));

        return response()->json(['data' => $this->document($catalog)]);
    }

    public function destroy(CatalogDocument $catalog, string $key): Response
    {
        Gate::authorize('items');

        $catalog->delete(Item::query()->where('source_key', $key)->firstOrFail());

        return response()->noContent();
    }

    /**
     * @return array{id: string, items: mixed}
     */
    private function document(CatalogDocument $catalog): array
    {
        return ['id' => 'catalog', 'items' => ItemResource::collection($catalog->items())];
    }
}
