<?php

namespace App\Documents;

use App\Models\Route as BakeryRoute;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Reads and writes the browser app's `users` documents:
 * {name, username, owner, perms: {permission: bool}, routes: "all"|[routeKey], active, createdAt}.
 * Password hashes never leave the server.
 */
class UserDocuments
{
    /**
     * Create or update an account. The owner account always keeps full access.
     *
     * @param  array{name: string, username: string, password?: ?string, perms?: array<string, bool>, routes?: string|list<string>, active?: bool}  $data
     */
    public function save(string $key, array $data, ?User $user): User
    {
        return DB::transaction(function () use ($key, $data, $user): User {
            $user ??= new User(['source_key' => $key, 'is_owner' => false]);
            $wasActive = $user->exists && $user->active;

            $user->name = trim($data['name']);
            $user->username = Str::lower(trim($data['username']));

            if (filled($data['password'] ?? null)) {
                $user->forceFill([
                    'password' => $data['password'],
                    'legacy_password_hash' => null,
                    'legacy_password_salt' => null,
                ]);
            }

            $routeKeys = null;

            if ($user->is_owner) {
                $user->perms = array_fill_keys(User::PERMISSIONS, true);
                $user->role = 'admin';
                $user->all_routes = true;
                $user->active = true;
            } else {
                $permissions = $this->permissions($data['perms'] ?? []);
                $user->perms = $permissions;
                $user->role = User::roleMatching($permissions) ?? ($user->role ?: 'viewer');
                $user->all_routes = ($data['routes'] ?? 'all') === 'all';
                $user->active = (bool) ($data['active'] ?? true);
                $routeKeys = $user->all_routes ? [] : $data['routes'];
            }

            $user->save();

            if ($routeKeys !== null) {
                $user->routes()->sync(BakeryRoute::query()->whereIn('source_key', $routeKeys)->pluck('id'));
            }

            if ($wasActive && ! $user->active) {
                $this->endSessions($user);
            }

            return $user;
        });
    }

    public function delete(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $this->endSessions($user);
            $user->delete();
        });
    }

    /**
     * Copy an account from the browser app's backup, keeping its password working.
     * Existing accounts are never overwritten.
     *
     * @param  array<string, mixed>  $document
     * @return 'created'|'existing'|'username_taken'|'skipped'
     */
    public function import(string $key, array $document): string
    {
        $username = Str::lower(trim((string) ($document['username'] ?? '')));

        if ($username === '' || blank($document['passHash'] ?? null) || blank($document['salt'] ?? null)) {
            return 'skipped';
        }

        if (User::query()->where('source_key', $key)->exists()) {
            return 'existing';
        }

        if (User::query()->where('username', $username)->exists()) {
            return 'username_taken';
        }

        $isOwner = (bool) ($document['owner'] ?? false);
        $permissions = $isOwner ? array_fill_keys(User::PERMISSIONS, true) : $this->permissions((array) ($document['perms'] ?? []));
        $routes = $document['routes'] ?? 'all';
        $allRoutes = $isOwner || $routes === 'all' || ! is_array($routes);

        $user = new User([
            'source_key' => $key,
            'name' => trim((string) ($document['name'] ?? '')) ?: $username,
            'username' => $username,
            'perms' => $permissions,
            'role' => User::roleMatching($permissions) ?? 'viewer',
            'all_routes' => $allRoutes,
            'active' => $isOwner || ($document['active'] ?? true) !== false,
            'is_owner' => $isOwner,
            'password' => Str::password(40),
        ]);
        $user->forceFill([
            'legacy_password_hash' => (string) $document['passHash'],
            'legacy_password_salt' => (string) $document['salt'],
        ]);

        if (DocumentKey::timestamp($document['createdAt'] ?? null)) {
            $user->created_at = DocumentKey::timestamp($document['createdAt']);
        }

        $user->save();

        if (! $allRoutes) {
            $user->routes()->sync(BakeryRoute::query()->whereIn('source_key', array_map('strval', $routes))->pluck('id'));
        }

        return 'created';
    }

    /**
     * @param  array<string, mixed>  $permissions
     * @return array<string, bool>
     */
    private function permissions(array $permissions): array
    {
        $result = [];

        foreach (User::PERMISSIONS as $permission) {
            $result[$permission] = (bool) ($permissions[$permission] ?? false);
        }

        return $result;
    }

    private function endSessions(User $user): void
    {
        DB::table('sessions')->where('user_id', $user->id)->delete();
    }
}
