<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\HasSourceKey;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    public const PERMISSIONS = [
        'orders', 'bills_view', 'bills_generate', 'bills_returns', 'bills_payments',
        'bills_prices', 'reports', 'shops', 'routes', 'items', 'users',
    ];

    /** @use HasFactory<UserFactory> */
    use HasFactory, HasSourceKey, Notifiable;

    protected $fillable = [
        'source_key', 'name', 'username', 'role', 'perms', 'all_routes', 'active', 'is_owner', 'email', 'password',
    ];

    protected $hidden = ['password', 'remember_token', 'legacy_password_hash', 'legacy_password_salt'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'perms' => 'array',
            'all_routes' => 'boolean',
            'active' => 'boolean',
            'is_owner' => 'boolean',
        ];
    }

    public function routes(): BelongsToMany
    {
        return $this->belongsToMany(Route::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'created_by');
    }

    public static function permissionsForRole(string $role): array
    {
        return self::rolePresets()[$role] ?? array_fill_keys(self::PERMISSIONS, false);
    }

    public static function rolePresets(): array
    {
        $admin = array_fill_keys(self::PERMISSIONS, true);
        $manager = $admin;
        $manager['users'] = false;
        $salesman = array_fill_keys(self::PERMISSIONS, false);
        $viewer = array_fill_keys(self::PERMISSIONS, false);

        foreach (['orders', 'bills_view', 'bills_returns', 'bills_payments'] as $permission) {
            $salesman[$permission] = true;
        }

        foreach (['bills_view', 'reports'] as $permission) {
            $viewer[$permission] = true;
        }

        return [
            'admin' => $admin,
            'manager' => $manager,
            'salesman' => $salesman,
            'viewer' => $viewer,
        ];
    }

    public function canPermission(string $permission): bool
    {
        if ($this->is_owner) {
            return true;
        }

        $rolePermissions = self::permissionsForRole($this->role);

        return (bool) ($this->perms[$permission] ?? $rolePermissions[$permission] ?? false);
    }

    public function isAdmin(): bool
    {
        return $this->is_owner || $this->role === 'admin' || $this->canPermission('users');
    }

    /**
     * Whether the account sees every route and shop, either because it works on all routes
     * or because it manages shops, routes or users.
     */
    public function seesAllRoutes(): bool
    {
        return $this->all_routes || $this->canPermission('shops') || $this->canPermission('routes') || $this->canPermission('users');
    }

    public function canAccessRoute(int|Route $route): bool
    {
        if ($this->all_routes) {
            return true;
        }

        $routeId = $route instanceof Route ? $route->getKey() : $route;

        return $this->routes()->where('routes.id', $routeId)->exists();
    }

    /**
     * Get the preset role whose permissions match exactly, if any.
     *
     * @param  array<string, bool>  $permissions
     */
    public static function roleMatching(array $permissions): ?string
    {
        foreach (self::rolePresets() as $role => $preset) {
            if ($preset == array_merge(array_fill_keys(self::PERMISSIONS, false), $permissions)) {
                return $role;
            }
        }

        return null;
    }

    /**
     * Check a password against the hash the browser app stored, and switch the account to a
     * standard password hash when it matches.
     */
    public function upgradeLegacyPassword(string $password): bool
    {
        if ($this->legacy_password_hash === null || $this->legacy_password_salt === null) {
            return false;
        }

        if (! hash_equals($this->legacy_password_hash, self::legacyPasswordHash($password, $this->legacy_password_salt, $this->legacy_password_hash))) {
            return false;
        }

        $this->forceFill([
            'password' => $password,
            'legacy_password_hash' => null,
            'legacy_password_salt' => null,
        ])->save();

        return true;
    }

    /**
     * Rebuild the browser app's hash: SHA-256 of "salt:password", or its FNV-1a fallback
     * ("f" followed by hex) on browsers without Web Crypto.
     */
    private static function legacyPasswordHash(string $password, string $salt, string $storedHash): string
    {
        $text = $salt.':'.$password;

        if (strlen($storedHash) === 64) {
            return hash('sha256', $text);
        }

        $hash = 2166136261;
        foreach (unpack('n*', mb_convert_encoding($text, 'UTF-16BE', 'UTF-8')) as $codeUnit) {
            $hash ^= $codeUnit;
            $hash = ($hash * 16777619) & 0xFFFFFFFF;
        }

        return 'f'.dechex($hash);
    }
}
