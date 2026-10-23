<?php

namespace App\Documents;

use App\Models\Route as BakeryRoute;
use Illuminate\Validation\ValidationException;

/**
 * Reads and writes the browser app's `routes` documents: {name, driver, order}.
 */
class RouteDocuments
{
    /**
     * Create or update a route. A full save replaces every field; a partial save only the given ones.
     *
     * @param  array{name?: string, driver?: ?string, order?: ?int}  $data
     */
    public function save(string $key, array $data, bool $replace): BakeryRoute
    {
        $route = BakeryRoute::query()->firstOrNew(['source_key' => $key]);

        if ($replace || array_key_exists('name', $data)) {
            $route->name = trim($data['name']);
        }

        if ($replace || array_key_exists('driver', $data)) {
            $route->driver = trim((string) ($data['driver'] ?? '')) ?: null;
        }

        if ($replace || array_key_exists('order', $data)) {
            $route->sort = (int) ($data['order'] ?? 0);
        }

        $route->save();

        return $route;
    }

    /**
     * Delete a route that has no shops and no order or bill history.
     */
    public function delete(BakeryRoute $route): void
    {
        if ($route->shops()->exists()) {
            throw ValidationException::withMessages([
                'route' => 'Move or delete this route\'s shops before deleting the route.',
            ]);
        }

        if ($route->orders()->exists() || $route->bills()->exists()) {
            throw ValidationException::withMessages([
                'route' => 'This route has saved orders or bills, so it cannot be deleted.',
            ]);
        }

        $route->delete();
    }
}
