<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class BillLine extends Model
{
    protected $fillable = ['bill_id', 'item_id', 'name', 'qty', 'ret', 'price'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
