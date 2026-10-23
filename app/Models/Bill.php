<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    protected $fillable = [
        'date', 'route_id', 'shop_id', 'order_id', 'shop_name', 'total', 'paid', 'pay_method', 'status',
        'paid_at', 'generated_at', 'edited_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total' => 'decimal:2',
            'paid' => 'decimal:2',
            'paid_at' => 'datetime',
            'generated_at' => 'datetime',
            'edited_at' => 'datetime',
        ];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BillLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->all_routes) {
            return $query;
        }

        return $query->whereIn('route_id', $user->routes()->select('routes.id'));
    }
}
