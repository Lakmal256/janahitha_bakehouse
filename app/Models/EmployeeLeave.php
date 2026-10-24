<?php

namespace App\Models;

use App\Models\Concerns\HasSourceKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLeave extends Model
{
    use HasSourceKey;

    public const STATUSES = ['pending', 'approved', 'rejected'];

    public const TYPES = ['Casual', 'Sick', 'Annual', 'Unpaid', 'Other'];

    protected $fillable = [
        'source_key',
        'employee_id',
        'leave_type',
        'start_date',
        'end_date',
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
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
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
