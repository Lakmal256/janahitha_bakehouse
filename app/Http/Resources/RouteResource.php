<?php

namespace App\Http\Resources;

use App\Models\Route as BakeryRoute;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BakeryRoute
 */
class RouteResource extends JsonResource
{
    /**
     * Transform the route into the browser app's `routes` document.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->source_key,
            'name' => $this->name,
            'driver' => $this->driver ?? '',
            'order' => $this->sort,
        ];
    }
}
