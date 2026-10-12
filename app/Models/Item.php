<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = ['source_key', 'name', 'default_price', 'sort'];

    protected function casts(): array
    {
        return ['default_price' => 'decimal:2'];
    }

    public function shops(): BelongsToMany
    {
        return $this->belongsToMany(Shop::class, 'shop_items', 'item_id', 'shop_id')
            ->withPivot(['sort', 'price']);
    }

    public function orderLines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    public function billLines(): HasMany
    {
        return $this->hasMany(BillLine::class);
    }
}
