<?php

namespace App\Models;

use App\Models\Concerns\HasSourceKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryAdvance extends Model
{
    use HasSourceKey;

    public const STATUSES = ['pending', 'approved', 'rejected'];

    protected $fillable = [
        'source_key',
        'employee_id',
        'amount',
        'request_date',
        'salary_month',
        'reason',
        'status',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'request_date' => 'date:Y-m-d',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
