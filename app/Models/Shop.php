<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $fillable = ['source_key', 'name', 'route_id', 'sort', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'shop_items', 'shop_id', 'item_id')
            ->withPivot(['sort', 'price'])
            ->orderByPivot('sort');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->all_routes) {
            return $query;
        }

        return $query->whereIn('route_id', $user->routes()->select('routes.id'));
    }
}
