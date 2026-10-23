<?php

namespace App\Http\Resources;

use App\Models\Route as BakeryRoute;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the account into the browser app's `users` document, without any password data.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->source_key,
            'name' => $this->name,
            'username' => $this->username,
            'owner' => $this->is_owner,
            'perms' => collect(User::PERMISSIONS)->mapWithKeys(fn (string $permission): array => [$permission => $this->canPermission($permission)])->all(),
            'routes' => $this->all_routes ? 'all' : $this->routes->map(fn (BakeryRoute $route): string => $route->source_key)->values()->all(),
            'active' => $this->active,
            'createdAt' => $this->created_at?->getTimestampMs(),
        ];
    }
}
