<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['date', 'route_id', 'shop_id', 'created_by'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->all_routes) {
            return $query;
        }

        return $query->whereIn('route_id', $user->routes()->select('routes.id'));
    }
}
