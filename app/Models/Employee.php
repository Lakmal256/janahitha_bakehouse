<?php

namespace App\Models;

use App\Models\Concerns\HasSourceKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasSourceKey;

    protected $fillable = [
        'source_key',
        'name',
        'phone',
        'location',
    ];

    public function salaryAdvances(): HasMany
    {
        return $this->hasMany(SalaryAdvance::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(EmployeeLeave::class);
    }
}
