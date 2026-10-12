<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
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
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'username', 'role', 'perms', 'all_routes', 'active', 'is_owner', 'email', 'password',
    ];

    protected $hidden = ['password', 'remember_token'];

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

    public function canAccessRoute(int|Route $route): bool
    {
        if ($this->all_routes) {
            return true;
        }

        $routeId = $route instanceof Route ? $route->getKey() : $route;

        return $this->routes()->where('routes.id', $routeId)->exists();
    }
}
