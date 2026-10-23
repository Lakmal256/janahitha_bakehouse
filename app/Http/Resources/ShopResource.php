<?php

namespace App\Http\Resources;

use App\Models\Item;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Shop
 */
class ShopResource extends JsonResource
{
    /**
     * Transform the shop into the browser app's `shops` document.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->source_key,
            'name' => $this->name,
            'routeId' => $this->route->source_key,
            'items' => $this->items->map(fn (Item $item): string => $item->source_key)->values()->all(),
            'prices' => (object) $this->items
                ->filter(fn (Item $item): bool => $item->pivot->price !== null)
                ->mapWithKeys(fn (Item $item): array => [$item->source_key => (float) $item->pivot->price])
                ->all(),
            'order' => $this->sort,
        ];
    }
}
