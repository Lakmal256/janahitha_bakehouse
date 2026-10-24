<?php

namespace App\Http\Resources;

use App\Models\EmployeeLeave;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EmployeeLeave
 */
class EmployeeLeaveResource extends JsonResource
{
    /**
     * Transform the leave record into the browser app's `employee-leaves` document.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->source_key,
            'employeeId' => $this->employee?->source_key,
            'employeeName' => $this->employee?->name ?? '',
            'leaveType' => $this->leave_type,
            'startDate' => $this->start_date?->format('Y-m-d'),
            'endDate' => $this->end_date?->format('Y-m-d'),
            'reason' => $this->reason ?? '',
            'status' => $this->status,
            'createdAt' => $this->created_at?->getTimestampMs(),
        ];
    }
}
