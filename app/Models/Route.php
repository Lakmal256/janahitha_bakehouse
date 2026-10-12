<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
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
}
