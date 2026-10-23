<?php

namespace App\Models;

use App\Models\Concerns\HasSourceKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Route extends Model
{
    use HasSourceKey;

    protected $fillable = ['source_key', 'name', 'driver', 'sort'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function shops(): HasMany
    {
        return $this->hasMany(Shop::class)->orderBy('sort');
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

        return $query->whereIn('id', $user->routes()->select('routes.id'));
    }
}
